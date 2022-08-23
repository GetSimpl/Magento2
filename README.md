# Simpl Pay-in-3 for Magento2


Give your customers the option to purchase any product in 3 simple payments by using Simpl. The “Simpl Pay-in-3 Gateway for Magento2” plugin provides the option to choose Simpl Pay-in-3 as the checkout payment method.
It supports displaying the Simpl logo and payment calculations below product prices on individual product pages, cart page and the checkout page.

For each payment that is fulfilled by Simpl, an order will be created inside the Magento2 system You can easily track these orders in the ‘ORDERS’ section of the Magento2 admin panel. Additionally, we support instant refunds automatically. You can initiate a refund from the order detail page.

## Magento2 Support Version
- Up to 2.4.4 ( Including PHP 8.1 Version)

## Install through "Simpl.zip" file

- Go to <b>App / Code / </b>
- Create Directory called `Simpl` and then create Sub-directory called `Splitpay` 
- Upload whole code under Directory structured called `App/Code/Simpl/Splitpay`
- Run All Below commands

```
php bin/magento module:enable Simpl_Splitpay
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
```

## Install the extension through composer package manager.

```
composer require getsimpl/module-splitpay
```

```
php bin/magento module:enable Simpl_Splitpay
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
```