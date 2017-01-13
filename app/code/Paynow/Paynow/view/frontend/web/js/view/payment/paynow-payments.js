/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'paynow',
                component: 'Paynow_Paynow/js/view/payment/method-renderer/paynow-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
