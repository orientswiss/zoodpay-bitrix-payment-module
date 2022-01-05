<?php
/**
 * Description: Load the classes and event on each Handler Call
 * Author: mintali
 * Email : mohammadali.namazi@zoodpay.com
 * Date: 2021-05-16, Sun, 1:37
 * File: include
 * Path: bitrix/modules/zoodpay.payment/include.php
 * Line: 16
 */

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

//EventManager::getInstance()->addEventHandler("sale", "OnSaleOrderBeforeSaved", ["\\OS\\ZEventHandler\\EHandler", "OnSaleOrderBeforeSavedHandler"]);
try {
    Loader:: includeModule('sale');
    $classes = array(
        'OS\ZEventHandler\EHandler' => 'lib/EHandler.php',
        'OS\Helper\DataHelper' => 'lib/DataHelper.php'
    );
    CModule::AddAutoloadClasses('zoodpay.payment', $classes);

} catch (LoaderException $e) {
    echo $e->getMessage();
}