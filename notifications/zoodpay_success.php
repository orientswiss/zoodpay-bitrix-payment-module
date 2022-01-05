<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Order;
use OS\Helper\DataHelper;
global $APPLICATION;
define("STOP_STATISTICS", true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define("DisableEventsCheck", true);



\CModule::IncludeModule('zoodpay.payment');

\CModule::IncludeModule('sale');

header('Content-type: application/json');
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?php
$safePost = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

$request = json_decode(file_get_contents('php://input'), true );
if (!(isset($safePost['transaction_id']))) {
    $safePost = $request;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($safePost["merchant_order_reference"]) && !empty($safePost["transaction_id"]) && !empty($safePost["amount"]) && !empty($safePost["status"]) && !empty($safePost["signature"])) {
    $zDataHelper = new DataHelper();
    $returnResult = "N";
    $arFields = null;
    $queryResult = $zDataHelper->getDataBaseData(DataHelper::ZOODPAY_CONFIG_TABLE, DataHelper::ZOODPAY_CONFIG_COLUMN, false, '', '');
    $merchantData = null;
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
            'PAID_STATUS' => BusinessValue::get(DataHelper::ZP_PAID_STATUS, $consumerKey),
            'FAILED_STATUS' => BusinessValue::get(DataHelper::ZP_FAILED_STATUS, $consumerKey),
            'DELIVERED_STATUS' => BusinessValue::get(DataHelper::ZP_DELIVERED_STATUS, $consumerKey),
            'CANCELLED_STATUS' => BusinessValue::get(DataHelper::ZP_CANCELLED_STATUS, $consumerKey),
            'ZOODPAY_NOTIFY_URL_PAID' => BusinessValue::get(DataHelper::ZOODPAY_NOTIFY_URL_PAID, $consumerKey)
        );

    }

    $orderId = (int)$safePost["merchant_order_reference"];
    $LocalTransaction = $zDataHelper->getTotalRowData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_Merchant_Order_Ref, $orderId);
    $localString = implode("|", array($queryResultJsonDecode['setting']['country_code'], $LocalTransaction['currency'], number_format(floatval($LocalTransaction['amount']), 2, '.', ''), $LocalTransaction['merchant_order_reference'], $merchantData['merchant_key'], $LocalTransaction['transaction_id'], $merchantData['merchant_salt']));
    $localSignature = hash('sha512', $localString);
    if ($localSignature == $safePost["signature"]) {
        /** @var Order $arOrder */
        //  $order= \Bitrix\Sale\Order::load($orderId);
        $arOrder = CSaleOrder::GetByID(intval($orderId));
        (new CSalePaySystemAction)->InitParamArrays($arOrder, $arOrder["ID"]);
        switch ($safePost["status"]) {
            case  "Paid" :
            {
                $arFields = array(
                    "PS_STATUS" => ("Y"),
                    "STATUS_ID" => $merchantData['PAID_STATUS'],
                    "PS_STATUS_CODE" => $merchantData['PAID_STATUS'],
                    "PS_STATUS_DESCRIPTION" => $safePost["status"],
                    "PS_STATUS_MESSAGE" => ("The ZoodPay ID for this transaction: " . $safePost["transaction_id"] . ", Time of this transaction: " . $safePost["created_at"]),
                    "PS_SUM" => $safePost["amount"],
                    "PS_CURRENCY" => $LocalTransaction['currency'],
                    "PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
                    "USER_ID" => $arOrder["USER_ID"]
                );

                $returnResult = "Y";
                break;
            }
            case  "Pending" :
            {
                $arFields = array(
                    "PS_STATUS" => ("N"),
                    "STATUS_ID" => $merchantData['FAILED_STATUS'],
                    "PS_STATUS_CODE" => $merchantData['FAILED_STATUS'],
                    "PS_STATUS_DESCRIPTION" => $safePost["status"],
                    "PS_STATUS_MESSAGE" => ("The ZoodPay ID for this transaction: " . $safePost["transaction_id"] . ", and transaction is Pending " . $safePost["created_at"]),
                    "PS_SUM" => 0,
                    "PS_CURRENCY" => $LocalTransaction['currency'],
                    "PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
                    "USER_ID" => $arOrder["USER_ID"]
                );
                break;
            }
        }

        try {
            (new CSaleOrder)->PayOrder($arOrder["ID"], $returnResult);
            (new CSaleOrder)->Update($arOrder["ID"], $arFields);
        } catch (ArgumentOutOfRangeException | Exception | ObjectNotFoundException $e) {
        }

        $zDataHelper->updateDataBaseData($zDataHelper::ZOODPAY_TRANSACTIONS_TABLE, $zDataHelper::ZOODPAY_TRANSACTIONS_status, $safePost["status"], $zDataHelper::ZOODPAY_TRANSACTIONS_id, $safePost["transaction_id"]);

        LocalRedirect($merchantData['ZOODPAY_NOTIFY_URL_PAID'], true, 301);
    }
}
?>