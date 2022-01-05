<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem\Manager;


Loc::loadMessages(__FILE__);

$description = [
    'RETURN' => Loc::getMessage('SALE_HPS_ZP_DESC_RETURN')

];
$isAvailable = false;
$data = [
    'NAME' => Loc::getMessage('SALE_HPS_ZP_NAME'),
    'IS_AVAILABLE' => Manager::HANDLER_AVAILABLE_TRUE,
    'SORT' => 1000,
    'CODES' => [
        'ZOODPAY_USER' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_USER'),
            'SORT' => 100,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
        ],
        'ZOODPAY_PWD' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_PWD'),
            'SORT' => 200,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
        ],
        'ZOODPAY_SALT' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_SALT'),
            'SORT' => 210,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
        ],
        'ZOODPAY_API_URL' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_API_URL'),
            'SORT' => 230,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
        ],
        'ZOODPAY_API_VER' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_API_VER'),
            'SORT' => 250,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
        ],

        'PAYMENT_ID' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_ORDER_ID'),
            'SORT' => 400,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'ID',
                'PROVIDER_KEY' => 'PAYMENT',
            ]
        ],
        'PAYMENT_DATE_INSERT' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_DATE_INSERT'),
            'SORT' => 500,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'DATE_BILL',
                'PROVIDER_KEY' => 'PAYMENT',
            ]
        ],
        'PAYMENT_SHOULD_PAY' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_SHOULD_PAY'),
            'SORT' => 600,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'SUM',
                'PROVIDER_KEY' => 'PAYMENT',
            ]
        ],
        'PAYMENT_CURRENCY' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_CURRENCY'),
            'SORT' => 700,
            'GROUP' => 'PAYMENT',
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'CURRENCY',
                'PROVIDER_KEY' => 'PAYMENT',
            ]
        ],
        'ZOODPAY_NOTIFY_URL_PAID' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_NOTIFY_URL_PAID'),
            'SORT' => 800,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'https://' . $_SERVER['HTTP_HOST'] . '/personal/orders/?filter_history=Y',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],
        'ZOODPAY_NOTIFY_URL_FAILED' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_NOTIFY_URL_FAILED'),
            'SORT' => 800,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'https://' . $_SERVER['HTTP_HOST'] . '/personal/orders/',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],

        'ZOODPAY_SSL_ENABLE' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_SSL_ENABLE'),
            'SORT' => 1000,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'INPUT' => [
                'TYPE' => 'Y/N'
            ],
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'Y',
                'PROVIDER_KEY' => 'INPUT'
            ]
        ],


        'ZOODPAY_RETURN' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_RETURN'),
            'DESCRIPTION' => Loc::getMessage('SALE_HPS_ZP_RETURN_DESC'),
            'SORT' => 1700,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
        ],

        'ZOODPAY_SITE_ID' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_SITE_ID'),
            'DESCRIPTION' => Loc::getMessage('SALE_HPS_ZP_RETURN_DESC'),
            'SORT' => 1700,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
        ],
        'ZOODPAY_LC' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_LS'),
            'SORT' => 1800,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),

            'INPUT' => [
                'TYPE' => 'ENUM',

                'OPTIONS' => [
                    'ru' => Loc::getMessage('SALE_HPS_ZP_LC_RUSSIAN'),
                    'en' => Loc::getMessage('SALE_HPS_ZP_LC_ENGLISH'),
                    'ar' => Loc::getMessage('SALE_HPS_ZP_LC_ARABIC'),
                    'kk' => Loc::getMessage('SALE_HPS_ZP_LC_KAZAKH'),
                    'ku' => Loc::getMessage('SALE_HPS_ZP_LC_KURDISH'),
                    'uz' => Loc::getMessage('SALE_HPS_ZP_LC_UZBEK')
                ],

            ]
        ],

        'ZOODPAY_CC' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_CS'),
            'SORT' => 1800,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),

            'INPUT' => [
                'TYPE' => 'ENUM',

                'OPTIONS' => [
                    'UZ' => Loc::getMessage('SALE_HPS_ZP_LC_UZBEKISTAN'),
                    'KZ' => Loc::getMessage('SALE_HPS_ZP_LC_KAZAKHSTAN'),
                    'IQ' => Loc::getMessage('SALE_HPS_ZP_LC_IRAQ'),
                    'JO' => Loc::getMessage('SALE_HPS_ZP_LC_JORDAN'),
                    'KW' => Loc::getMessage('SALE_HPS_ZP_LC_KUWAIT'),
                    'SA' => Loc::getMessage('SALE_HPS_ZP_LC_SAUDI_ARABIA')
                ],

            ]
        ],
        //Added For User Details
        'ZOODPAY_OC' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_OS'),
            'SORT' => 1800,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),

            'INPUT' => [
                'TYPE' => 'ENUM',

                'OPTIONS' => [
                    'OC' => Loc::getMessage('SALE_HPS_ZP_OC_ORDER'),
                    'PR' => Loc::getMessage('SALE_HPS_ZP_LC_PERSONAL'),
                ],

            ]
        ],


        'ZP_PAID_STATUS' => [
            'NAME' => Loc::getMessage('SALE_ZP_PAID_STATUS'),
            'SORT' => 801,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'P',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],

        'ZP_FAILED_STATUS' => [
            'NAME' => Loc::getMessage('SALE_ZP_FAILED_STATUS'),
            'SORT' => 802,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'N',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],

        'ZP_CANCELLED_STATUS' => [
            'NAME' => Loc::getMessage('SALE_ZP_CANCELLED_STATUS'),
            'SORT' => 803,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'CN',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],

        'ZP_DELIVERED_STATUS' => [
            'NAME' => Loc::getMessage('SALE_ZP_DELIVERED_STATUS'),
            'SORT' => 804,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'DF',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],


        'ZP_REFUND_INITIATED_STATUS' => [
            'NAME' => Loc::getMessage('SALE_ZP_REFUND_INITIATED_STATUS'),
            'SORT' => 805,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'RI',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],
        'ZP_REFUND_APPROVED_STATUS' => [
            'NAME' => Loc::getMessage('SALE_ZP_REFUND_APPROVED_STATUS'),
            'SORT' => 806,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'RA',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],
        'ZP_REFUND_DECLINED_STATUS' => [
            'NAME' => Loc::getMessage('SALE_ZP_REFUND_DECLINED_STATUS'),
            'SORT' => 807,
            'GROUP' => Loc::getMessage('GENERAL_SETTINGS_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => 'RD',
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],


        'ZOODPAY_CHECK_HEALTHY' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_CHECK_HEALTHY'),
            'SORT' => 1900,
            'GROUP' => Loc::getMessage('CONFIG_HEALTHY_ZOODPAY'),
            'INPUT' => [
                'TYPE' => 'Y/N',

            ]

        ],
        'ZOODPAY_API_STATUS' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_API_STATUS'),
            'SORT' => 2000,
            'GROUP' => Loc::getMessage('CONFIG_HEALTHY_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => Loc::getMessage('SALE_HPS_ZP_API_DOWN'),
                'PROVIDER_KEY' => 'VALUE',
            ]
        ],
        'ZOODPAY_CHECK_CONFIG' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_CHECK_CONFIG'),
            'SORT' => 2100,
            'GROUP' => Loc::getMessage('CONFIG_HEALTHY_ZOODPAY'),
            'INPUT' => [
                'TYPE' => 'Y/N',

            ]

        ],
        'ZOODPAY_CONFIG_STATUS' => [
            'NAME' => Loc::getMessage('SALE_HPS_ZP_CONFIG_STATUS'),
            'SORT' => 2200,
            'GROUP' => Loc::getMessage('CONFIG_HEALTHY_ZOODPAY'),
            'DEFAULT' => [
                'PROVIDER_VALUE' => Loc::getMessage('SALE_HPS_ZP_WRONG_CRED'),
                'PROVIDER_KEY' => 'VALUE',
            ]
        ]
        ,


    ]
];