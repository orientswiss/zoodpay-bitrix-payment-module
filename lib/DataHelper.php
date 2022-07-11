<?php
namespace OS\Helper;

use Bitrix\Main\ArgumentException;
use Bitrix\Sale\BusinessValue;
use Bitrix\Main\Localization\Loc;

/**
 * Class DataHelper
 * @package OS\Helper
 * Author: mintali
 * Email : mohammadali.namazi@zoodpay.com
 * Date: 2021-05-16, Sun, 21:40
 * File: DataHelper
 * Path: bitrix/modules/zoodpay.payment/lib/DataHelper.php
 * Line: 16
 */
class DataHelper
{
    const paymentCode = 'zoodpay';
    const ZOODPAY_USER = 'ZOODPAY_USER';
    const ZOODPAY_PWD ='ZOODPAY_PWD';
    const ZOODPAY_SALT = 'ZOODPAY_SALT';
    const ZOODPAY_API_URL = 'ZOODPAY_API_URL';
    const ZOODPAY_API_VER = 'ZOODPAY_API_VER';
    const ZOODPAY_SITE_ID = 'ZOODPAY_SITE_ID';
    const ZOODPAY_NOTIFY_URL = 'ZOODPAY_NOTIFY_URL';
    const ZOODPAY_SSL_ENABLE = 'ZOODPAY_SSL_ENABLE';
    const ZOODPAY_RETURN = 'ZOODPAY_RETURN';
    const ZOODPAY_LC = 'ZOODPAY_LC';
    const ZOODPAY_CC = 'ZOODPAY_CC';
    const ZOODPAY_OC = 'ZOODPAY_OC';
    const ZOODPAY_CHECK_HEALTHY = 'ZOODPAY_CHECK_HEALTHY';
    const ZOODPAY_AUTO_TURN = 'ZOODPAY_AUTO_TURN';
    const ZOODPAY_API_STATUS = 'ZOODPAY_API_STATUS';
    const ZOODPAY_CHECK_CONFIG = 'ZOODPAY_CHECK_CONFIG';
    const ZOODPAY_CONFIG_STATUS = 'ZOODPAY_CONFIG_STATUS';
    const ZP_PAID_STATUS = 'ZP_PAID_STATUS';
    const ZP_FAILED_STATUS = 'ZP_FAILED_STATUS';
    const ZP_DELIVERED_STATUS = 'ZP_DELIVERED_STATUS';
    const ZP_CANCELLED_STATUS = 'ZP_CANCELLED_STATUS';
    const ZP_REFUND_INITIATED_STATUS = 'ZP_REFUND_INITIATED_STATUS';
    const ZP_REFUND_APPROVED_STATUS = 'ZP_REFUND_APPROVED_STATUS';
    const ZP_REFUND_DECLINED_STATUS = 'ZP_REFUND_DECLINED_STATUS';
    const ZOODPAY_API_HEALTHY_MESSAGE = 'ZoodPay API is Healthy';
    const ZOODPAY_API_DOWN_MESSAGE = 'ZoodPay API is DOWN';
    const ZOODPAY_CONFIG_FETCHED_MESSAGE = 'ZoodPay Config Fetched';
    const ZOODPAY_CONFIG_ERROR_MESSAGE = 'Wrong Credentials';
    const ZOODPAY_NOTIFY_URL_PAID = 'ZOODPAY_NOTIFY_URL_PAID';
    const ZOODPAY_NOTIFY_URL_FAILED = 'ZOODPAY_NOTIFY_URL_FAILED';
    //Payment Section;
    const b_sale_pay_system_action = 'b_sale_pay_system_action';
    const RESULT_FILE = 'RESULT_FILE';
    const ACTION_FILE = 'ACTION_FILE';
    const PAY_SYSTEM_ID = 'PAY_SYSTEM_ID';
    const PAYMENT_SYSTEM_ID = 'payment_system_id';
    const b_sale_bizval = 'b_sale_bizval';
    const CODE_KEY = 'CODE_KEY';
    const PROVIDER_VALUE = 'PROVIDER_VALUE';
    const b_sale_loc_2site = 'b_sale_loc_2site';
    const b_sale_loc_name = 'b_sale_loc_name';
    const SITE_ID = 'SITE_ID';
    const LOCATION_ID = 'LOCATION_ID';
    const LOCATION_NAME = 'NAME';
    const LANGUAGE_ID = 'LANGUAGE_ID';
    const ACTIVE_STATUS = 'ACTIVE';
    const ZOODPAY_CONFIG_TABLE = 'zoodpay_config';
    const ZOODPAY_TRANSACTIONS_TABLE = 'zoodpay_transactions';
    const ZOODPAY_REFUNDS_TABLE = 'zoodpay_refunds';
    const ZOODPAY_TRANSACTIONS_status = 'status';
    const ZOODPAY_refund_status = 'refund_status';
    const ZOODPAY_TRANSACTIONS_id = 'transaction_id';
    const ZOODPAY_TRANSACTIONS_Merchant_Order_Ref = 'merchant_order_reference';
    const ZOODPAY_merchant_refund_id = 'merchant_refund_id';
    const ZOODPAY_refund_id = 'refund_id';
    const ZOODPAY_CONFIG_COLUMN = 'config';
    const API_CreateTransaction = '/transactions';
    const API_RefundTransaction = '/refunds';
    const API_GetConfigurations = '/configuration';
    const API_HealthCheck = 'healthcheck';
    const API_Delivery = "/delivery";
    const Pending_Payment = 'Pending Payment';
    const PAID = 'Paid';
    const FAILED = 'Failed';
    const CANCELLED = 'Cancelled';

    public function __construct()
    {

    }

    /**
     * @param $merchantID -- Need to provide in case of Auth
     * @param $merchantKey -- Need to provide in case of Auth
     * @param $apiUrl -- API URL
     * @param  $Auth_Req -- is Authentication required or Not
     * @return array -- Response Code + Response
     */
    public function curlGet(string $merchantID, string $merchantKey, string $apiUrl, bool $Auth_Req)
    {
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if ($Auth_Req) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$merchantID:$merchantKey");
        }
        $curl_response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ["statusCode" => $status_code,
            "response" => $curl_response];
    }

    /**
     * @param $table
     * @param $columnToUpdate
     * @param $data
     * @param $columnCondition
     * @param $columnConditionValue
     */
    public function updateDataBaseData($table, $columnToUpdate, $data, $columnCondition, $columnConditionValue)
    {
        global $DB;
        $strSql = "UPDATE " . $table . " set " . $columnToUpdate . " = '" . $data . "'  WHERE " . $columnCondition . " = '" . $columnConditionValue . "'";
        $results = $DB->Query($strSql);

        return $results->Fetch();
    }

    /**
     * @param $table
     * @param $columnToUpdate
     * @param $data
     * @param $columnCondition
     * @param $columnConditionValue
     */
    public function replaceDataBaseData($table, $columnToUpdate, $data, $columnCondition, $columnConditionValue)
    {
        global $DB;
        $strSql = "REPLACE " . $table . " set " . $columnToUpdate . " = '" . $data . "'  WHERE " . $columnCondition . " = '" . $columnConditionValue . "'";
        $results = $DB->Query($strSql);

        return $results->Fetch();
    }


    /**
     * @param $payId
     * @param $min
     * @param $max
     */
    public  function  replacePriceRange($payId, $min, $max){
        if ($payId != null && !empty($payId))
        {
            global $DB;
            $strSql = "SELECT * from b_sale_service_rstr where `CLASS_NAME`= '\\\Bitrix\\\Sale\\\Services\\\PaySystem\\\Restrictions\\\Price' and `SERVICE_ID` =$payId ";
            $results = $DB->Query($strSql);
            $row= $results->Fetch();
            if($row != false )
            {
                $strSql = 'update b_sale_service_rstr set  PARAMS= \'a:2:{s:9:"MIN_VALUE";s:'.strlen((string)$min).':"'.$min.'";s:9:"MAX_VALUE";s:'.strlen((string)$max).':"'.$max.'";}\' where SERVICE_ID ='."$payId".'  AND CLASS_NAME="\\\Bitrix\\\Sale\\\Services\\\PaySystem\\\Restrictions\\\Price";';
            }
            else{
                $strSql = 'INSERT into  b_sale_service_rstr(ID, SERVICE_ID, SERVICE_TYPE, SORT, CLASS_NAME, PARAMS)  VALUES (default,' .$payId.',1,100,"\\\Bitrix\\\Sale\\\Services\\\PaySystem\\\Restrictions\\\Price", \'a:2:{s:9:"MIN_VALUE";s:'.strlen((string)$min).':"'.$min.'";s:9:"MAX_VALUE";s:'.strlen((string)$max).':"'.$max.'";}\' )';
            }
            $results = $DB->Query($strSql);
            return $results->Fetch();
        }
    }

    /**
     * @param $table
     * @param $columnToUpdate
     * @param $data
     * @throws ArgumentException
     */
    public function  insertIntoConfigDataBaseTable($payid, $data){
        global $DB;
        $strSql =  "INSERT " . self::ZOODPAY_CONFIG_TABLE . " (payment_system_id, config) VALUES('".$payid."', '".$data."')  ";
//        $strSql =  "INSERT " . self::ZOODPAY_CONFIG_TABLE . " (payment_system_id, config) VALUES('".$payid."', '".$data."') on DUPLICATE KEY UPDATE `config` = '".$data ."' ";
        $results = $DB->Query($strSql);
        if($results->Fetch()){
            throw new ArgumentException('Db Query Error', 'data');
        }
        return $results->Fetch();


    }

    /**
     * @param $data
     * @return array|false
     * @throws ArgumentException
     */
    public function  insertIntoTranDataBaseTable($data){
        global $DB;
        $strSql =   "INSERT  INTO  zoodpay_transactions (transaction_id, merchant_order_reference, amount, currency, status,selected_service,payment_system_id, payment_id,refund_id,url,expiry_time,created_at) VALUES('".$data['transaction_id']."', '".$data['merchant_order_reference']."', '".$data['amount']."', '".$data['currency']."', '".$data['status']."', '".$data['selected_service']."', '".$data['payment_system_id']."', '".$data['payment_id']."', '".$data['refund_id']."', '".$data['url']."', '".$data['expiry_time']."', '".$data['created_at']."') on DUPLICATE KEY UPDATE `created_at` = '".$data['created_at'] ."',`url` = '".$data['url'] ."' ,`expiry_time` = '".$data['expiry_time'] ."'   ";
        $results = $DB->Query($strSql);
        if($results->Fetch()){
            throw new ArgumentException('Db Query Error', 'data');
        }
        return $results->Fetch();
    }


    /**
     * @param $data
     * @return array|false
     */
    public function  insertIntoRefundDataBaseTable($data){
        global $DB;
        $strSql =   "INSERT  INTO  zoodpay_refunds (refund_id,merchant_refund_id, order_id, payment_id, refund_payment_id, transaction_id,refund_status, refund_amount) VALUES('".$data['refund_id']."', '".$data['merchant_refund_id']."', '".$data['order_id']."', '".$data['payment_id']."', '".$data['refund_payment_id']."', '".$data['transaction_id']."', '".$data['refund_status']."', '".$data['refund_amount']."') on DUPLICATE KEY UPDATE `refund_status` = '".$data['refund_status'] ."'  ";
        $results = $DB->Query($strSql);
        if($results->Fetch()){
            throw new ArgumentException('Db Query Error', 'data');
        }
        return $results->Fetch();
    }


    /**
     * @param $table
     * @param $condition
     * @param $conditionValue
     * @return array|false|null
     */
    public function  getTotalRowData($table, $condition, $conditionValue){

        global $DB;
        $strSql =  "SELECT * FROM ".$table. " WHERE ".$condition." = '". $conditionValue."'";
        $results = $DB->Query($strSql);
        $resultArray = $results->Fetch();
        return $resultArray ?? $results->Fetch();
    }


    /**
     * @param $table
     * @param $columnToSelect
     * @param $conditionRequired
     * @param $columnCondition
     * @param $columnConditionValue
     * @return array|false|null
     */
    public function getDataBaseData($table, $columnToSelect, $conditionRequired, $columnCondition, $columnConditionValue)
    {
        global $DB;
        if ($conditionRequired)
        {
            $strSql = "SELECT distinct " . $columnToSelect . " FROM " . $table . " WHERE " . $columnCondition . " = '" . $columnConditionValue . "'";
        }
        else {
            $strSql = "SELECT distinct " . $columnToSelect . " FROM " . $table;
        }
        $results = $DB->Query($strSql);
        $resultArray = $results->Fetch();
        return $resultArray ?? $results->Fetch();
    }

    /**
     * @param $table
     * @param $conditionRequired
     * @param $columnCondition
     * @param $columnConditionValue
     * @throws ArgumentException
     */
    public function deleteDatbaseTable($table, $conditionRequired, $columnCondition, $columnConditionValue ){
        global $DB;
        if ($conditionRequired)
        {
            $strSql = "DELETE FROM " . $table . " WHERE " . $columnCondition . " = '" . $columnConditionValue . "'";
        }
        else $strSql = "DELETE FROM " . $table ;
        $results = $DB->Query($strSql);
        if($results->Fetch()){
            throw new ArgumentException('Db Query Error', 'data');
        }
        return $results->Fetch();
    }

    /**
     * @param $data -- array of data or data that need to be sent
     * @param $api_end -- ending of url choose from the constant Values
     * @return array -- return array ($status_code,$curl_response )
     */
    public function curlPost($merchantID, $merchantKey, $data, $apiUrl)
    {
        $data_string = json_encode($data);
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$merchantID:$merchantKey");
        $curl_response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ["statusCode" => $status_code,
            "response" => $curl_response];
    }

    /**
     * @param $merchantID -- Merchant Key
     * @param $merchantKey -- Merchant Secret
     * @param $data -- array of data or data that need to be sent
     * @param $apiUrl
     * @return array -- return array ($status_code,$curl_response )
     */
    public function curlPUT($merchantID, $merchantKey, $data, $apiUrl){
        $data_string = json_encode($data);
        $del_headers=   ['Accept: application/json', 'Content-Length: ' . strlen($data_string) , 'Authorization: Basic ' . base64_encode($merchantID . ':' . $merchantKey) , 'Content-Type: application/json', ];
        $D_ch = curl_init();
        curl_setopt($D_ch, CURLOPT_POST, 1);
        curl_setopt($D_ch, CURLOPT_URL, $apiUrl);
        curl_setopt($D_ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($D_ch, CURLOPT_HEADER, 0);
        curl_setopt($D_ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($D_ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($D_ch, CURLOPT_HTTPHEADER, $del_headers);
        curl_setopt($D_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($D_ch, CURLOPT_CUSTOMREQUEST, "PUT");
        $curl_response = curl_exec($D_ch);
        $status_code = curl_getinfo($D_ch, CURLINFO_HTTP_CODE);
        curl_close($D_ch);
        return ["statusCode" => $status_code,
            "response"=>$curl_response];
    }

    /**
     * @param $countryID
     * @return mixed|null
     */
    public function getCountryName($countryID){
        $countryName = $this->getDataBaseData(self::b_sale_loc_name, self::LOCATION_NAME,true, self::LOCATION_ID, $countryID);
        if(isset( $countryName['NAME'])){
            return  $countryName['NAME'];
        }
        return null;
    }

    /**
     * @param $countryName
     * @return string|null  the Country code  based on CountryName in English
     */
    public function getCountryCode($countryName)
    {
        $countryList = array (
            'Afghanistan' => 'AF',
            'Aland Islands' => 'AX',
            'Albania' => 'AL',
            'Algeria' => 'DZ',
            'American Samoa' => 'AS',
            'Andorra' => 'AD',
            'Angola' => 'AO',
            'Anguilla' => 'AI',
            'Antarctica' => 'AQ',
            'Antigua And Barbuda' => 'AG',
            'Argentina' => 'AR',
            'Armenia' => 'AM',
            'Aruba' => 'AW',
            'Australia' => 'AU',
            'Austria' => 'AT',
            'Azerbaijan' => 'AZ',
            'Bahamas' => 'BS',
            'Bahrain' => 'BH',
            'Bangladesh' => 'BD',
            'Barbados' => 'BB',
            'Belarus' => 'BY',
            'Belgium' => 'BE',
            'Belize' => 'BZ',
            'Benin' => 'BJ',
            'Bermuda' => 'BM',
            'Bhutan' => 'BT',
            'Bolivia' => 'BO',
            'Bosnia And Herzegovina' => 'BA',
            'Botswana' => 'BW',
            'Bouvet Island' => 'BV',
            'Brazil' => 'BR',
            'British Indian Ocean Territory' => 'IO',
            'Brunei Darussalam' => 'BN',
            'Bulgaria' => 'BG',
            'Burkina Faso' => 'BF',
            'Burundi' => 'BI',
            'Cambodia' => 'KH',
            'Cameroon' => 'CM',
            'Canada' => 'CA',
            'Cape Verde' => 'CV',
            'Cayman Islands' => 'KY',
            'Central African Republic' => 'CF',
            'Chad' => 'TD',
            'Chile' => 'CL',
            'China' => 'CN',
            'Christmas Island' => 'CX',
            'Cocos (Keeling) Islands' => 'CC',
            'Colombia' => 'CO',
            'Comoros' => 'KM',
            'Congo' => 'CG',
            'Congo, Democratic Republic' => 'CD',
            'Cook Islands' => 'CK',
            'Costa Rica' => 'CR',
            'Cote D\'Ivoire' => 'CI',
            'Croatia' => 'HR',
            'Cuba' => 'CU',
            'Cyprus' => 'CY',
            'Czech Republic' => 'CZ',
            'Denmark' => 'DK',
            'Djibouti' => 'DJ',
            'Dominica' => 'DM',
            'Dominican Republic' => 'DO',
            'Ecuador' => 'EC',
            'Egypt' => 'EG',
            'El Salvador' => 'SV',
            'Equatorial Guinea' => 'GQ',
            'Eritrea' => 'ER',
            'Estonia' => 'EE',
            'Ethiopia' => 'ET',
            'Falkland Islands (Malvinas)' => 'FK',
            'Faroe Islands' => 'FO',
            'Fiji' => 'FJ',
            'Finland' => 'FI',
            'France' => 'FR',
            'French Guiana' => 'GF',
            'French Polynesia' => 'PF',
            'French Southern Territories' => 'TF',
            'Gabon' => 'GA',
            'Gambia' => 'GM',
            'Georgia' => 'GE',
            'Germany' => 'DE',
            'Ghana' => 'GH',
            'Gibraltar' => 'GI',
            'Greece' => 'GR',
            'Greenland' => 'GL',
            'Grenada' => 'GD',
            'Guadeloupe' => 'GP',
            'Guam' => 'GU',
            'Guatemala' => 'GT',
            'Guernsey' => 'GG',
            'Guinea' => 'GN',
            'Guinea-Bissau' => 'GW',
            'Guyana' => 'GY',
            'Haiti' => 'HT',
            'Heard Island & Mcdonald Islands' => 'HM',
            'Holy See (Vatican City State)' => 'VA',
            'Honduras' => 'HN',
            'Hong Kong' => 'HK',
            'Hungary' => 'HU',
            'Iceland' => 'IS',
            'India' => 'IN',
            'Indonesia' => 'ID',
            'Iran, Islamic Republic Of' => 'IR',
            'Iraq' => 'IQ',
            'Ireland' => 'IE',
            'Isle Of Man' => 'IM',
            'Israel' => 'IL',
            'Italy' => 'IT',
            'Jamaica' => 'JM',
            'Japan' => 'JP',
            'Jersey' => 'JE',
            'Jordan' => 'JO',
            'Kazakhstan' => 'KZ',
            'Kenya' => 'KE',
            'Kiribati' => 'KI',
            'Korea' => 'KR',
            'Kuwait' => 'KW',
            'Kyrgyzstan' => 'KG',
            'Lao People\'s Democratic Republic' => 'LA',
            'Latvia' => 'LV',
            'Lebanon' => 'LB',
            'Lesotho' => 'LS',
            'Liberia' => 'LR',
            'Libyan Arab Jamahiriya' => 'LY',
            'Liechtenstein' => 'LI',
            'Lithuania' => 'LT',
            'Luxembourg' => 'LU',
            'Macao' => 'MO',
            'Macedonia' => 'MK',
            'Madagascar' => 'MG',
            'Malawi' => 'MW',
            'Malaysia' => 'MY',
            'Maldives' => 'MV',
            'Mali' => 'ML',
            'Malta' => 'MT',
            'Marshall Islands' => 'MH',
            'Martinique' => 'MQ',
            'Mauritania' => 'MR',
            'Mauritius' => 'MU',
            'Mayotte' => 'YT',
            'Mexico' => 'MX',
            'Micronesia, Federated States Of' => 'FM',
            'Moldova' => 'MD',
            'Monaco' => 'MC',
            'Mongolia' => 'MN',
            'Montenegro' => 'ME',
            'Montserrat' => 'MS',
            'Morocco' => 'MA',
            'Mozambique' => 'MZ',
            'Myanmar' => 'MM',
            'Namibia' => 'NA',
            'Nauru' => 'NR',
            'Nepal' => 'NP',
            'Netherlands' => 'NL',
            'Netherlands Antilles' => 'AN',
            'New Caledonia' => 'NC',
            'New Zealand' => 'NZ',
            'Nicaragua' => 'NI',
            'Niger' => 'NE',
            'Nigeria' => 'NG',
            'Niue' => 'NU',
            'Norfolk Island' => 'NF',
            'Northern Mariana Islands' => 'MP',
            'Norway' => 'NO',
            'Oman' => 'OM',
            'Pakistan' => 'PK',
            'Palau' => 'PW',
            'Palestinian Territory, Occupied' => 'PS',
            'Panama' => 'PA',
            'Papua New Guinea' => 'PG',
            'Paraguay' => 'PY',
            'Peru' => 'PE',
            'Philippines' => 'PH',
            'Pitcairn' => 'PN',
            'Poland' => 'PL',
            'Portugal' => 'PT',
            'Puerto Rico' => 'PR',
            'Qatar' => 'QA',
            'Reunion' => 'RE',
            'Romania' => 'RO',
            'Russian Federation' => 'RU',
            'Rwanda' => 'RW',
            'Saint Barthelemy' => 'BL',
            'Saint Helena' => 'SH',
            'Saint Kitts And Nevis' => 'KN',
            'Saint Lucia' => 'LC',
            'Saint Martin' => 'MF',
            'Saint Pierre And Miquelon' => 'PM',
            'Saint Vincent And Grenadines' => 'VC',
            'Samoa' => 'WS',
            'San Marino' => 'SM',
            'Sao Tome And Principe' => 'ST',
            'Saudi Arabia' => 'SA',
            'Senegal' => 'SN',
            'Serbia' => 'RS',
            'Seychelles' => 'SC',
            'Sierra Leone' => 'SL',
            'Singapore' => 'SG',
            'Slovakia' => 'SK',
            'Slovenia' => 'SI',
            'Solomon Islands' => 'SB',
            'Somalia' => 'SO',
            'South Africa' => 'ZA',
            'South Georgia And Sandwich Isl.' => 'GS',
            'Spain' => 'ES',
            'Sri Lanka' => 'LK',
            'Sudan' => 'SD',
            'Suriname' => 'SR',
            'Svalbard And Jan Mayen' => 'SJ',
            'Swaziland' => 'SZ',
            'Sweden' => 'SE',
            'Switzerland' => 'CH',
            'Syrian Arab Republic' => 'SY',
            'Taiwan' => 'TW',
            'Tajikistan' => 'TJ',
            'Tanzania' => 'TZ',
            'Thailand' => 'TH',
            'Timor-Leste' => 'TL',
            'Togo' => 'TG',
            'Tokelau' => 'TK',
            'Tonga' => 'TO',
            'Trinidad And Tobago' => 'TT',
            'Tunisia' => 'TN',
            'Turkey' => 'TR',
            'Turkmenistan' => 'TM',
            'Turks And Caicos Islands' => 'TC',
            'Tuvalu' => 'TV',
            'Uganda' => 'UG',
            'Ukraine' => 'UA',
            'United Arab Emirates' => 'AE',
            'United Kingdom' => 'GB',
            'United States' => 'US',
            'United States Outlying Islands' => 'UM',
            'Uruguay' => 'UY',
            'Uzbekistan' => 'UZ',
            'Vanuatu' => 'VU',
            'Venezuela' => 'VE',
            'Viet Nam' => 'VN',
            'Virgin Islands, British' => 'VG',
            'Virgin Islands, U.S.' => 'VI',
            'Wallis And Futuna' => 'WF',
            'Western Sahara' => 'EH',
            'Yemen' => 'YE',
            'Zambia' => 'ZM',
            'Zimbabwe' => 'ZW',
        );

        if(isset( $countryList[$countryName])){
            return  $countryList[$countryName];
        }
        return null;
    }

    /**
     * @param $array
     * @param $key
     * @param $value
     * @return mixed
     */
    public function removeElementWithValue($array, $key, $value){
        foreach($array as $subKey => $subArray){
            if($subArray[$key] == $value){
                unset($array[$subKey]);
            }
        }
        return $array;
    }

    public function validate_phone_number($phone)
    {
        // Allow +, - and . in phone number
        $filtered_phone_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        // Remove "-" from number
        $phone_to_check = str_replace("-", "", $filtered_phone_number);
        // Check the lenght of number
        // This can be customized if you want phone number from a specific country
        if (strlen($phone_to_check) < 12 || strlen($phone_to_check) > 14) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $payID
     * @return void
     */
    public function checkApiHealth($payID){

        $queryResult = $this->getTotalRowData($this::b_sale_pay_system_action,$this::PAY_SYSTEM_ID,$payID);
       // $queryResult = $this->getDataBaseData($this::b_sale_pay_system_action, $this::PAY_SYSTEM_ID, true, $this::ACTION_FILE, $this::paymentCode);
        $healthy = false ;
        if ($queryResult != false) {
            if($queryResult['ACTION_FILE'] === "zoodpay"){
                $consumerKey = "PAYSYSTEM_" . $payID;
                $merchantData = array(
                    'API_URL' => BusinessValue::get($this::ZOODPAY_API_URL, $consumerKey),
                );
                $apiUrl = $merchantData['API_URL'] . $this::API_HealthCheck;
                $curlResponse = $this->curlGet('', '', $apiUrl, false);
                if (($curlResponse['statusCode'] == 200) && strpos($curlResponse['response'], 'OK')) {
                    $healthy = true;
                }
            }

        }


        if($healthy){
            $qResult = $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_API_HEALTHY'), $this::CODE_KEY, $this::ZOODPAY_API_STATUS);
            $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, "Y", $this::CODE_KEY, $this::ZOODPAY_CHECK_HEALTHY);
            $configResponse = $this->getDataBaseData($this::ZOODPAY_CONFIG_TABLE, $this::ZOODPAY_CONFIG_COLUMN, true, $this::PAYMENT_SYSTEM_ID,  $payID);
            if(isset($configResponse['config'])){
                $config = (json_decode($configResponse['config'], true)['config']);
                $minArray = [];
                $maxArray = [];
                foreach ($config as $key => $value) {
                    array_push($minArray, (int)$value['min_limit']);
                    array_push($maxArray, (int)$value['max_limit']);
                }
                if($payID != null)
                {
                    $this->replacePriceRange($payID, min($minArray), max($maxArray));
                }

                $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_CONFIG'), $this::CODE_KEY, $this::ZOODPAY_CONFIG_STATUS);
            }
            else {
                $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_NO_CONFIG'), $this::CODE_KEY, $this::ZOODPAY_CONFIG_STATUS);
                $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, "N", $this::CODE_KEY, $this::ZOODPAY_CHECK_CONFIG);

                if($payID != null){
                    $this->replacePriceRange($payID, 0, 1);
                }

            }


        }
        else{

            $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_API_DOWN'), $this::CODE_KEY, $this::ZOODPAY_API_STATUS);
            //   AddMessage2Log (DataHelper::ZOODPAY_API_DOWN_MESSAGE);
            $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, "N", $this::CODE_KEY, $this::ZOODPAY_CHECK_HEALTHY);

            $configResponse = $this->getDataBaseData($this::ZOODPAY_CONFIG_TABLE, $this::ZOODPAY_CONFIG_COLUMN, true, $this::PAYMENT_SYSTEM_ID,  $payID);

            if(isset($configResponse['config'])){
                $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_CONFIG'), $this::CODE_KEY, $this::ZOODPAY_CONFIG_STATUS);
                $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, "Y", $this::CODE_KEY, $this::ZOODPAY_CHECK_CONFIG);

            }else{
                $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, Loc::getMessage('SALE_HPS_ZP_NO_VERIFY_CRED'), $this::CODE_KEY, $this::ZOODPAY_CONFIG_STATUS);
                $this->updateDataBaseData($this::b_sale_bizval, $this::PROVIDER_VALUE, "N", $this::CODE_KEY, $this::ZOODPAY_CHECK_CONFIG);
            }


            if($payID != null){
                $this->replacePriceRange($payID, 0, 1);
            }


        }
    }

}