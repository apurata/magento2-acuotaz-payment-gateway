define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (
        $,
        Component,
        redirectOnSuccessAction,
        messageList,
        $t
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Apurata_Financing/payment/financing'
            },

            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                var self = this;
                self._super();
                return self;
            },
            
            /**
             * Initialize Apurata Financing element
             */
            initApurataFinancing: function () {
                var config = window.checkoutConfig.payment[this.getCode()];
                console.log(config);
                if (!config) {
                    return;
                }

                this.financingIntentUrl = config.financingIntentUrl;
                this.financingCreationUrl = config.financingCreationUrl;
                this.apurataClientId = config.apurataClientId;

                var r = new XMLHttpRequest();
                r.open("GET", "https://apurata.com/pos/info-steps", true);
                r.onreadystatechange = function () {
                if (r.readyState != 4 || r.status != 200) return;
                var elem = document.getElementById("apurata-pos-steps");
                elem.innerHTML = r.responseText;
                };
                r.send();
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                return this.getCode() === this.isChecked();
            },


            afterPlaceOrder: function () {
                redirectOnSuccessAction.redirectUrl = this.financingIntentUrl + '?pos_client_id=' + this.apurataClientId;
                this.redirectAfterPlaceOrder = true;
            },
        });
    }
);