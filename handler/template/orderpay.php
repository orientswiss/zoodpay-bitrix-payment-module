<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

if (!empty($params[2]['url'])){

  echo '<div style="padding: 3px; margin: 3px"><a class="btn" href="'.$params[2]['url'].'">'.Loc::getMessage("ZoodPay_PAY").'</a></div> ';
}