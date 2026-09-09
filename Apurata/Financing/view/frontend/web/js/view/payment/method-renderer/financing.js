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
                if (!config) {
                    return;
                }

                this.financingIntentUrl = config.financingIntentUrl;
                this.financingCreationUrl = config.financingCreationUrl;
                this.apurataClientId = config.apurataClientId;

                // Same-origin Magento proxy (avoids CSP connect-src + mixed content)
                var infoStepsUrl = config.financingInfoStepsUrl;
                if (!infoStepsUrl) {
                    return;
                }
                var r = new XMLHttpRequest();
                r.open('GET', infoStepsUrl, true);
                r.onreadystatechange = function () {
                    if (r.readyState != 4 || r.status != 200) return;
                    var elem = document.getElementById('apurata-pos-steps');
                    if (!elem) return;
                    try {
                        var data = JSON.parse(r.responseText);
                        elem.innerHTML = data.info_steps || '';
                    } catch (e) {
                        elem.innerHTML = r.responseText;
                    }
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
