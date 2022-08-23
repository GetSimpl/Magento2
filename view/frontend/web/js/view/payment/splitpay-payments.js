define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push({
            type: 'splitpay',
            component: 'Simpl_Splitpay/js/view/payment/method-renderer/splitpay-method'
        });
        
        return Component.extend({});
    }
);