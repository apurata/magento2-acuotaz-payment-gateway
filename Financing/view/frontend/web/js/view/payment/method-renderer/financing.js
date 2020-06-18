define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (
        $,
        Component,
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
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                return this.getCode() === this.isChecked();
            },

            /**
             * Place the order
             * 
             * @param {Object} data
             */
            placeOrderClick: function () {
                var self = this;
                $.get(this.financingIntentUrl, {}, function(response) {

                    if (!response || !response.financingIntent) {
                        messageList.addErrorMessage({
                            message: $t('An error occurred generating the financing intent.')
                        });
                        return;
                    }
                    
                    var keys = Object.keys(response.financingIntent)
                    self.financingCreationUrl += '?pos_client_id=' + self.apurataClientId;
                    for (var key of keys) {
                        var param = '&' + key + '=' + response.financingIntent[key];
                        console.log(param)
                        self.financingCreationUrl += param; 
                    }
                    /* console.log(self.financingCreationUrl) */
                    window.location.replace(self.financingCreationUrl);
                });
            },
        });
    }
);