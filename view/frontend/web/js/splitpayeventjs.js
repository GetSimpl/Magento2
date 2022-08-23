define([
    "jquery",
    'mage/url'
], function ($, url) {
    'use strict';
    $.widget('mage.splitpayeventjs', {
        options: {            
        },
        _create: function () {
            var self = this;
            if(self.options.pageaction == '')
            {
                $.ajax({
                    type: 'POST',
                    url: url.build('splitpay/payment/track'),
                    success: function (response) {
                        
                    },
                    error: function (response) {
                        console.log(response);
                    }
                });
            }
        }
    });
    return $.mage.splitpayeventjs;
});