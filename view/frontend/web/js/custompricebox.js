define([
    'jquery',
    'Simpl_Splitpay/js/price-utils',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template',
    'jquery/ui'
], function ($, utils, utilscatalog, _, mageTemplate)
{
    'use strict';

    return function (widget)
    {
        $.widget('mage.priceBox', widget,
            {
                reloadPrice: function reDrawPrices() {
                    var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                        priceTemplate = mageTemplate(this.options.priceTemplate);

                    var oldPriceValue = 0;
                    var finalPriceValue = 0;
                    var isOldPriceExists = false;

                    _.each(this.cache.displayPrices, function (price, priceCode) {
                        if(priceCode == 'oldPrice') {
                            isOldPriceExists = true;
                        }
                    }, this);

                    _.each(this.cache.displayPrices, function (price, priceCode) {

                        price.final = _.reduce(price.adjustments, function (memo, amount) {
                            return memo + amount;
                        }, price.amount);

                        price.formatted = utilscatalog.formatPrice(price.final, priceFormat);

                        if(priceCode == 'oldPrice') {
                            oldPriceValue = price.final;
                        }

                        if(priceCode == 'finalPrice') {
                            finalPriceValue = price.final;
                        }


                        var minPriceLimit = window.minPriceLimit!='' ? parseInt(window.minPriceLimit) : '';
                        var maxPriceLimit = parseInt(window.maxPriceLimit);
                        var payinThreeModel = 'https://d19ud5ez64hf3q.cloudfront.net/popup/index.html?price='+ price.final;
                        if(priceCode=='baseOldPrice') {
                            $('#simplprice').text(utils.formatPrice(price.final, priceFormat));
                            $('.simpl-popup-link').attr('href',payinThreeModel);
                        }else{
                            $('#simplprice').text(utils.formatPrice(finalPriceValue, priceFormat));
                            $('.simpl-popup-link').attr('href',payinThreeModel);
                        }


                        if(minPriceLimit =='' || (minPriceLimit!='' && price.final >= minPriceLimit && price.final <= maxPriceLimit)) {
                            if (window.enabledfor == 2 && isOldPriceExists) {
                                if (oldPriceValue == price.final) {
                                    $('div.splitpay').show();
                                } else {
                                    $('div.splitpay').hide();
                                }
                                if(priceCode=='baseOldPrice') {
                                    $('#simplprice').text(utils.formatPrice(price.final, priceFormat));
                                    $('.simpl-popup-link').attr('href',payinThreeModel);
                                }else{
                                    $('#simplprice').text(utils.formatPrice(finalPriceValue, priceFormat));
                                    $('.simpl-popup-link').attr('href',payinThreeModel);
                                }
                            } else {
                                $('div.splitpay').show();
                                if(priceCode=='baseOldPrice') {
                                    $('#simplprice').text(utils.formatPrice(price.final, priceFormat));
                                    $('.simpl-popup-link').attr('href',payinThreeModel);
                                }else{
                                    $('#simplprice').text(utils.formatPrice(finalPriceValue, priceFormat));
                                    $('.simpl-popup-link').attr('href',payinThreeModel);
                                }
                            }
                        }else{
                            $('div.splitpay').hide();
                        }
                        $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate({
                            data: price
                        }));

                    }, this);
                }
            });

        return $.mage.priceBox;
    }
});
