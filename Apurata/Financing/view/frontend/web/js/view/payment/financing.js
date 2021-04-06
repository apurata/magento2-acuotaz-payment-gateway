define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';

        rendererList.push(
            {
                type: 'apurata_financing',
                component: 'Apurata_Financing/js/view/payment/method-renderer/financing'
            }
        );

        return Component.extend({});
    }
);
