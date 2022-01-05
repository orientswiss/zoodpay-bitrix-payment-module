<?php
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use OS\ZEventHandler\EHandler;
use Bitrix\Main\Application;

global $APPLICATION;

use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

IncludeModuleLangFile(__FILE__);
require_once(__DIR__ . '/../include.php');
if (!CModule::IncludeModule("sale")) return false;
class zoodpay_payment extends CModule
{
    const MODULE_ID = 'zoodpay.payment';
    public $MODULE_ID = 'zoodpay.payment';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    var $strError = '';

    function __construct()
    {

        $this->MODULE_PATH = Application::getDocumentRoot()."/bitrix/modules/".$this->MODULE_ID;
        $arModuleVersion = array();
        include(dirname(__FILE__) . "/version.php");
        include($this->MODULE_PATH . '/install/version.php');
        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }
        $this->MODULE_NAME = GetMessage("ZOODPAY_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("ZOODPAY_MODULE_DESC");
        $this->PARTNER_NAME = GetMessage("ZOODPAY_PARTNER_NAME");
        $this->PARTNER_URI = "https://www.zoodpay.com";

    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    /**
     * @throws Exception
     */
    function DoInstall()
    {
        global $APPLICATION;
        $errors = array();
        $installCheck = true;


        if ((!extension_loaded('curl')) && (!function_exists("curl_init") )) {
            $errors[] = GetMessage("ZOODPAY_NO_CURL");


        }
        if( ! function_exists("json_decode") ) {
            $errors[] = GetMessage('ZOODPAY_NO_JSON');

        }
        if( ! IsModuleInstalled("sale") ) {
            $errors[] =GetMessage('ZOODPAY_NO_SALE');

        }

        $tmp = $this->InstallFiles();
        if($tmp['status'] == false) {

            $errors[] =GetMessage('ZOODPAY_NO_FILE') . ": " . $tmp['errorMsg'];

        }

        if($this->addHandlers() == false) {

            $errors[] =GetMessage('ZOODPAY_NO_EVENT');

        }

        if($this->AddDb()  === false) {

            $errors[] =GetMessage('ZOODPAY_NO_DB');

        }

        if($errors)
        {
            $APPLICATION->ThrowException(implode("<br>", $errors));
            $installCheck =  false;
        }

        if($installCheck)
        {
            RegisterModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile(
                GetMessage("INSTALL_SUCCESS"),
                Application::getDocumentRoot()  . '/bitrix/modules/zoodpay.payment/install/step.php'
            );


        }
        else
        {
            $APPLICATION->IncludeAdminFile(
                GetMessage("INSTALL_ERROR"),
                Application::getDocumentRoot()  . '/bitrix/modules/zoodpay.payment/install/step.php'
            );
        }

    }

    /**
     * @throws Exception
     */
    public function InstallFiles($arParams = array()): array
    {

            if (!(Directory::isDirectoryExists(Application::getDocumentRoot() . '/bitrix/php_interface/include/sale_payment/'))) {
                Directory::createDirectory(Application::getDocumentRoot() . '/bitrix/php_interface/include/sale_payment/');
            }

            if (Directory::isDirectoryExists(Application::getDocumentRoot() . '/bitrix/modules/' . $this->MODULE_ID . '/install')) {
                $source = Application::getDocumentRoot() . '/bitrix/modules/' . $this->MODULE_ID . '/install';

//                CopyDirFiles($source . "/sale_payment/zoodpay/",Application::getDocumentRoot() . '/bitrix/modules/sale/payment/');
                CopyDirFiles($source . "/sale_payment/zoodpay/",Application::getDocumentRoot() . '/bitrix/php_interface/include/sale_payment/zoodpay/',true,true);
                // $this->copyDir($source . "/sale_payment", Application::getDocumentRoot() . '/bitrix/modules/sale/payment');

                if (!Directory::isDirectoryExists(Application::getDocumentRoot() . '/personal/order/payment/')) {
                    Directory::createDirectory(Application::getDocumentRoot() . '/personal/order/payment/');
                }

                if (!Directory::isDirectoryExists(Application::getDocumentRoot() . '/bitrix/images/sale/sale_payments/')) {
                    Directory::createDirectory(Application::getDocumentRoot() . '/bitrix/images/sale/sale_payments/');
                }
                $payment_dir = Application::getDocumentRoot() . '/personal/order/payment/';
                $logo_dir = Application::getDocumentRoot() . '/bitrix/images/sale/sale_payments/';
                File::putFileContents($payment_dir . 'zoodpay_success.php', File::getFileContents($source . "/notifications/zoodpay_success.php"));
                File::putFileContents($payment_dir . 'zoodpay_error.php', File::getFileContents($source . "/notifications/zoodpay_error.php"));
                File::putFileContents($payment_dir . 'zoodpay_ipn.php', File::getFileContents($source . "/notifications/zoodpay_ipn.php"));
                File::putFileContents($payment_dir . 'zoodpay_refund.php', File::getFileContents($source . "/notifications/zoodpay_refund.php"));
                File::putFileContents($logo_dir . 'zoodpay.png', File::getFileContents($source . "/sale_payment/zoodpay/zoodpay.png"));

                if (File::isFileExists(Application::getDocumentRoot() . '/bitrix/js/sale/admin/order_payment.js')) {
                    $b = File::getFileContents(Application::getDocumentRoot() . '/bitrix/js/sale/admin/order_payment.js');
                    if (!strstr($b, "//Added for Zoodpay")) {
                        $pattern = "var input = BX.create('input', {
			props : {
				type : 'text',
				className : 'adm-bus-input',
				name : 'PAY_RETURN_NUM_'+this.index,
				maxlength : 20
			}
		});
		td = BX.create('td', {
			props : { className : 'adm-detail-content-cell-r'},
			children : [input],
			text : BX.message['PAYMENT_OPERATION_TITLE']+':'
		});
		tr.appendChild(td);
		tBody.appendChild(tr);";

                        $replace = "var input = BX.create('input', {
			props : {
				type : 'text',
				className : 'adm-bus-input',
				name : 'PAY_RETURN_NUM_'+this.index,
				maxlength : 20
			}
		});
		td = BX.create('td', {
			props : { className : 'adm-detail-content-cell-r'},
			children : [input],
			text : BX.message['PAYMENT_OPERATION_TITLE']+':'
		});
		tr.appendChild(td);
		tBody.appendChild(tr);
		
		//Added for Zoodpay Kindly Do not Delete





		tr = BX.create('tr', {
			children : [
				BX.create('td', {
					props : { className : 'adm-detail-content-cell-l fwb'},
					text : BX.message['PAYMENT_RETURN_AMOUNT']+':'
				})
			]
		});
		var input = BX.create('input', {
			props : {
				type : 'number',
				className : 'adm-bus-input',
				name : 'RETURN_AMOUNT',
				maxlength : 20
			}
		});
		td = BX.create('td', {
			props : { className : 'adm-detail-content-cell-r'},
			children : [input],
			text : BX.message['PAYMENT_RETURN_AMOUNT']+':'
		});
		tr.appendChild(td);
		tBody.appendChild(tr);

		
";
                        $c = str_replace($pattern, $replace, $b);
                        File::putFileContents(Application::getDocumentRoot() . "/bitrix/js/sale/admin/order_payment.js", $c,File::REWRITE);


                    }

                } else {
                    return ["status" => false , "errorMsg" => GetMessage('ZOODPAY_NO_P1')];

                }

                if (File::isFileExists(Application::getDocumentRoot() . '/bitrix/modules/sale/lib/helpers/admin/blocks/orderpayment.php')) {
                    $b = File::getFileContents(Application::getDocumentRoot() . '/bitrix/modules/sale/lib/helpers/admin/blocks/orderpayment.php');
                    if (!strstr($b, "//Added for Zoodpay")) {

                        $pattern = "'PAYMENT_CASHBOX_CHECK_ADD_WINDOW_TITLE' => Loc::getMessage('PAYMENT_CASHBOX_CHECK_ADD_WINDOW_TITLE')";

                        $replace = "'PAYMENT_CASHBOX_CHECK_ADD_WINDOW_TITLE' => Loc::getMessage('PAYMENT_CASHBOX_CHECK_ADD_WINDOW_TITLE'),
				 //Added for Zoodpay Kindly Do not Delete
            'PAYMENT_RETURN_AMOUNT' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_SUM'),
            ";
                        $c = str_replace($pattern, $replace, $b);
                        File::putFileContents(Application::getDocumentRoot() . '/bitrix/modules/sale/lib/helpers/admin/blocks/orderpayment.php', $c,File::REWRITE);
                    }
                } else {
                    return ["status" => false , "errorMsg" => GetMessage('ZOODPAY_NO_P2')];

                }
            }

        return ["status" => true , "errorMsg" => GetMessage('ZOODPAY_NO_P2')];
    }



    protected function addHandlers()
    {
        parent::InstallEvents();
        EventManager::getInstance()->registerEventHandler(
            'sale',
            'OnSaleOrderBeforeSaved',
            $this->MODULE_ID,
            '\\OS\\ZEventHandler\\EHandler',
            'OnSaleOrderBeforeSavedHandler'
        );
          EventManager::getInstance()->registerEventHandler(
            'sale',
            'OnSaleComponentOrderCreated',
            $this->MODULE_ID,
            '\\OS\\ZEventHandler\\EHandler',
            'PaymentAvailability'
        );

        EventManager::getInstance()->addEventHandler("sale", "OnSaleShipmentEntitySaved", ["\OS\ZEventHandler\EHandler", "OnSaleOrderBeforeSavedHandler"]);
        EventManager::getInstance()->addEventHandler("sale", "OnSaleComponentOrderCreated", ["\OS\ZEventHandler\EHandler", "PaymentAvailability"]);
        return true;
    }

    function AddDb()
    {

        global $DB;
        $DB->Query("
			CREATE TABLE IF NOT EXISTS `zoodpay_config` (
		`id` int AUTO_INCREMENT,
        `Config`  longtext NOT NULL,
      PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2;");
        $DB->Query("
			CREATE TABLE IF NOT EXISTS `zoodpay_transactions` (
		`id` int AUTO_INCREMENT ,
        `transaction_id`           varchar(100) null,
        `merchant_order_reference` varchar(100) null,
        `amount`                   varchar(50)  null,
        `currency`                 varchar(50)  null,
        `selected_service`         varchar(50)  null,
        `payment_id`               varchar(100) null,
        `refund_id`                varchar(100) null,
        `status`                   varchar(50)  null,
        `url`                      text  null,
        `expiry_time`              varchar(150) null,
        `created_at`               varchar(100) null,
        PRIMARY KEY (`id`),
        UNIQUE KEY `zoodpay_transactions_transaction_id` (`transaction_id`)
    
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2;"
        );
        $DB->Query("
        CREATE TABLE IF NOT EXISTS `zoodpay_refunds` (
    `id` int AUTO_INCREMENT ,
    `refund_id`          varchar(250) null,
    `merchant_refund_id` varchar(100) null,
    `payment_id`         varchar(100) null,
    `refund_payment_id`  varchar(100) null,
    `transaction_id`     varchar(100) null,
    `refund_status`      varchar(100) null,
    `refund_amount`      varchar(100) null,
    `order_id`           varchar(100) null,
    PRIMARY KEY (`id`),
    UNIQUE KEY `zoodpay_refunds_refund_id` (`refund_id`)

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2;"
        );

        return true;
    }



    function DoUninstall()
    {
        global $APPLICATION;
        $this->deleteHandlers();
        UnRegisterModule($this->MODULE_ID);
        $this->UnInstallFiles();
        $this->UnistallDb();
        $APPLICATION->IncludeAdminFile(
            GetMessage("UNINSTALL_SUCCESS"),
            Application::getDocumentRoot()  . '/bitrix/modules/zoodpay.payment/install/unstep.php'

        );
    }

    protected function deleteHandlers()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler('sale', 'OnSaleOrderBeforeSaved', $this->MODULE_ID, '\\OS\\ZEventHandler\\EventHandler', 'OnSaleOrderBeforeSavedHandler');
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx('/bitrix/modules/sale/payment/zoodpay');
        DeleteDirFilesEx('/bitrix/php_interface/include/sale_payment/zoodpay');
        DeleteDirFilesEx('/personal/order/zoodpay_success.php');
        DeleteDirFilesEx('/personal/order/zoodpay_error.php');
        DeleteDirFilesEx('/personal/order/zoodpay_ipn.php');
        DeleteDirFilesEx('/personal/order/zoodpay_refund.php');
        return true;
    }
    function UnistallDb() {
        global $DB;
        $DB->Query("DROP TABLE IF EXISTS `zoodpay_config`;");
        return true;
    }
}

?>