<?php

use Bitrix\Main\Application;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $APPLICATION;

global $DB;

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

?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="<?php echo APP_CHARSET; ?>">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo "Bitrix Installation Report For ZoodPay Module" ?></title>
        <link href="data:image/x-icon;base64,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" rel="shortcut icon" type="image/x-icon" />
    </head>
    <body>



    <article>

        <?php ob_start(); ?>


        <?php
        // Checked Parsed Files
        $orderPaymentJs = "Did not Patched";
        $orderPaymentPhp = "Did not Patched";
        $zoodpayCT= "Does Not Exist";
        $zoodpayRT= "Does Not Exist";
        $zoodpayTT= "Does Not Exist";
        if (file_exists(Application::getDocumentRoot() . '/bitrix/js/sale/admin/order_payment.js')) {
            $b = file_get_contents(Application::getDocumentRoot()  . '/bitrix/js/sale/admin/order_payment.js');
            if (strstr($b, "//Added for Zoodpay")) {
                $orderPaymentJs = "Patched";
            }
        }


        if (file_exists(Application::getDocumentRoot()  . '/bitrix/modules/sale/lib/helpers/admin/blocks/orderpayment.php')) {
            $b = file_get_contents(Application::getDocumentRoot()  . '/bitrix/modules/sale/lib/helpers/admin/blocks/orderpayment.php');
            if (strstr($b, "//Added for Zoodpay")) {
                $orderPaymentPhp = "Patched";
            }
        }

        //Check Db Status

        global $DB;
        $zoodpayConfigTable = $DB->Query("show tables like 'zoodpay_config'");
        $zoodpayConfigTable = $zoodpayConfigTable->fetch();
        if($zoodpayConfigTable != null)
            if ( isset($zoodpayConfigTable) )
            {
                $zoodpayCT= "Exist";
            }

        $zoodpayrefundsTable = $DB->Query("show tables like 'zoodpay_refunds'");
        $zoodpayrefundsTable = $zoodpayrefundsTable->fetch();
        if($zoodpayrefundsTable != null)
            if ( isset($zoodpayrefundsTable) )
            {
                $zoodpayRT= "Exist";
            }

        $zoodpaytransactionsTable = $DB->Query("show tables like 'zoodpay_transactions'");
        $zoodpaytransactionsTable = $zoodpaytransactionsTable->fetch();
        if($zoodpaytransactionsTable != null)
            if ( isset($zoodpaytransactionsTable) )
            {
                $zoodpayTT= "Exist";
            }

        ?>

        <h1 id="zoodpay_check">ZoodPay Installation Check</h1>
        <table class="table">
            <thead>
            <tr>
                <th style="width: 35%">Method</th>
                <th> Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            echo '<tr> ';
            echo '<td class="key"> Parsing '. Application::getDocumentRoot() . '/bitrix/js/sale/admin/order_payment.js'.'</td>';
            echo '<td >'. $orderPaymentJs.'</td>';
            echo '</tr>';

            echo '<tr> ';
            echo '<td class="key"> Parsing '.Application::getDocumentRoot() . '/bitrix/modules/sale/lib/helpers/admin/blocks/orderpayment.php'.'</td>';
            echo '<td >'. $orderPaymentPhp.'</td>';
            echo '</tr>';
            echo '<tr> ';
            echo '<td class="key"> Table ZoodPay Configuration </td>';
            echo '<td >'. $zoodpayCT.'</td>';
            echo '</tr>';
            echo '<tr> ';
            echo '<td class="key"> Table ZoodPay Refunds </td>';
            echo '<td >'. $zoodpayRT.'</td>';
            echo '</tr>';
            echo '<tr> ';
            echo '<td class="key"> Table ZoodPay Transactions  </td>';
            echo '<td >'. $zoodpayTT.'</td>';
            echo '</tr>';

            ?>
            </tbody>
        </table>


        <h1 id="php_check"><?php echo "Php Report " ?></h1>
        <table class="table">
            <tbody>
            <tr>
                <td class="key" style="width: 35%">PHP Version</td>
                <td><?php echo enc(phpversion()); ?></td>
            </tr>
            <tr>
                <td class="key">System</td>
                <td><?php echo enc(php_uname()); ?></td>
            </tr>
            <tr>
                <td class="key">Server API</td>
                <td><?php echo enc(php_sapi_name()); ?></td>
            </tr>
            <?php if(function_exists('posix_getpwuid')) { ?>
                <tr>
                    <td class="key">Web User</td>
                    <td>
                        <?php
                        $posixUser = posix_getpwuid(posix_geteuid());
                        echo enc($posixUser['name']);
                        ?>
                    </td>
                </tr>
            <?php } ?>
            <tr>
                <td class="key">Loaded Configuration File</td>
                <td><?php echo enc(php_ini_loaded_file()); ?></td>
            </tr>
            <tr>
                <td class="key">Scanned Configuration Files</td>
                <td><?php echo str_replace(',', '<br />', enc(php_ini_scanned_files())); ?></td>
            </tr>



            <tr>
                <td class="key">Error Reporting</td>
                <td>
                    <?php
                    $erep = array();
                    $er = (int)ini_get('error_reporting');
                    $erep[] = $er & E_ERROR ? '<span class="green">E_ERROR</span>' : '<span class="gray">E_ERROR</span>';
                    $erep[] = $er & E_WARNING ? '<span class="green">E_WARNING</span>' : '<span class="gray">E_WARNING</span>';
                    $erep[] = $er & E_PARSE ? '<span class="green">E_PARSE</span>' : '<span class="gray">E_PARSE</span>';
                    $erep[] = $er & E_NOTICE ? '<span class="green">E_NOTICE</span>' : '<span class="gray">E_NOTICE</span>';
                    $erep[] = $er & E_CORE_ERROR ? '<span class="green">E_CORE_ERROR</span>' : '<span class="gray">E_CORE_ERROR</span>';
                    $erep[] = $er & E_CORE_WARNING ? '<span class="green">E_CORE_WARNING</span>' : '<span class="gray">E_CORE_WARNING</span>';
                    $erep[] = $er & E_COMPILE_ERROR ? '<span class="green">E_COMPILE_ERROR</span>' : '<span class="gray">E_COMPILE_ERROR</span>';
                    $erep[] = $er & E_COMPILE_WARNING ? '<span class="green">E_COMPILE_WARNING</span>' : '<span class="gray">E_COMPILE_WARNING</span>';
                    $erep[] = $er & E_USER_ERROR ? '<span class="green">E_USER_ERROR</span>' : '<span class="gray">E_USER_ERROR</span>';
                    $erep[] = $er & E_USER_WARNING ? '<span class="green">E_USER_WARNING</span>' : '<span class="gray">E_USER_WARNING</span>';
                    $erep[] = $er & E_USER_NOTICE ? '<span class="green">E_USER_NOTICE</span>' : '<span class="gray">E_USER_NOTICE</span>';
                    $erep[] = $er & E_STRICT ? '<span class="green">E_STRICT </span>' : '<span class="gray">E_STRICT</span>';
                    $erep[] = $er & E_RECOVERABLE_ERROR ? '<span class="green">E_RECOVERABLE_ERROR</span>' : '<span class="gray">E_RECOVERABLE_ERROR</span>';
                    $erep[] = $er & E_DEPRECATED ? '<span class="green">E_DEPRECATED</span>' : '<span class="gray">E_DEPRECATED</span>';
                    $erep[] = $er & E_USER_DEPRECATED ? '<span class="green">E_USER_DEPRECATED</span>' : '<span class="gray">E_USER_DEPRECATED</span>';
                    echo implode(" ", $erep);
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key">phpinfo() Availability</td>
                <td>
                    <?php
                    ob_start();
                    if(function_exists('phpinfo')) @phpinfo();
                    $pi = ob_get_contents();
                    ob_end_clean();
                    echo empty($pi) ? '<span class="red">disabled</span>' : '<span class="green">enabled</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key">Zend Version</td>
                <td><?php echo enc(zend_version()); ?></td>
            </tr>
            <tr>
                <td class="key">Local Time</td>
                <td><?php echo date('Y-m-d H:i:s'); ?></td>
            </tr>
            </tbody>
        </table>






        <?php
        $article = ob_get_contents();
        ob_end_flush();
        ?>

    </article>

    <nav>
        <?php

        preg_match_all('/<h(1|2) id="([^"]+)">(.*)<\/h(1|2)>/U', $article, $headings);

        echo '<ul>';
        foreach($headings[0] AS $i => $match) {
            $level = $headings[1][$i];
            $id = $headings[2][$i];
            $title = $headings[3][$i];
            echo '<li class="level'.$level.'"><a href="#'.$id.'">'.$title.'</a></li>';
        }
        echo '</ul>';

        ?>
    </nav>

    <style>
        html {
            font-family: Arial;
            font-size: 12px;
            box-sizing: border-box;
            background: #fff;
            color: #fff;
            width: 100%;
            height: 100%;

        }
        * {
            font-family: inherit;
            font-size: inherit;
            font-weight: inherit;
            font-style: inherit;
            text-decoration: inherit;
            box-sizing: inherit;
            padding: 0;
            margin: 0;
            color: inherit;
            background: transparent;
            text-align: inherit;
        }
        body {
            width: 100%;
            height: 100%;
            position: relative;
        }
        nav {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            padding: 2rem;
            overflow: auto;
        }
        article {
            position: absolute;
            overflow: auto;
            left: 250px;
            top: 0;
            bottom: 0;
            right: 0;
            padding: 2rem;
            overflow: auto;
        }
        b {
            font-weight: bold;
        }
        a {
            text-decoration: underline;
        }
        h1 {
            text-align: center;
            font-weight: bold;
            font-size: 1.8rem;
            background: #303898;
            border: 1px solid #666;
            padding: 0.5rem;
            box-shadow: 1px 2px 3px #cccccc;
            max-width: 1050px;
            margin: 3rem auto;
            border-radius: 30px;
        }
        #header {
            font-size: 2.2rem;
            padding: 1rem 0.5rem;
            margin-top: 0;
        }
        h2 {
            text-align: center;
            font-weight: bold;
            font-size: 1.6rem;
        }
        h2, table {
            margin: 1.5rem auto;
            max-width: 1000px;
        }
        th {
            font-weight: bold;
        }
        .table {
            border-collapse: collapse;
            border-top: 1px solid #666;
            border-left: 1px solid #666;
            box-shadow: 1px 2px 3px #cccccc;
            width: 100%;
        }
        .table thead {
            background: #303898;
        }
        .table td,
        .table th {
            border-right: 1px solid #666;
            border-bottom: 1px solid #666;
            padding: 4px 5px;
        }
        .table td {
            background: #dddddd;
            overflow-x: auto;
            max-width: 300px;
            word-wrap: break-word;
            color: #0b011d;
        }
        .table td.key {
            font-weight: bold;
            background-color: #f7df1ec2;
            color : #2d4234
        }
        .no-value {
            color: #fff;
            font-style: italic;
        }
        .green {
            color: #008000;
        }
        .red {
            color: red;
        }
        .gray {
            color: #999;
        }
        .docs {
            font-weight: normal;
            color: #666;
            float: right;
        }
        nav ul {
            list-style: none;
            border-bottom: 1px solid #666;
            box-shadow: 1px 2px 3px #ccc;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        nav ul>li:first-child a {
            border-radius: 4px 4px 0 0;
        }
        nav ul {
            border-radius: 0 0 4px 4px;
        }
        nav ul>li:last-child a {
            border-radius: 0 0 3px 3px;
        }
        nav a {
            display: block;
            border: 1px solid #666;
            border-bottom: 0;
            padding: 4px 5px;
            text-decoration: none;
        }
        nav a:hover {
            color: #000;
        }
        nav li.level1 a {
            background: #303898;
            font-weight: bold;
        }
        nav li.level2 a {
            background: #ccccff;
            padding-left: 2rem;
        }
    </style>


    </body>
    </html>


<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
