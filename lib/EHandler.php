<?php

namespace OS\ZEventHandler;

use Bitrix\Main,
    Bitrix\Main\ModuleManager,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc,
    Bitrix\Sale,
    Bitrix\Sale\Order,
    Bitrix\Sale\PaySystem,
    Bitrix\Main\Request,
    Bitrix\Sale\Payment,
    Bitrix\Sale\PaySystem\ServiceResult,
    Bitrix\Sale\PaymentCollection,
    Bitrix\Main\Diag\Debug,
    Bitrix\Sale\PriceMaths,
    Bitrix\Main\Application,
    Bitrix\Sale\BusinessValue,
    Bitrix\Main\ArgumentOutOfRangeException,
    Bitrix\Main\Event,
    Bitrix\Main\EventManager,
    Bitrix\Main\EventResult,
    Bitrix\Main\LoaderException,
    Bitrix\Main\ObjectNotFoundException,
    Bitrix\Main\ORM\EntityError,
    Bitrix\Main\SystemException,
    Bitrix\Main\Loader,
    Bitrix\Sale\Internals\Catalog,
    Bitrix\Sale\Result,
    Bitrix\Sale\ResultError,
    Bitrix\Main\Entity,
    OS\Helper\DataHelper,
    Exception;

Loc::loadMessages(__FILE__);

/**
 * @param Event $event
 * @return EventResult|void
 *@package OS\ZEventHandler
 * Author: mintali
 * Email : mohammadali.namazi@zoodpay.com
 * Date: 2021-05-16, Sun, 21:40
 * File: EHandler
 * Path: bitrix/modules/zoodpay.payment/lib/EHandler.php
 * Line: 49
 */
class EHandler
{

    /**
     * @param Event $event
     * @return EventResult|void
     */
    public static function OnSaleOrderBeforeSavedHandler(Event $event)
    {


        $errorMessage = Loc::getMessage('SALE_ZP_NOT_VALID');
        try {
            $arOrder = $event->getParameter("ENTITY");
            $orderValues = $arOrder->getFieldValues();
            $payID = $orderValues['PAY_SYSTEM_ID'];
            $shipment = $arOrder->getShipmentCollection();
            $zDataHelper = new DataHelper();
            if (($_POST['action'] == 'updatePaymentStatus') && ((!isset($_POST['refund'])) && (isset($_POST['data']['RETURN_AMOUNT'])))) {
                global $USER;

                $refundStatus = "";
                $transactionDetails = $zDataHelper->getTotalRowData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_Merchant_Order_Ref, $arOrder->getId());
                if (!empty($transactionDetails)) {
                    if (isset($_POST)) {
                        if ($transactionDetails['payment_id'] == $_POST['paymentId']) {
                            if (($_POST['action'] == 'updatePaymentStatus') && isset($_POST['data']['RETURN_AMOUNT'])) {
                                if ($transactionDetails['amount'] >= floatval($_POST['data']['RETURN_AMOUNT'])) {
                                    $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, true, DataHelper::PAYMENT_SYSTEM_ID, $payID);
                                    $merchantData = null;
                                    if (isset($queryResult) && $queryResult != null) {
//                                        $queryResultJsonDecode = json_decode($queryResult['config'], true);
                                        $consumerKey = "PAYSYSTEM_" . $payID;
                                        $merchantData = array(
                                            'merchant_key' => BusinessValue::get(DataHelper::ZOODPAY_USER, $consumerKey),
                                            'merchant_secret' => BusinessValue::get(DataHelper::ZOODPAY_PWD, $consumerKey),
                                            'merchant_salt' => BusinessValue::get(DataHelper::ZOODPAY_SALT, $consumerKey),
                                            'API_URL' => BusinessValue::get(DataHelper::ZOODPAY_API_URL, $consumerKey),
                                            'API_Ver' => BusinessValue::get(DataHelper::ZOODPAY_API_VER, $consumerKey),
                                            'SITE_ID' => BusinessValue::get(DataHelper::ZOODPAY_SITE_ID, $consumerKey),
                                            'REFUND_INITIATED_STATUS' => BusinessValue::get(DataHelper::ZP_REFUND_INITIATED_STATUS, $consumerKey),
                                            'REFUND_APPROVED_STATUS' => BusinessValue::get(DataHelper::ZP_REFUND_APPROVED_STATUS, $consumerKey),
                                            'REFUND_DECLINED_STATUS' => BusinessValue::get(DataHelper::ZP_REFUND_DECLINED_STATUS, $consumerKey),
                                        );
                                    }else{
                                        $errorMessage = Loc::getMessage('SALE_ZP_NOT_ALLOWED');
                                        goto ZError;

                                    }
                                    $apiUrl = $merchantData['API_URL'] . $merchantData['API_Ver'] . $zDataHelper::API_RefundTransaction;
                                    $addRefund = date("is", time());
                                    $merchant_refund_reference = $arOrder->getId() . $addRefund . '-refund';
                                    $transaction_id = $transactionDetails['transaction_id'];
                                    $refund_amount = $_POST['data']['RETURN_AMOUNT'];
                                    $data = [

                                        "merchant_refund_reference" => $merchant_refund_reference,
                                        "reason" => $_POST['data']['PAY_RETURN_COMMENT_0'],
                                        "refund_amount" => $refund_amount,
                                        'request_id' => $merchant_refund_reference,
                                        "transaction_id" => $transaction_id
                                    ];
                                    $curlResponse = $zDataHelper->curlPost($merchantData['merchant_key'], $merchantData['merchant_secret'], $data, $apiUrl);
                                    if ($curlResponse['statusCode'] == 201) {
                                        $curlResponseJsonDecode = json_decode($curlResponse['response'], true);
                                        $refundStatus = $curlResponseJsonDecode['refund']['status'];
                                        switch ($curlResponseJsonDecode['refund']['status']) {
                                            case "Initiated" :
                                            {
                                                $paymentCollection = $arOrder->getPaymentCollection();
                                                $GeneralPayID = null;
                                                $SumAmount = null;
                                                foreach ($paymentCollection as $pay => $value) {
                                                    if ($value->getField('IS_RETURN') == "Y") {
                                                        $GeneralPayID = $value->getId();
                                                        $SumAmount = $value->getSum();
                                                    }

                                                }
                                                $payment = $paymentCollection->createItem(
                                                    \Bitrix\Sale\PaySystem\Manager::getObjectById($payID)
                                                );
                                                $refund_amount = -1 * $refund_amount;
                                                try {
                                                    $payment->setField("PS_SUM", $refund_amount);
                                                    $payment->setField("SUM", $refund_amount);
                                                    $payment->setField("IS_RETURN", "N");
                                                    $payment->setField("CURRENCY", $arOrder->getCurrency());
                                                    $payment->setPaid('N');
                                                    $payment->setReturn("P");
                                                    $payment->setField('PS_STATUS_CODE', $merchantData['REFUND_INITIATED_STATUS']);
                                                    $payment->setField('PS_STATUS_MESSAGE', "Refund Initiated for the Refund ID: " . $curlResponseJsonDecode['refund_id']);
                                                    $payment->save();
                                                    $paymentCollection = $arOrder->getPaymentCollection();
                                                    $totalRefunded = null;
                                                    foreach ($paymentCollection as $pay => $value) {
                                                        if (($value->getId() != $GeneralPayID) && ($value->isPaid() == "Y")) {
                                                            $totalRefunded += $value->getSum();
                                                        }
                                                    }
                                                    foreach ($paymentCollection as $pay => $value) {
                                                        if (($value->getId() == $GeneralPayID)) {
                                                            $value->setField('PS_SUM', $SumAmount + $totalRefunded);
                                                            $value->setPaid('Y');
                                                            $value->save();
                                                        }
                                                    }

                                                    $data = [
                                                        'refund_id' => $curlResponseJsonDecode['refund_id'],
                                                        'merchant_refund_id' => $merchant_refund_reference,
                                                        'order_id' => $arOrder->getId(),
                                                        'payment_id' => $GeneralPayID,
                                                        'refund_payment_id' => $payment->getId(),
                                                        'transaction_id' => $transaction_id,
                                                        'refund_status' => $refundStatus,
                                                        'refund_amount' => $refund_amount,

                                                    ];
                                                    $sqlresponse = $zDataHelper->insertIntoRefundDataBaseTable($data);
                                                    return new EventResult(EventResult::ERROR, new ResultError(loc::getMessage('SALE_ZP_REFUND_CREATE'), 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
                                                    // $arOrder->save();

                                                } catch (ArgumentOutOfRangeException | ObjectNotFoundException | Exception $e) {
                                                    return new EventResult(EventResult::ERROR, new ResultError($e->getMessage(), 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');

                                                }

                                                break;
                                            }

                                        }
                                    } else {
                                        $errorMessage = loc::getMessage('SALE_ZP_REJECT');
                                        goto ZError;
                                    }
                                } else {
                                    $errorMessage = loc::getMessage('SALE_ZP_REFUND_ERROR');
                                    goto ZError;
                                }
                            } else {
                                $errorMessage = Loc::getMessage('SALE_ZP_NOT_ALLOWED');
                                goto ZError;
                            }
                        } else {
                            $errorMessage = Loc::getMessage('SALE_ZP_NOT_ALLOWED');
                            goto ZError;
                        }
                    } else {
                        ZError:
                        if (!isset($_POST['merchant_order_reference'])) {
                            return new EventResult(EventResult::ERROR, new ResultError($errorMessage, 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
                        }
                    }
                    if (!$arOrder->isNew()) {
                        $orderStatus = $arOrder->getField("STATUS_ID");
                        if ($arOrder->isCanceled() && ($orderStatus != "CN")) {
                            $arOrder->setField("STATUS_ID", "CN");
                            $event->addResult(new EventResult(
                                EventResult::SUCCESS,
                                array(
                                    "RESULT" => $arOrder,
                                )
                            ));
                        }
                        return;
                    }
                }
            }

            if (($_POST['action'] == 'updatePaymentStatus')  ) {
                $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, true, DataHelper::PAYMENT_SYSTEM_ID, $payID);
                $merchantData = null;
                $transactionDetails = $zDataHelper->getTotalRowData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_Merchant_Order_Ref, $arOrder->getId());


                if (isset($queryResult) && isset($transactionDetails)) {

                    AddMessage2Log(" Inside Query");
                    $queryResultJsonDecode = json_decode($queryResult['Config'], true);
//                    $payID = $queryResultJsonDecode['setting']['pay_id'];
                    $localPayId = $arOrder->getPaySystemIdList();
                    if ((($orderValues['PAY_SYSTEM_ID'] == $payID) && $_POST['data']['ORDER_STATUS_ID_0'] == "N")  || (($orderValues['PAY_SYSTEM_ID'] == $payID) && ($orderValues['DEDUCTED'] == "N" )) || ($_POST['method'] == 'cancel') || ($orderValues['PAY_SYSTEM_ID'] == $payID && $transactionDetails['payment_id'] != $_POST['paymentId'] )) {
                        $errorMessage = Loc::getMessage('SALE_ZP_NOT_ALLOWED');
                        return new EventResult(EventResult::ERROR, new ResultError($errorMessage, 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
                    }
                }
            }

            if ($_POST['action'] == 'saveStatus') {
                $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, true, DataHelper::PAYMENT_SYSTEM_ID, $payID);
                $merchantData = null;
                if (isset($queryResult)) {
                    // AddMessage2Log(" Inside Query");
                    $queryResultJsonDecode = json_decode($queryResult['Config'], true);
                 //   $payID = $queryResultJsonDecode['setting']['pay_id'];
                    $localPayId = $arOrder->getPaySystemIdList();
                    if ((($orderValues['PAY_SYSTEM_ID'] == $payID) && $_POST['statusId'] == "P" && $orderValues['PAYED'] == 'N')) {
                        $errorMessage = Loc::getMessage('SALE_ZP_NOT_ALLOWED');
                        return new EventResult(EventResult::ERROR, new ResultError($errorMessage, 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
                    }
                }
            }

            //Set Shipment Date from Shipment Panel
            if ($_POST['action'] == "updateShipmentStatus") {
                $transactionDetails = $zDataHelper->getTotalRowData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_Merchant_Order_Ref, $arOrder->getId());
                if (!empty($transactionDetails)) {
                    //    AddMessage2Log ( " Inside Transaction"  );
                    $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, true, DataHelper::PAYMENT_SYSTEM_ID, $payID);
                    $merchantData = null;
                    if (isset($queryResult) && $queryResult != null) {
                        $queryResultJsonDecode = json_decode($queryResult['config'], true);
                       // $payID = $queryResultJsonDecode['setting']['pay_id'];
                        $consumerKey = "PAYSYSTEM_" . $payID;
                        $merchantData = array(
                            'merchant_key' => BusinessValue::get(DataHelper::ZOODPAY_USER, $consumerKey),
                            'merchant_secret' => BusinessValue::get(DataHelper::ZOODPAY_PWD, $consumerKey),
                            'merchant_salt' => BusinessValue::get(DataHelper::ZOODPAY_SALT, $consumerKey),
                            'API_URL' => BusinessValue::get(DataHelper::ZOODPAY_API_URL, $consumerKey),
                            'API_Ver' => BusinessValue::get(DataHelper::ZOODPAY_API_VER, $consumerKey),
                            'SITE_ID' => BusinessValue::get(DataHelper::ZOODPAY_SITE_ID, $consumerKey),
                            'PAID_STATUS' => BusinessValue::get(DataHelper::ZP_PAID_STATUS, $consumerKey),
                            'FAILED_STATUS' => BusinessValue::get(DataHelper::ZP_FAILED_STATUS, $consumerKey),
                            'DELIVERED_STATUS' => BusinessValue::get(DataHelper::ZP_DELIVERED_STATUS, $consumerKey),
                        );
                        if (($arOrder->isPaid() == "Y") && ((($_POST['status'] == $merchantData['DELIVERED_STATUS'])) || ($_POST['field'] == "DEDUCTED" && $_POST['status'] == "Y"))) {
                            $apiUrl = $merchantData['API_URL'] . $merchantData['API_Ver'] . $zDataHelper::API_CreateTransaction . '/' . $transactionDetails['transaction_id'] . $zDataHelper::API_Delivery;
                            $data = [
                                "delivered_at" => date("Y-m-d H:i:s", time())
                                //   "final_capture_amount" => $arOrder->getSumPaid(),
                            ];
                            $curlResponse = $zDataHelper->curlPUT($merchantData['merchant_key'], $merchantData['merchant_secret'], $data, $apiUrl);
                            // ACK request accepted by ZoodPay
                            if (isset($curlResponse)) {
                                if ($curlResponse['statusCode'] == 200) {
                                    $curlResponseArray = json_decode($curlResponse['response'], true);
                                    $delivered_at = $curlResponseArray['delivered_at'];
                                    $final_capture_amount = $curlResponseArray['final_capture_amount'];
                                    $original_amount = $curlResponseArray['original_amount'];
                                    $status = $curlResponseArray['status'];
                                    $transaction_id = $curlResponseArray['transaction_id'];
                                } else {
                                    $curlResponseArray = json_decode($curlResponse['response'], true);
                                    $errorMessage = loc::getMessage('SALE_ZP_DELIVERY_ERROR') . $curlResponseArray['message'];
                                    return new EventResult(EventResult::ERROR, new ResultError($errorMessage, 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
                                }
                            }
                        } else {
                            return new EventResult(EventResult::ERROR, new ResultError($errorMessage, 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
                        }
                    }
                    else {
                        return new EventResult(EventResult::ERROR, new ResultError($errorMessage, 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
                    }
                }
            }



            if((($_POST['soa-action'] == "saveOrderAjax") || ($_POST['action'] == "saveOrderAjax")) && ($_POST['location_type'] == "code") ) {

                $errMsg= "";
                $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, true, DataHelper::PAYMENT_SYSTEM_ID, $payID);
                $merchantData = null;
                if (isset($queryResult)) {
                    // AddMessage2Log(" Inside Query");
                    $queryResultJsonDecode = json_decode($queryResult['Config'], true);
                   // $payID = $queryResultJsonDecode['setting']['pay_id'];

                    if (($orderValues['PAY_SYSTEM_ID'] == $payID) && ($orderValues['DEDUCTED'] == "N")  && ($orderValues['CANCELED'] == "N") && ($orderValues['PAYED'] == "N") && ($orderValues['STATUS_ID'] == "N") ) {

                        if( $_POST['ORDER_PROP_1'] == null || $_POST['ORDER_PROP_1'] == "" ){
                            $errMsg .= Loc::getMessage("SALE_ZP_NAME_ERROR") ."</br>";
                        }
                        if(  strlen($_POST['ORDER_PROP_1']) > 80 ){
                            $errMsg .= Loc::getMessage("SALE_ZP_NAME_L_ERROR") ."</br>";
                        }


                        if( $_POST['ORDER_PROP_2'] == null || $_POST['ORDER_PROP_2'] == "" ){
                            $errMsg .= Loc::getMessage("SALE_ZP_EMAIL_ERROR") ."</br>";
                        }
                        if( $_POST['ORDER_PROP_3'] == null || $_POST['ORDER_PROP_3'] == "" ){
                            $errMsg .= Loc::getMessage("SALE_ZP_NO_PHONE_ERROR") ."</br>";
                        }

                        if( $_POST['ORDER_PROP_3'] != null || $_POST['ORDER_PROP_3'] != "" ){
                            if(!($zDataHelper->validate_phone_number($_POST['ORDER_PROP_3'] ))){
                                $errMsg .= Loc::getMessage("SALE_ZP_PHONE_ERROR") ."</br>";
                            }
                        }
                        if( $_POST['ORDER_PROP_4'] == null || $_POST['ORDER_PROP_4'] == "" ){
                            $errMsg .= Loc::getMessage("SALE_ZP_NO_ZIP_ERROR")."</br>";
                        }

                        if( $_POST['ORDER_PROP_4'] != null || $_POST['ORDER_PROP_4'] != "" ){
                            if (strlen($_POST['ORDER_PROP_4']) < 5){
                                $errMsg .= Loc::getMessage("SALE_ZP_ZIP_ERROR") ."</br>";
                            }
                        }

//                        if( $_POST['ORDER_PROP_5'] == null || $_POST['ORDER_PROP_5'] == "" ){
//                            $errMsg .= "Address City Can Not Be empty" ."</br>";
//                        }
//
//                        if( $_POST['ORDER_PROP_7'] == null || $_POST['ORDER_PROP_7'] == "" ){
//                            $errMsg .= "Address Can Not Be empty" ."</br>";
//                        }

                        if($errMsg != ""){
                            return new EventResult(EventResult::ERROR, new ResultError($errMsg, 'saveOrderAjax'), 'sale');
                        }
                    }
                }
            }



        } catch (Exception $e) {
            return new EventResult(EventResult::ERROR, new ResultError($e->getMessage(), 'SALE_ORDER_PAYMENT_RETURN_NO_SUPPORTED'), 'sale');
        }
    }


    /**
     * @param $order
     * @param $arUserResult
     * @param $request
     * @param $arParams
     * @param $arResult
     * @param $arDeliveryServiceAll
     * @param $arPaySystemServiceAll
     */
    function PaymentAvailability($order	, &$arUserResult, $request, &$arParams, &$arResult, &$arDeliveryServiceAll, &$arPaySystemServiceAll)
    {
        $zDataHelper = new DataHelper();
        $totalRowData = $zDataHelper->getTotalRowData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::PAYMENT_SYSTEM_ID, $arUserResult['PAY_SYSTEM_ID']);
         if ($totalRowData != false)
         {
             $zDataHelper->checkApiHealth($arUserResult['PAY_SYSTEM_ID']);
         }

    }

}