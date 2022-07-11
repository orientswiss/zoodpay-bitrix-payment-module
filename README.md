# ZoodPay 1C-Bitrix Payment Module
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

ZoodPay Buy Now Pay Later payment Module for the 1C-Bitrix.

## Installation

```
1. Copy The Content of this archive to bitrix/modules/zoodpay.payment/
2. Use the market place installer to install the module.
```

## Usage

```
1. Goto Admin -> Online Shop -> Add New Payment System -> Select ZoodPay
2. Fill the necessary information and click Save
3. You should be able to see that API health showing: ZoodPay API is Healthy
4. You should be able to see that Configuration STATUS: ZoodPay Config Fetched
```

## Verification of Patched Files Manually
````
Verify that the following text "//Added for Zoodpay Kindly Do not Delete" exist within the mentioned files:

File 1 : /bitrix/js/sale/admin/order_payment.js
File 2 : /bitrix/modules/sale/lib/helpers/admin/blocks/orderpayment.php
````
## Verification of Installation Automatically
````
open https://website/bitrix/modules/zoodpay.payment/lib/checkScript.php in your broswer window, and it will validate module installation and showing debug info.. 
````
## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Support 
For any inquiry write to integration@zoodpay.com with a detailed description of the issue.
## Credits
- [ZoodPay](https://github.com/orientswiss)
## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
