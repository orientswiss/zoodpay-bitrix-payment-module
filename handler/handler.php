<?php

/**
 * Description: ZoodPay Payment Module for Bitrix
 * Author: mintali
 * Email : mohammadali.namazi@zoodpay.com
 * Date: 2021-05-17, Mon, 8:16
 * File: handler
 * Path: handler/handler.php
 * Line: 64
 */

namespace Sale\Handlers\PaySystem;

use Bitrix\Main,
    Bitrix\Main\ModuleManager,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Main\Localization\Loc,
    Bitrix\Sale,
    Bitrix\Sale\PaySystem,
    Bitrix\Main\Request,
    Bitrix\Sale\Payment,
    Bitrix\Main\Diag\Debug,
    Bitrix\Sale\PaySystem\ServiceResult,
    Bitrix\Sale\PaymentCollection,
    Bitrix\Sale\PriceMaths,
    Bitrix\Main\Error,
    Bitrix\Sale\BasketItem,
    Bitrix\Sale\BusinessValue,
    Bitrix\Main\Application,
    Bitrix\Sale\PaySystem\Service,
    CModule,
    CSaleLocation,
    Bitrix\Main\Event,
    Bitrix\Main\EventManager,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\Type\Date,
    Bitrix\Sale\Order,
    Bitrix\Sale\Internals\Catalog,
    Bitrix\Sale\Registry,
    Bitrix\Sale\PaySystem\BaseServiceHandler,
    CSaleOrder,
    CUser,
    OS\Helper\DataHelper,
    OS\ZEventHandler\EHandler,
    Bitrix\Main\ArgumentNullException,
    Bitrix\Main\NotImplementedException,
    Bitrix\Sale\Shipment,
    Exception;


CModule::IncludeModule('zoodpay.payment');
Loc::loadMessages(__FILE__);

//define ( "LOG_FILENAME" , $_SERVER[ "DOCUMENT_ROOT" ]."/bitrix/php_interface/include/sale_payment/zoodpay/zoodpay1.log" );


/**
 * Class ZoodPayHandler
 * @package Sale\Handlers\PaySystem
 */
class ZoodPayHandler
    extends PaySystem\BaseServiceHandler
    implements PaySystem\IPrePayable, PaySystem\IRefundExtended
{
    const DELIMITER_PAYMENT_ID = ':';

    private $prePaymentSetting = array();
    private $orderPaymentSetting = array();

    public static function getPriceRange(Payment $payment, $paySystemId)
    {
        $result = array();

        $classes = array(
            '\Bitrix\Sale\Services\PaySystem\Restrictions\PercentPrice',
            '\Bitrix\Sale\Services\PaySystem\Restrictions\Price'
        );

        $params = array(
            'select' => array('CLASS_NAME', 'PARAMS'),
            'filter' => array(
                'SERVICE_ID' => $paySystemId,
                '=CLASS_NAME' => $classes
            )
        );

        $dbRes = Manager::getList($params);
        while ($data = $dbRes->fetch()) {
            $range = $data['CLASS_NAME']::getRange($payment, $data['PARAMS']);

            if (!$result['MAX'] || $range['MAX'] < $result['MAX'])
                $result['MAX'] = $range['MAX'];

            if (!$result['MIN'] || $range['MIN'] > $result['MIN'])
                $result['MIN'] = $range['MIN'];
        }

        return $result;
    }


    /**
     * @return array
     */
    static public function getIndicativeFields()
    {
        return array('mc_gross', 'mc_currency');
    }

    /**
     * @param Request $request
     * @param $paySystemId
     * @return bool
     */
    protected static function isMyResponseExtended(Request $request, $paySystemId)
    {
        $data = PaySystem\Manager::getById($paySystemId);


        return false;

    }

    /**
     * @param Request $request
     * @return mixed
     */
    private static function getRegistryType(Request $request)
    {
        $paymentId = null;

    }

    /**
     * @return array -- Available Currency for ZoodPay
     */
    public function getCurrencyList()
    {
//        AddMessage2Log ( "Currency Event Occurred"  );
        return array("USD", "KWD", "KZT", "IQD", "JOD", "SAR", "UZS");
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return PaySystem\ServiceResult
     */
    public function processRequest(Payment $payment, Request $request)
    {
        /** @var PaySystem\ServiceResult $serviceResult */
        $serviceResult = new PaySystem\ServiceResult();

        $instance = Application::getInstance();
        $context = $instance->getContext();
        $request = $context->getRequest();
        $server = $context->getServer();


        return $serviceResult;
    }

    /**
     * @param Payment $payment
     * @param Request|null $request
     * @return PaySystem\ServiceResult
     * @throws Exception
     */
    public function initiatePay(Payment $payment, Request $request = null)
    {
        try {
            $zDataHelper = new DataHelper();
            $paymentSystemID = $payment->getPaymentSystemId();
            $result = new PaySystem\ServiceResult();
            $order = $payment->getOrder();
            $payment->setPaid('N');
            $orderId = $order->getId();

            $transactionDetails = $zDataHelper->getTotalRowData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_Merchant_Order_Ref, $orderId);
            if (!empty($transactionDetails)) {
                if($transactionDetails['url'] != "" || $transactionDetails['url'] != null ){
                    date_default_timezone_set('UTC');
                    $expiryTime = strtotime($transactionDetails['expiry_time']);
                    $time= strtotime(date('c', strtotime(date('Y-m-d\TH:i:s\Z',time()))));
                    if(((($expiryTime - $time)) >= 0) && ($transactionDetails['status'] == "Pending Payment"))
                    {
                        $this->orderPaymentSetting['url'] = $transactionDetails['url'];;
                        return $this->showTemplate($payment, 'orderpay');
                    }
                    $result->addError(new Error(Loc::getMessage('SALE_ZP_CLOSED_TRAN')));
                    // header($transactionDetails['url'], true, 301);
                }
                else{
                    $result->addError(new Error(Loc::getMessage('SALE_ZP_REJECT')));

                }
                return $result;

            }

            $this->initPrePayment($payment, $request);

            $this->prePaymentSetting['TDM'] = $this->getTransactionDataModel($payment);

            if (isset($_POST['selected_service'])) {
                $this->prePaymentSetting['TDM']['order']['service_code'] = $_POST['selected_service'];
                $curlResponse = $zDataHelper->curlPost($this->prePaymentSetting['merchant_key'], $this->prePaymentSetting['merchant_secret'], $this->prePaymentSetting['TDM'], $this->prePaymentSetting['CreateTrUrl']);
                if ($curlResponse['statusCode'] == 201) {
                    $curlResponseJsonDecode = json_decode($curlResponse['response'], true);
                    $this->prePaymentSetting['callBackUrl'] = $curlResponseJsonDecode['payment_url'];
                    $data = ['amount' => $this->prePaymentSetting['TDM']['order']['amount'],
                        'currency' => $order->getCurrency(),
                        'merchant_order_reference' => strval($this->prePaymentSetting['TDM']['order']['merchant_reference_no']),
                        'transaction_id' => $curlResponseJsonDecode['transaction_id'],
                        'status' => $zDataHelper::Pending_Payment,
                        'selected_service' => $this->prePaymentSetting['TDM']['order']['service_code'],
                        'payment_id' => $payment->getId(),
                        'refund_id' => '',
                        'url' => $curlResponseJsonDecode['payment_url'],
                        'expiry_time' => $curlResponseJsonDecode['expiry_time'],
                        'created_at' => date('m/d/Y h:i:s a', time())
                    ];
                    $sqlresponse = $zDataHelper->insertIntoTranDataBaseTable($data);
                    $payment->setReturn("P");
                    $order->save();
                    $payment->save();
                    LocalRedirect($curlResponseJsonDecode['payment_url'], true, 301);

                } else {
                    $message = Loc::getMessage('ZoodPay_Error');
                    if ($curlResponse['statusCode'] == 400) {

                        $errorResponse = json_decode($curlResponse['response'], true);
                        $message = $errorResponse['message'] . "<br>";
                        if (isset($errorResponse['details'])) {
                            foreach ($errorResponse['details'] as $iValue) {
                                $message .= $iValue['field'] . ": " . $iValue['error'] . "<br>";
                            }
                        }
                    }

                    echo "<pre  class='alert-danger'>" . $message;
                    echo "</pre>";
                    PaySystem\ErrorLog::add(array(
                        'ACTION' => 'initiatePay',
                        'MESSAGE' => $message
                    ));
                    $result->addError(new Error($message));
                    return $result;
                }
            } else {

                return $this->showTemplate($payment, 'template');

            }


        } catch (ArgumentNullException | Main\ArgumentException | Main\SystemException | Exception $e) {
            throw new Exception($e->getMessage());
        }

        $error = ' init payment error';
        echo $error;
        PaySystem\ErrorLog::add(array(
            'ACTION' => 'initiatePay',
            'MESSAGE' => $error
        ));


        $result->addError(new Error($error));
        return $result;


    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return bool
     */
    public function initPrePayment(Payment $payment = null, Request $request = null)
    {

        if ($payment !== null) {
            $this->prePaymentSetting = array(
                'merchant_key' => $this->getBusinessValue($payment, 'ZOODPAY_USER'),
                'merchant_secret' => $this->getBusinessValue($payment, 'ZOODPAY_PWD'),
                'merchant_salt' => $this->getBusinessValue($payment, 'ZOODPAY_SALT'),
                'CURRENCY' => $this->getBusinessValue($payment, 'PAYMENT_CURRENCY'),
                'API_URL' => $this->getBusinessValue($payment, 'ZOODPAY_API_URL'),
                'API_Ver' => $this->getBusinessValue($payment, 'ZOODPAY_API_VER'),
                'NOTIFY_URL' => $this->getBusinessValue($payment, 'ZOODPAY_NOTIFY_URL'),

                'SITE_ID' => $this->getBusinessValue($payment, 'ZOODPAY_SITE_ID'),
                'ZOODPAY_OC' => $this->getBusinessValue($payment, 'ZOODPAY_OC'),
                'ZOODPAY_LC' => $this->getBusinessValue($payment, 'ZOODPAY_LC'),

                'ENCODING' => $this->service->getField('ENCODING')
            );


            $this->prePaymentSetting['CreateTrUrl'] = $this->prePaymentSetting['API_URL'] . $this->prePaymentSetting['API_Ver'] . DataHelper::API_CreateTransaction;


            $zDataHelper = new DataHelper();
            $configResponse = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, false, '', '');
            $config = (json_decode($configResponse['Config'], true)['config']);
            $paymentSetting = (json_decode($configResponse['Config'], true)['setting']);

            $this->prePaymentSetting['country_code'] = $paymentSetting['country_code'];
            $this->prePaymentSetting['location_id'] = $paymentSetting['location_id'];


            $serviceAvailability = false;


            $order = $payment->getOrder();

            $quoteTotal = $order->getPrice();

            $availableServiceResult = null;
            $k = 0;
            $lang = Application::getInstance()->getContext()->getLanguage();
            foreach ($config as $i => $iValue) {

                if (($quoteTotal >= $iValue['min_limit']) && ($quoteTotal <= $iValue['max_limit'])) {
                    $serviceName = $iValue['service_name'];
                    $serviceCode = $iValue['service_code'];
                    $imgSrc = Application::getPersonalRoot()."/modules/zoodpay.payment/handler/asset/img/".$serviceCode."_".$lang.".png";
                    if (isset($iValue['instalments'])) {

                        $monthlyPayment = round($quoteTotal / $iValue['instalments'], 2) . ' ' . $this->prePaymentSetting['CURRENCY'];

                        $availableServiceResult[$serviceCode] = [
                            "service_code" => $serviceCode,
                            "service_text" => "$serviceName ".Loc::getMessage('text_of') . "$monthlyPayment "  ,
                            "service_type" => $serviceName,
                            "service_installment_bool" => true,
                            "service_installment" => $iValue['instalments'],
                            "service_description" => $iValue['description'],
                            "img_src" => $imgSrc
                        ];
                        $k++;
                        $serviceAvailability = true;
                    } else {

                        $availableServiceResult[$serviceCode] = [
                            "service_code" => $serviceCode,
                            "service_installment_bool" => false,
                            "service_text" => "$serviceName " ,
                            "service_type" => $serviceName,
                            "service_description" => $iValue['description'],
                            "img_src" => $imgSrc
                        ];
                        $k++;
                        $serviceAvailability = true;
                    }


                }
            }
            if (count($availableServiceResult) == 1) {
                $this->prePaymentSetting['only_service'] = true;
                $this->prePaymentSetting['selected_service'] = $availableServiceResult[0]['service_code'];
            } else {
                $this->prePaymentSetting['only_service'] = false;
                $this->prePaymentSetting['selected_service'] = '';
            }


            if ($serviceAvailability) {
                foreach ($availableServiceResult as $keyS => $valueS) {
                    $this->prePaymentSetting['services'][$keyS] = $valueS;
                }
            }

        }


        return true;
    }

    /**
     * @param Payment $payment
     * @return array ZoodPay Transaction Data Model
     * @throws Exception
     */
    public function getTransactionDataModel(Payment $payment)
    {
        $zDataHelper = new DataHelper();
        $dataModel = null;
        $payerPhone = "";
        $payerEmail = "";
        $payerProfileName = "";
        $payerZip = "";
        $payerLocation = "";
        $payerAddress = "";
        $payerName = "";
        $payerLName = "";
        $payerCity = "";
        $payerDOB = "";
        $shippingConfig = "";
        global $APPLICATION;
        try {
            $order = $payment->getOrder();
            $paymentCollection = $payment->getCollection();
            if ($this->prePaymentSetting['ZOODPAY_OC'] == "OC") {
                //Added For User Details
                if ($paymentCollection) {
                    $orderProperty = $order->getPropertyCollection();
                    $payerNameProb = $orderProperty->getPayerName();
                    $payerAddressProb = $orderProperty->getAddress();
                    $payerLocationProb = $orderProperty->getDeliveryLocation();
                    $payerZipProb = $orderProperty->getDeliveryLocationZip();
                    $payerProfileNameProb = $orderProperty->getProfileName();
                    $payerEmailProb = $orderProperty->getUserEmail();
                    $payerPhoneProb = $orderProperty->getPhone();

                    if ($payerPhoneProb) {
                        $payerPhone = $payerPhoneProb->getValue();
                    }
                    if ($payerEmailProb) {
                        $payerEmail = $payerEmailProb->getValue();
                    }
                    if ($payerProfileNameProb) {
                        $payerProfileName = $payerProfileNameProb->getValue();
                    }
                    if ($payerZipProb) {
                        $payerZip = $payerZipProb->getValue();
                    }
                    if ($payerLocationProb) {
                        $payerLocation = $payerLocationProb->getValue();
                    }
                    if ($payerAddressProb) {
                        $payerAddress = $payerAddressProb->getValue();
                    }
                    if ($payerNameProb) {
                        $payerName = $payerNameProb->getValue();
                    }
                    $payerCity = $payerLocation;

                }
            } else {
                $userID = $order->getUserId();
                $rsUser = CUser::GetByID($userID);
                $arUser = $rsUser->Fetch();
                $payerPhone = $arUser['PERSONAL_MOBILE'];
                $payerEmail = $arUser['EMAIL'];
                $payerProfileName = $arUser['NAME'] . $arUser['LAST_NAME'];
                $payerZip = $arUser['PERSONAL_ZIP'];
                $payerLocation = $arUser['PERSONAL_STATE'];
                $payerCity = $arUser['PERSONAL_CITY'];
                $payerAddress = implode(",", array($arUser['PERSONAL_STREET'], $arUser['PERSONAL_CITY'], $arUser['PERSONAL_STATE']));
                $payerName = $arUser['NAME'] . $arUser['LAST_NAME'];
                $payerDOB = date("Y-m-d", strtotime($arUser['PERSONAL_BIRTHDAY']));

            }

            $payerPhone = $this->clearSpecialChar($payerPhone);
            if ($payerZip == "") {
                $payerZip = "050000";
            }

            $shippingCollection = $order->getShipmentCollection();
            $delivery = null;
            $i = 0;
            $deliveryIdList = $order->getDeliveryIdList();

            foreach ($shippingCollection as $shipment) {
                $delivery[$i] = $shipment->getDelivery();

                $i++;
            }
            $shippingServiceCol = null;
            foreach ($delivery as $keyD => $valueD) {

                if ($deliveryIdList['0'] == $valueD->getId()) {
                    $shippingServiceCol = $valueD;
                    $shippingConfig = $shippingServiceCol->getConfig();
                }

            }


            $locationId = null;
            foreach ($deliveryIdList as $keyL => $valueL) {
                $locationId = $valueL;
            }

            //Change the pickup address to PICKUP for the field
            if ($payerAddress == "" || $payerAddress == null) {
                $payerAddress = $this->getWarehouseAddress($order->getId());
            }

            if ($payerAddress == "" || $payerAddress == null) {
                $payerAddress = "PICK-UP";
            }
            if (strlen($payerName) > 50) {
                $sNames = explode(" ", $payerName);
                if (isset($sNames[0]) && ($sNames[0] != "") ) {
                    $tempName = $sNames[0]." ".$sNames[1];
                    if (strlen($tempName) < 50) {
                        $payerName = $tempName;
                        if (isset($sNames[2])) {
                            $payerLName = $sNames[2];
                        }
                    } else {

                        if (strlen($sNames[0]) < 50) {
                            $payerName = $sNames[0];
                            $payerLName = $sNames[1];
                        } else {
                            $sNames = str_split($payerName, 40);
                            if (isset($sNames[0])) {
                                $payerName = $sNames[0];
                                $payerLName = $sNames[1];
                            }
                        }
                    }
                } else {
                    $sNames = str_split($payerName, 40);
                    if (isset($sNames[0])) {
                        $payerName = $sNames[0];
                        $payerLName = $sNames[1];

                    }
                }

            }


            /** @var  $Customer_Data --- Details for creating transaction */
            $Customer_Data = [
                'customer_dob' => $payerDOB,
                'customer_email' => $payerEmail,
                'customer_phone' => $payerPhone,
                //'customer_pid' => 0,
                'first_name' => $payerName,
                'last_name' => $payerLName

            ];


            /** @var  $countryCode
             *      Used for the Create transaction
             */

            $countryCode = $this->prePaymentSetting['country_code'];

            /** @var  $billing_Data -- Details for creating transaction */
            $billing_Data = [

                'address_line1' => $payerAddress,
                'address_line2' => 'null',
                'city' => $payerCity,
                'country_code' => $countryCode,
                'name' => $payerName,
                'phone_number' => $payerPhone,
                'state' => $payerCity,
                'zipcode' => $payerZip
            ];


            /** @var  $Shipping_Data -- Details for creating transaction */

            $Shipping_Data = [

                'address_line1' => $payerAddress,
                'address_line2' => 'null',
                'city' => $payerCity,
                'country_code' => $countryCode,
                'name' => $payerName,
                'phone_number' => $payerPhone,
                'state' => $payerCity,
                'zipcode' => $payerZip
            ];

            $shippingService = [

                "name" => $shippingServiceCol->getName(),
                "priority" => "null",
                "shipped_at" => "null",
                "tracking" => $shippingServiceCol->getTrackingClass()
            ];


            $orderString = implode("|", array($this->prePaymentSetting['merchant_key'], $order->getId(), $order->getPrice(), $this->prePaymentSetting['CURRENCY'], $this->prePaymentSetting['country_code'], $this->prePaymentSetting['merchant_salt']));


            /** @var  $orderSignature -- Create Signature Based on ZoodPay API DOC */

            $orderSignature = hash('sha512', $orderString);


            /** @var  $order_Data -- Details for creating transaction */


            $order_Data = [

                'amount' => $order->getPrice(),
                'currency' => $this->prePaymentSetting['CURRENCY'],
                'discount_amount' => $order->getDiscountPrice(),
                'lang' => $this->prePaymentSetting['ZOODPAY_LC'],
                'market_code' => $this->prePaymentSetting['country_code'],
                'merchant_reference_no' => (string)$order->getId(),
                'service_code' => '',
                'shipping_amount' => (floatval($shippingConfig['MAIN']['ITEMS']['PRICE']['VALUE'])),
                'signature' => $orderSignature,
                'tax_amount' => $order->getTaxPrice(),

            ];


            /** @var  $orderItemData -- Details for creating transaction */
            $orderItemData = [];

            /** @var BasketItem $basketItem */
            $basketItem = $order->getBasket()->getBasketItems();


            foreach ($basketItem as $item) {


                $orderItemData[] =

                    [
                        'categories' => [[$item->getField('CATALOG_XML_ID')]],


                        'currency_code' => $item->getCurrency(),
                        'discount_amount' => $item->getDiscountPrice(),
                        'name' => $item->getField('NAME'),
                        'price' => $item->getPrice(),
                        'quantity' => $item->getQuantity(),
                        'sku' => (string)$item->getId(),
                        'tax_amount' => $item->getVat()

                    ];
            }


            return [
                "billing" => $billing_Data,
                "customer" => $Customer_Data,
                "items" => $orderItemData,
                "order" => $order_Data,
                "shipping" => $Shipping_Data,
                "shipping_service" => $shippingService
            ];


        } catch (ArgumentNullException | Main\ArgumentException | Main\SystemException $e) {
            throw new Exception($e->getMessage());
        }


    }

    /**
     * @param $string
     * @return array|string|string[]|null
     */
    public function clearSpecialChar($string)
    {
        $string = str_replace(' ', '', $string); // Remove All Spaces.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', '', $string); // Remove multiple hyphens.
    }

    /**
     * In Case of Store/Warehouse pickup, pull the address of it.
     * @param $order_id
     * @return false|mixed False/Store Address
     * @throws Main\ArgumentException
     * @throws ArgumentNullException
     * @throws Main\Db\SqlQueryException
     */
    public function getWarehouseAddress($order_id)
    {
        // Loading the OrderId
        try {
            $order = Order::load($order_id);
        } catch (ArgumentNullException $e) {
            return false;
        } catch (NotImplementedException $e) {
            return false;
        }

        $store_id = null;
        foreach ($order->getShipmentCollection() as $s) {
            /** @var Shipment $s */
            $store_id = $s->getStoreId();
            if ($store_id) {
                break;
            }
        }
        if (!$store_id) {
            return false;
        }

        // Getting the Warehouse Id from address
        $connection = Application::getConnection();
        $sql = '
        SELECT ADDRESS 
        FROM b_catalog_store
        WHERE id = ' . (int)$store_id . '
    ';
        $store = $connection->query($sql)->fetch();
        if (!$store) {
            return false;
        }

        return $store['ADDRESS'];
    }

    /**
     * @param Payment $payment
     * @return array
     */
    public function getParamsBusValue(Payment $payment = null)
    {
        $params[0] = parent::getParamsBusValue($payment);
        $params [1] = $this->prePaymentSetting;
        $params [2] = $this->orderPaymentSetting;
        return $params;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPaymentIdFromRequest(Request $request)
    {
        $paymentId = null;
        if ($request->get('custom') !== null) {
            $paymentId = $request->get('custom');
        }

        if ($paymentId === null) {
            $paymentId = $request->get('cm');
        }

        $pos = mb_strpos($paymentId, static::DELIMITER_PAYMENT_ID);
        if ($pos !== false) {
            return mb_substr($paymentId, $pos + 1);
        }

        return $paymentId;
    }

    /**
     * @param PaySystem\ServiceResult $result
     * @param Request $request
     * @return mixed
     */
    public function sendResponse(PaySystem\ServiceResult $result, Request $request)
    {
        $data = $result->getData();

    }

    /**
     * @return array
     */
    public function getProps()
    {
        $data = array();

        return $data;
    }

    /**
     * @param array $orderData
     */
    public function payOrder($orderData = array())
    {
        $serviceResult = new PaySystem\ServiceResult();


    }

    /**
     * @param array $orderData
     * @return bool|string
     */
    public function BasketButtonAction($orderData = array())
    {
        global $APPLICATION;


        return false;
    }

    /**
     * @param array $orderData
     */
    public function setOrderConfig($orderData = array())
    {
        if ($orderData) {
            $this->prePaymentSetting = array_merge($this->prePaymentSetting, $orderData);
        }
    }


    public function getDescription()
    {
        $zDataHelper = new DataHelper();
        $zDataHelper->checkApiHealth();
      $genError = false;
        if ( ($_POST['ACTION_FILE'] == "zoodpay") && ($_POST['Update'] == "Y") )
        {
            $payName = $zDataHelper::paymentCode;
            $queryResult = $zDataHelper->getDataBaseData(DataHelper::b_sale_pay_system_action, DataHelper::PAY_SYSTEM_ID, true, DataHelper::ACTION_FILE, $payName);

            $apiStatusArray = $zDataHelper->getDataBaseData(DataHelper::b_sale_bizval, DataHelper::PROVIDER_VALUE, true, DataHelper::CODE_KEY, DataHelper::ZOODPAY_CHECK_HEALTHY);

            if ($apiStatusArray['PROVIDER_VALUE'] != "N") {
                if (isset($queryResult)) {
                    $payID = $queryResult['PAY_SYSTEM_ID'];
                    $consumerKey = "PAYSYSTEM_" . $payID;
                    $merchantData = array(
                        'merchantUserKey' => BusinessValue::get(DataHelper::ZOODPAY_USER, $consumerKey),
                        'merchantSecret' => BusinessValue::get(DataHelper::ZOODPAY_PWD, $consumerKey),
                        'SALT' => BusinessValue::get(DataHelper::ZOODPAY_SALT, $consumerKey),
                        'API_URL' => BusinessValue::get(DataHelper::ZOODPAY_API_URL, $consumerKey),
                        'API_Ver' => BusinessValue::get(DataHelper::ZOODPAY_API_VER, $consumerKey),
                        'SITE_ID' => BusinessValue::get(DataHelper::ZOODPAY_SITE_ID, $consumerKey),
                        'ZOODPAY_CC' => BusinessValue::get(DataHelper::ZOODPAY_CC, $consumerKey),
                        'ZOODPAY_LC' => BusinessValue::get(DataHelper::ZOODPAY_LC, $consumerKey),
                        'ZOODPAY_OC' => BusinessValue::get(DataHelper::ZOODPAY_OC, $consumerKey),

                    );
                        $locationArray = $zDataHelper->getDataBaseData(DataHelper::b_sale_loc_2site, DataHelper::LOCATION_ID, true, DataHelper::SITE_ID, $merchantData['SITE_ID']);
                        $locationID = $locationArray['LOCATION_ID'];
                        // Try to get the location from the CSaleLocation
                        //  $arLocs = CSaleLocation::GetByID($locationID, 'en');
                        $countryCode = $merchantData['ZOODPAY_CC'];
                        $fetchData = ["market_code" => "$countryCode"];
                        $apiUrl = $merchantData['API_URL'] . $merchantData['API_Ver'] . DataHelper::API_GetConfigurations;
                        $curlResponse = $zDataHelper->curlPost($merchantData['merchantUserKey'], $merchantData['merchantSecret'], $fetchData, $apiUrl);
                        $configFetched = false;
                        if (isset($curlResponse)) {
                            if ($curlResponse['statusCode'] == 200) {
                                $configFetched = true;
                                $resJson = str_replace(array("\r", "\n", "\t", '\r', '\n', '\t'), "", $curlResponse['response']); // Trim values
                                $jsonDecode = json_decode($resJson, true);
                                $minArray = [];
                                $maxArray = [];
                                foreach ($jsonDecode['configuration'] as $key => $value) {
                                    array_push($minArray, (int)$value['min_limit']);
                                    array_push($maxArray, (int)$value['max_limit']);
                                }
                                $res['config'] = $jsonDecode['configuration'];
                                $res['setting'] = ['country_code' => $countryCode, 'consumer_key' => $consumerKey, 'pay_id' => $payID];
                                $zDataHelper->deleteDatbaseTable(DataHelper::ZOODPAY_CONFIG_TABLE, false, '', '');
                                $zDataHelper->insertIntoConfigDataBaseTable(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, json_encode($res, JSON_UNESCAPED_UNICODE));
                                $zDataHelper->updateDataBaseData(DataHelper::b_sale_bizval, DataHelper::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_CONFIG_FETCHED'), DataHelper::CODE_KEY, DataHelper::ZOODPAY_CONFIG_STATUS);
                                $zDataHelper->updateDataBaseData(DataHelper::b_sale_bizval, DataHelper::PROVIDER_VALUE, "Y", DataHelper::CODE_KEY, DataHelper::ZOODPAY_CHECK_CONFIG);

                                $zDataHelper->replacePriceRange($payID, min($minArray), max($maxArray));
                                //AddMessage2Log (DataHelper::ZOODPAY_CONFIG_FETCHED_MESSAGE);
                            }
                        }
                        if(!$configFetched){
                                $zDataHelper->updateDataBaseData(DataHelper::b_sale_bizval, DataHelper::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_WRONG_CRED'), DataHelper::CODE_KEY, DataHelper::ZOODPAY_CONFIG_STATUS);
                                $zDataHelper->updateDataBaseData(DataHelper::b_sale_bizval, DataHelper::PROVIDER_VALUE, "N", DataHelper::CODE_KEY, DataHelper::ZOODPAY_CHECK_CONFIG);
                                $zDataHelper->replacePriceRange($payID, 0, 1);
                                $zDataHelper->deleteDatbaseTable(DataHelper::ZOODPAY_CONFIG_TABLE, false, '', '');
                        }


                }else {
                    $genError = true;
                }
            }else {
                $genError = true;
            }

            if($genError){
                    $zDataHelper->updateDataBaseData(DataHelper::b_sale_bizval, DataHelper::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_NO_VERIFY_CRED'), DataHelper::CODE_KEY, DataHelper::ZOODPAY_CONFIG_STATUS);
                    $zDataHelper->updateDataBaseData(DataHelper::b_sale_bizval, DataHelper::PROVIDER_VALUE, "N", DataHelper::CODE_KEY, DataHelper::ZOODPAY_CHECK_CONFIG);
            }

        }



   return parent::getDescription(); // Change the autogenerated stub
    }

    public function refund(Payment $payment, $refundableSum)
    {
        //Implement refund() method.
    }

    public function isRefundableExtended()
    {
        //  Implement isRefundableExtended() method.
    }

    /**
     * @return Service|null
     */
    public function getService(): Service
    {
        return $this->service;
    }

    /**
     * @return array
     */
    public function getPrePaymentSetting(): array
    {
        return $this->prePaymentSetting;
    }

    public function getHandlerType()
    {
        return parent::getHandlerType(); //  Change the autogenerated stub
    }

    /**
     * @param Service|null $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @param $lines
     * @return PaySystem\ServiceResult
     */
    protected function processSuccessAction(Payment $payment, Request $request, $lines)
    {
        $serviceResult = new PaySystem\ServiceResult();

        $keys = array();


        return $serviceResult;
    }

    /**
     * @param Request $request
     * @param Payment $payment
     * @return PaySystem\ServiceResult
     */
    protected function processVerifiedAction(Payment $payment, Request $request)
    {
        $serviceResult = new PaySystem\ServiceResult();


        return $serviceResult;
    }

    /**
     * @param Payment $payment
     * @param Request $request
     * @return string
     */
    protected function getPdtRequest(Payment $payment, Request $request)
    {
        $req = '';

        return $req;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getIpnRequest(Request $request)
    {
        $req = 'cmd=_notify-validate';

        foreach ($_POST as $key => $value) {
            $req .= '&' . $key . '=' . urlencode(stripslashes($value));
        }
        return $req;
    }

    /**
     * @return array
     */
    protected function getUrlList()
    {
        return null;
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    protected function isTestMode(Payment $payment = null)
    {
        return $this->getBusinessValue($payment, 'PS_IS_TEST') == 'Y';
    }

    /**
     * @param $data
     * @return array
     */
    protected function parsePrePaymentResult($data)
    {
        global $APPLICATION;
        //Implement parsePrePaymentResult
    }

    /**
     * @param Payment $payment
     * @return mixed|string
     */
    private function getReturnUrl(Payment $payment)
    {
        return $this->getBusinessValue($payment, 'ZOODPAY_RETURN') ?: $this->service->getContext()->getUrl();
    }



}
