define(
    [
        'uiComponent',
        'jquery',
        'ko',
        'featherlight',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Simpl_Splitpay/js/price-utils',
        'Magento_Customer/js/customer-data'
    ],
    function(
        Component,
        $,
        ko,
        featherlight,
        url,
        quote,
        priceUtils,
        customerCart
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Simpl_Splitpay/popup'
            },

            initialize: function () {
                var self = this;
                this._super();
            },

            isEnablePopup: function() {
                if (window.checkoutConfig.getsimple.enablepopup == "1") {
                    if (window.checkoutConfig.getsimple.enabledfor == "1") {
                        return true;
                    } else if (window.checkoutConfig.getsimple.enabledfor == "2" && window.checkoutConfig.getsimple.isMethodAvailable == "1") {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            },

            getPopupUrl: function() {
                return window.checkoutConfig.getsimple.popupurl;
            },

            getSplitAmount: function(customSplitPrice=null) {
                var totals = quote.totals();
                var totalCalculate = totals['subtotal_incl_tax']
                if (customSplitPrice != null)
                {
                    totalCalculate = customSplitPrice;
                }
                return priceUtils.formatPrice((parseFloat(totalCalculate)), quote.getPriceFormat());
            },

            getPopupDescription: function() {
                var totals = quote.totals();
                var totalCalculate = 0;
                $.each(totals['total_segments'], function (i,datas) {
                    if(datas['code'] =='grand_total')
                    {
                        totalCalculate = datas['value'];
                    }
                });
                var calculateTotal = parseFloat(totalCalculate);
                var minPriceLimit = window.checkoutConfig.getsimple.minPriceLimit != '' ? parseInt(window.checkoutConfig.getsimple.minPriceLimit) : '';
                var maxPriceLimit = parseFloat(window.checkoutConfig.getsimple.maxPriceLimit);
                localStorage.setItem("totalCalculate", totalCalculate);

                var calculateTotal = parseFloat(totalCalculate);
                var minPriceLimit = window.checkoutConfig.getsimple.minPriceLimit != '' ? parseInt(window.checkoutConfig.getsimple.minPriceLimit) : '';
                var maxPriceLimit = parseFloat(window.checkoutConfig.getsimple.maxPriceLimit);


                if (minPriceLimit == '' || (minPriceLimit != '' && calculateTotal >= minPriceLimit && calculateTotal <= maxPriceLimit)) {
                    setTimeout(function (calculateTotal) {
                        var totalPrice = localStorage.getItem("totalCalculate");
                        $('a.simpl-popup-link').attr('href','https://d19ud5ez64hf3q.cloudfront.net/popup/index.html?price='+totalPrice);
                    }, 1000);
                    return window.checkoutConfig.getsimple.popupdescription.replace("{{ amount }}", this.getSplitAmount(totalCalculate));
                }
            },

            bindClick: function() {
                if ($("#splitpay_popup_description a.simpl-popup-link").length > 0 ) {
                    ko.cleanNode($("#splitpay_popup_description a.simpl-popup-link")[0]);
                    ko.applyBindings(this, $("#splitpay_popup_description a.simpl-popup-link")[0]);
                }
                $( document ).on('click', 'a.simpl-popup-link', function(event){
                        event.preventDefault();
                        $.ajax({
                            type: 'POST',
                            data: {
                                'action': ((window.location.pathname.search("checkout/cart") == 1)?'CART_INTERSTITIAL_VIEW':'CHECKOUT_INTERSTITIAL_VIEW')
                            },
                            url: url.build('splitpay/payment/event'),
                            success: function (response) {
                            },
                            error: function (response) {
                            }
                        });
                    }
                );
                return this;
            },

            openPopup: function() {
                $.featherlight($('a.simpl-popup-link').data('featherlight'), {});
            }
        });
    }
);
