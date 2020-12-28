# Swedbank Pay Core for WooCommerce

![Build status][build-badge]
[![Latest Stable Version][version-badge]][packagist]
[![Total Downloads][downloads-badge]][packagist]
[![Codecov][codecov-badge]][codecov]
[![License][license-badge]][packagist]

## About

The Swedbank Pay Core for WooCommerce provides methods and classes 
which simplify integration [Swedbank Pay's API Platform][dev-portal] with WooCommerce.

This Core includes the following payments options:

*   Checkout
*   Credit and debit cards (Visa, Mastercard, Visa Electron, Maestro etc).
*   Invoice
*   Swish
*   Vipps
*   MobilePay
*   Trustly

## Installation

The recommended way to install the Swedbank Pay Core for WooCommerce library is with
Composer. You can also download the source code from one of the
[releases here on GitHub][releases] or simply clone this repository.

### Composer

The preferred method is via [Composer][composer]. Follow the
[installation instructions][composer-intro] if you do not already have
composer installed.

Once composer is installed, execute the following command in your project root
to install this SDK:

```sh
composer require swedbank-pay/swedbank-pay-woocommerce-core
```

  [build-badge]:      https://github.com/SwedbankPay/swedbank-pay-woocommerce-core/workflows/Integration%20tests/badge.svg?branch=master
  [dev-portal]:       https://developer.swedbankpay.com/
  [releases]:         https://github.com/SwedbankPay/swedbank-pay-woocommerce-core/releases
  [composer]:         https://getcomposer.org
  [composer-intro]:   https://getcomposer.org/doc/00-intro.md
  [version-badge]:    https://poser.pugx.org/swedbank-pay/swedbank-pay-woocommerce-core/version
  [downloads-badge]:  https://poser.pugx.org/swedbank-pay/swedbank-pay-woocommerce-core/downloads
  [license-badge]:    https://poser.pugx.org/swedbank-pay/swedbank-pay-woocommerce-core/license
  [packagist]:        https://packagist.org/packages/swedbank-pay/swedbank-pay-woocommerce-core
  [codecov]:          https://codecov.io/gh/SwedbankPay/swedbank-pay-woocommerce-core
  [codecov-badge]:    https://codecov.io/gh/SwedbankPay/swedbank-pay-woocommerce-core/branch/master/graph/badge.svg
  [dependabot]:       https://dependabot.com
  [dependabot-badge]: https://api.dependabot.com/badges/status?host=github&repo=SwedbankPay/swedbank-pay-woocommerce-core
  [og-image]:         https://repository-images.githubusercontent.com/211837579/156c6000-53ed-11ea-8927-782b8067996f
