# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.1] - 2022-06-28

### Changed

- Orthography for russian language file

## [1.5.0] - 2021-07-17

### Added

- Activate/Deactivate Module Automatically based on the ZoodPay API.
- Ability to Pay through the orderpay.php (User Order History Page)
- Changed in ZoodPay Transactions Table, Storing the URL.
- Validation of transaction expiry time
- Callbacks logic Moved to Module Folder,public folder will be only a reference.
- checkScript.php to validate module installation and debug info.
- Before Placing an Order,  EHandler.php will check the provided information, and validate the length etc....
- step.php will provide debug info to be sent within a mail.
- zoodpay_refund.php will change the order status also, and merchant need to create 3 status for refund.
