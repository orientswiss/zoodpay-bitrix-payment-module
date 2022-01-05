<? use Bitrix\Main\Application;

if(!check_bitrix_sessid()) return;?>
<?
IncludeModuleLangFile(__FILE__);

if ($ex = $APPLICATION->GetException())
{
	echo CAdminMessage::ShowMessage(array(
		'TYPE'    => 'ERROR',
		'MESSAGE' => GetMessage('MOD_INST_ERR'),
		'DETAILS' => $ex->GetString(),
		'HTML'    => true,
	));

    define('APP_CHARSET', ini_get('default_charset'));


    if(!defined('PHP_INI_USER')) define('PHP_INI_USER', 1);
    if(!defined('PHP_INI_PERDIR')) define('PHP_INI_PERDIR', 2);
    if(!defined('PHP_INI_SYSTEM')) define('PHP_INI_SYSTEM', 4);
    if(!defined('PHP_INI_ALL')) define('PHP_INI_ALL', PHP_INI_USER | PHP_INI_PERDIR | PHP_INI_SYSTEM);

    if(defined('ENT_HTML401')) {
        define('ENC_FLAGS', ENT_COMPAT | ENT_HTML401);
    } else {
        define('ENC_FLAGS', ENT_COMPAT);
    }


    function enc($text) {
        if(is_array($text)) print_r($text);
        return htmlentities($text, ENC_FLAGS, APP_CHARSET);
    }
    function get_value($value) {
        if(!isset($value) || $value=='') {
            return '<span class="no-value">no value</span>';
        } else {
            if(preg_match('/^#[a-f0-9]{6}$/i', $value)) {
                return '<span style="color: '.$value.'">'.$value.'</span>';
            }
            if(is_array($value)) {
                $result = array();
                foreach($value AS $k => $v) {
                    $result[] = enc($k).' = '.enc($v);
                }
                return implode("<br />", $result);
            } else {
                return enc($value);
            }
        }
    }

    function get_extension_version($name) {
        $testName = substr($name, 0, 4)=='pdo_' ? 'pdo_*' : $name;
        try {
            switch($testName) {
                case 'mysql':
                    return @mysql_get_client_info();
                case 'mysqli':
                    return @mysqli_get_client_info();
                case 'pdo_*':
                    $pdo = new PDO(substr($name, 4).':');
                    return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            }
        } catch(Exception $ex) { }
        return null;
    }

# get extension list
    $extensions = get_loaded_extensions();
    usort($extensions, 'strnatcasecmp');
    $extVersions = array();
    foreach($extensions AS $ext) {
        $v = get_extension_version($ext);
        if(empty($v)) continue;
        $extVersions[$ext] = $v;
    }

# get configuration by extension
    $configByExt = array();
    foreach(ini_get_all() AS $key => $details) {
        $ext = strpos($key, '.')===false ? '' : substr($key, 0, strpos($key, '.'));
        if(in_array($ext, $extensions)) {
            $configByExt[$ext][$key] = $details;
        } else {
            $configByExt[''][$key] = $details;
        }
    }
    uksort($configByExt, 'strnatcasecmp');



 $mailBody = "Error : " . $ex->GetString() . "%0D%0A";
 $mailBody .= "----------------------------------------------". "%0D%0A";
$mailBody .= "Website Where the module Installed : ". $host='https://'.$_SERVER['SERVER_NAME']. "%0D%0A";
$mailBody .= "----------------------------------------------". "%0D%0A";
$mailBody .= "Link to Check-up Script : ". $host='https://'.$_SERVER['SERVER_NAME']. Application::getPersonalRoot()."/modules/zoodpay.payment/lib/checkScript.php". "%0D%0A";
$mailBody .= "----------------------------------------------". "%0D%0A";
$mailBody .= "Server Details : ". "%0D%0A";
$mailBody .= "------". "%0D%0A";
$mailBody .= "Server Info : ". enc(php_uname()) . "%0D%0A";
$mailBody .= "------". "%0D%0A";
$mailBody .= "Php Version : ".  enc(phpversion()) . "%0D%0A";
$mailBody .= "------". "%0D%0A";
$tmp= str_replace(',', '-', enc(php_ini_scanned_files()));
$tmp= str_replace(array("\n", "\r"), ' ', $tmp);
$mailBody .= "Php Scanned Configuration Files : ". ( $tmp)  . "%0D%0A" ;
$mailBody .= "----------------------------------------------". "%0D%0A";
$mailBody .= "Time Stamp: ". date('m/d/Y h:i:s a', time()). "%0D%0A";
    ?>





<div style="margin: 2px; padding: 2px; color: slategray">
    <input type="button"  value="<?=GetMessage("ZOODPAY_Contact") ?>" class="btn btn-danger" onclick="window.location.href='mailto:integration@zoodpay.com?subject=<?=GetMessage("MOD_INST_ERR") . " ".$_SERVER['SERVER_NAME'] ?>&body=<?=$mailBody?>'">
</div>

<?php

}
else
{
?>
<?=CAdminMessage::ShowNote(GetMessage('STROY_MOD_INST_OK'));?>
<?=BeginNote('align="left"');?>
<?=GetMessage('ZOODPAY_INST_NOTE')?>
<?=EndNote();?>
<?php
}
?>
<form action="<?=$APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?=LANG?>">
	<input type="submit" name="" value="<?=GetMessage("MOD_BACK")?>">
</form>


