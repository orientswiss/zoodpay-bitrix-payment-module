<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
if ($params[0]["PAYED"] != "Y") {
    ?>
    <table border="0" width="100%" cellpadding="2" cellspacing="2">
        <tr>
            <td align="center">
                <?php
                $itemName = "Invoice " . $params["PAYMENT_ID"] . " (" . $params["PAYMENT_DATE_INSERT"] . ")";
                ?>
                <form action="<?= $params['URL'] ?>" method="post">
                    <?php
                    if (isset($params[1]['services'])) {
                        $i = 0;
                        echo "<div> <p>" . Loc::getMessage('ZoodPay_Finance') . "</p>";
                        foreach ($params[1]['services'] as $key => $value) {
                            //Changed based on the New Code
                            echo " <input type='radio' id='$key' name='selected_service' value='$key' " . ($i == 0 ? "checked" : "") . " >
                    <label for='$key'>" . $value["service_text"] . " </label>
                    <img style='max-width: 80px ; padding: 3px' src='".$value["img_src"]."'>
                      <a class='kinda-link' onclick='openModal(\"zpopup$i\")'  >" . Loc::getMessage('ZoodPay_Terms') . "</a>
                    <br> </div>

                    <div class='zmodel' id='zpopup$i' role='dialog'>
                <div class='zmodel-dialog'>
                    <!--ZoodPay Modal content-->
                    <div class='zmodel-content'>
                        <div class='zmodel-header'>

                            <a class='zclose' onclick='zcloseModal(\"zpopup$i\")'>&times;</a>
                            <h4 class='zmodel-title'> " . Loc::getMessage('ZoodPay_Terms') . $key . "</span></h4>
                        </div>
                        <div class='zmodel-body'>
                            <p ><span> " . $value["service_description"] . " </span></p>
                        </div>
                        <div class='zmodel-footer'>

                        </div>
                    </div>

                </div>
            </div>";
                            $i++;
                        }
                    } else {
                        echo "<pre  class='alert-danger'>" . loc::getMessage("SALE_ZP_ERROR");
                        echo "</pre>";
                    }
                    ?>
                    <input class="btn btn-primary"  style="margin: 6px" type="submit" value="<?php echo loc::getMessage("ZoodPay_Payment"); ?> " name="submit">
                </form>
            </td>
        </tr>
    </table>
    <?php
} else {
    echo "<pre  class='alert-danger'>" . loc::getMessage("SALE_ZP_ERROR");
    echo "</pre>";
}



?>
<header>

</header>

<style>
    a.kinda-link:hover {
        cursor: pointer;
    }


    /* The Modal (background) */
    .zmodel {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        padding-top: 100px; /* Location of the box */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0, 0, 0); /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
    }

    /* Modal Content */
    .zmodel-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }

    /* The Close Button */
    .zclose {
        color: #aaaaaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .zclose:hover,
    .zclose:focus {
        color: #000;
        text-decoration: none;
        cursor: pointer;
    }
</style>

<script>

    function openModal(id) {

        var zmodel = document.getElementById(id);


        zmodel.style.display = "block";


    }

    function zcloseModal(id) {

        var zmodel = document.getElementById(id);


        zmodel.style.display = "none";


    }

    // When the user clicks anywhere outside of the zmodel, zclose it
    window.onclick = function (event) {
        if (event.target == zmodel) {
            zmodel.style.display = "none";
        }
    }
</script>