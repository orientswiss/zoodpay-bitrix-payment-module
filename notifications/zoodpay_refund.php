<?php

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Order;
use OS\Helper\DataHelper;
define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define("DisableEventsCheck", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
\CModule::IncludeModule('zoodpay.payment');

global $APPLICATION;
Bitrix\Main\Loader::includeModule("sale");


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?
$zDataHelper = new DataHelper();
$safePost = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
$request = json_decode(file_get_contents('php://input'), true);

if (!(isset($safePost['refund_id']))) {
    $safePost = $request;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($safePost["refund_id"]) && !empty($safePost["signature"]) && !empty($safePost["refund"]['transaction_id'])) {

    $updated = false;
    $arFields = null;
    $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, false, '', '');
    $merchantData = null;
    $paymentRefund = null;
    $payed = "N";
    if (isset($queryResult)) {

        $queryResultJsonDecode = json_decode($queryResult['Config'], true);
        $payID = $queryResultJsonDecode['setting']['pay_id'];
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


        $merchant_refund_reference = $safePost['refund']["merchant_refund_reference"];

        $LocalTransaction = $zDataHelper->getTotalRowData($zDataHelper::ZOODPAY_REFUNDS_TABLE, $zDataHelper::ZOODPAY_merchant_refund_id, $merchant_refund_reference);

        if (isset($LocalTransaction)) {
            if ($LocalTransaction['refund_status'] != "Approved" && $LocalTransaction['refund_status'] != "Declined") {

                $localString = implode("|", array($LocalTransaction['merchant_refund_id'], round(floatval(-1 * $LocalTransaction['refund_amount']), 2), $safePost['refund']["status"], $merchantData['merchant_key'], $LocalTransaction['refund_id'], $merchantData['merchant_salt']));
                $localSignature = hash('sha512', $localString);

                if ($localSignature == $safePost["signature"]) {

                    $orderId = intval($LocalTransaction['order_id']);
                    $paymentRefund = intval($LocalTransaction['refund_payment_id']);

                    try {
                        /** @var Order $arOrder */
                        $order = Order::load($orderId);
                        $arPaymentsCollection = $order->loadPaymentCollection();
                        $currentPaymentOrder = $arPaymentsCollection->current();


                        // Create the Logic of refund Call back

                        $arOrder = CSaleOrder::GetByID(intval($LocalTransaction['order_id']));


                        switch ($safePost['refund']["status"]) {
                            case  "Approved" :
                            {


                                $arFields = array(

                                    "PS_STATUS_CODE" => $merchantData['REFUND_APPROVED_STATUS'],
                                    "PS_STATUS_DESCRIPTION" => $safePost['refund']["status"],

                                );

                                $order->setField("STATUS_ID", $merchantData['REFUND_APPROVED_STATUS']);
                                do {

                                    if ($paymentRefund == $currentPaymentOrder->getId()) {
                                        $updated = true;
                                        $currentPaymentOrder->setPaid("Y");
                                        $currentPaymentOrder->setField('PAID', "Y");
                                        $currentPaymentOrder->setReturn("P");
                                        $currentPaymentOrder->setField('PS_STATUS_CODE', $merchantData['REFUND_APPROVED_STATUS']);
                                        $currentPaymentOrder->setField('PS_STATUS_MESSAGE', "Refund Approved for the Refund ID: " . $safePost['refund_id']);
                                        $payed = "Y";
                                        $currentPaymentOrder->save();
                                    }

                                } while ($currentPaymentOrder = $arPaymentsCollection->next());


                                break;
                            }
                            case  "Declined" :
                            {


                                $arFields = array(

                                    "PS_STATUS_CODE" => $merchantData['REFUND_DECLINED_STATUS'],
                                    "PS_STATUS_DESCRIPTION" => $safePost['refund']["status"],

                                );
                                $order->setField("STATUS_ID", $merchantData['REFUND_DECLINED_STATUS']);

                                do {

                                    if ($paymentRefund == $currentPaymentOrder->getId()) {
                                        $updated = true;
                                        $currentPaymentOrder->setPaid("N");
                                        $currentPaymentOrder->setField('PAID', "N");
                                        $currentPaymentOrder->setReturn("P");
                                        $currentPaymentOrder->setField('PS_STATUS_CODE', $merchantData['REFUND_DECLINED_STATUS']);
                                        $currentPaymentOrder->setField('PS_STATUS_MESSAGE', "Refund Declined for the Refund ID: " . $safePost['refund_id']." Reason: " .$safePost['refund']["declined_reason"]);

                                        $currentPaymentOrder->save();
                                    }

                                } while ($currentPaymentOrder = $arPaymentsCollection->next());


                                break;


                            }


                        }


                        if ($updated) {
                            (new CSaleOrder)->Update($arOrder["ID"], $arFields);
                            $zDataHelper->updateDataBaseData($zDataHelper::ZOODPAY_REFUNDS_TABLE, $zDataHelper::ZOODPAY_refund_status, $safePost['refund']["status"], $zDataHelper::ZOODPAY_refund_id, $safePost["refund_id"]);
                            $zDataHelper->updateDataBaseData("b_sale_order_payment", "PAID", $payed, "ID", $paymentRefund);
                            $order->save();
                        }


                    } catch (ArgumentOutOfRangeException | ObjectNotFoundException | Exception $e) {
                        echo $e->getMessage();
                    }


                }
            }


        }


    }


}
?>