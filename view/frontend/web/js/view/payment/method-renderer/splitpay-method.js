define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList'
    ],
    function (Component, quote, $, ko, additionalValidators, setPaymentInformationAction, url, customer, placeOrderAction, fullScreenLoader, messageList) {
        'use strict';

        return Component.extend({
            defaults: {             
                redirectAfterPlaceOrder: false,
                template: 'Simpl_Splitpay/payment/splitpay-form'
            },
            
            context: function() {
                return this;
            },

            isShowLegend: function() {
                return true;
            },

            getCode: function() {
                return 'splitpay';
            },

            isActive: function() {
                return true;
            },

            getTitle: function() {
                return window.checkoutConfig.payment.splitpay.title;
            },
            
            getDescription: function() {
                return window.checkoutConfig.payment.splitpay.description;
            },

            initObservable: function() {
                var self = this._super();
                return self;
            },           
            
            afterPlaceOrder: function () {
                $.mage.redirect(url.build('splitpay/payment/request'));
            }                       
        });               
    }
);