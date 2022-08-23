var config = {    
    paths: {
        'featherlight': "Simpl_Splitpay/js/featherlight"
    },
    shim: {
        'featherlight': {
            deps: ['jquery']
        }
    },
    config: {
        mixins: {            
            'Magento_Catalog/js/price-box': {
            	'Simpl_Splitpay/js/custompricebox': true
            }
        }
    }    
};