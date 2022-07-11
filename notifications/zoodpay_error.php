<?php

use Bitrix\Main\Application;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Order;
use OS\Helper\DataHelper;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

global $APPLICATION;
define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define("DisableEventsCheck", true);
require($_SERVER["DOCUMENT_ROOT"]  . "/bitrix/modules/main/include/prolog_before.php");
\CModule::IncludeModule('zoodpay.payment');
\Bitrix\Main\Loader::includeModule("sale");

$safePost = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($safePost["merchant_order_reference"]) && !empty($safePost["transaction_id"]) && !empty($safePost["amount"]) && !empty($safePost["status"]) && !empty($safePost["signature"])) {
    $zDataHelper = new DataHelper();

    $orderId = (int)$safePost["merchant_order_reference"];
    $LocalTransaction = $zDataHelper->getTotalRowData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_Merchant_Order_Ref, $orderId);
    if(isset($LocalTransaction))
    {
        $payID = $LocalTransaction['payment_system_id'];
        $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, true, $zDataHelper::PAYMENT_SYSTEM_ID, $payID);
        $merchantData = null;
        if (isset($queryResult)) {
            $queryResultJsonDecode = json_decode($queryResult['config'], true);
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
                'CANCELLED_STATUS' => BusinessValue::get(DataHelper::ZP_CANCELLED_STATUS, $consumerKey),
                'ZOODPAY_NOTIFY_URL_FAILED' => BusinessValue::get(DataHelper::ZOODPAY_NOTIFY_URL_FAILED, $consumerKey),

            );

        }


        $localString = implode("|", array($queryResultJsonDecode['setting']['country_code'], $LocalTransaction['currency'], number_format(floatval($LocalTransaction['amount']), 2, '.', ''), $LocalTransaction['merchant_order_reference'], $merchantData['merchant_key'], $LocalTransaction['transaction_id'], $merchantData['merchant_salt']));
        $localSignature = hash('sha512', $localString);
        if ($localSignature == $safePost["signature"]) {
            /** @var Order $arOrder */
            // $order= \Bitrix\Sale\Order::load($orderId);
            $arOrder = CSaleOrder::GetByID(intval($orderId));
            $val = "N";
            $arFields = null;
            (new CSalePaySystemAction)->InitParamArrays($arOrder, $arOrder["ID"]);
            switch ($safePost["status"]) {

                case  "Failed" :
                {

                    $arFields = array(
                        "PS_STATUS" => ("N"),
                        "STATUS_ID" => $merchantData['FAILED_STATUS'],
                        "PS_STATUS_CODE" => $merchantData['FAILED_STATUS'],
                        "PS_STATUS_DESCRIPTION" => $safePost["status"],
                        "PS_STATUS_MESSAGE" => ("The ZoodPay ID for this transaction: " . $safePost["transaction_id"] . ", and transaction is Failed due to " . $safePost['errorMessage'] . ' ' . $safePost["created_at"]),
                        "PS_SUM" => 0,
                        "PS_CURRENCY" => $LocalTransaction['currency'],
                        "PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
                        "USER_ID" => $arOrder["USER_ID"]
                    );

                    break;
                }

                case  "Cancelled" :
                {
                    $arFields = array(
                        "PS_STATUS" => ("N"),
                        "STATUS_ID" => $merchantData['CANCELLED_STATUS'],
                        "PS_STATUS_CODE" => $merchantData['CANCELLED_STATUS'],
                        "CANCELED" => "Y",
                        "PS_STATUS_DESCRIPTION" => $safePost["status"],
                        "PS_STATUS_MESSAGE" => ("The ZoodPay ID for this transaction: " . $safePost["transaction_id"] . ", and transaction is Cancelled due to " . $safePost['errorMessage'] . ' ' . $safePost["created_at"]),
                        "PS_SUM" => 0,
                        "PS_CURRENCY" => $LocalTransaction['currency'],
                        "PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
                        "USER_ID" => $arOrder["USER_ID"]
                    );


                    break;
                }


            }
            (new CSaleOrder)->PayOrder($arOrder["ID"], $val);
            (new CSaleOrder)->Update($arOrder["ID"], $arFields);
            $zDataHelper->updateDataBaseData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_status, $safePost["status"], $zDataHelper::ZOODPAY_TRANSACTIONS_id, $safePost["transaction_id"]);

            LocalRedirect($merchantData['ZOODPAY_NOTIFY_URL_FAILED'], true, 301);
        }

    }



}
?>