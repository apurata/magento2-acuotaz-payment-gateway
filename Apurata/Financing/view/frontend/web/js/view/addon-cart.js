define(
    [
        'uiComponent',
        'jquery',
        'Magento_Customer/js/customer-data'
    ],
    function (Component, $, customerData) {
        'use strict';
        var total = "";
        var id = ".acuotaz-add-on-minicart";
        try {
            var cart = customerData.get('cart');
            var cart_data = customerData.get('cart-data');
            var count = cart().summary_count;
            var cart_total = cart().subtotalAmount;
        } catch (error) {}
        // Update items in cart
        if (cart) {
            cart.subscribe(function () {
                if (cart_data().totals === undefined || cart_data().totals === null) return;
                if (cart().summary_count !== count || cart_data().totals.grand_total != cart_total) {
                    requestaddon();
                }
            });
        }
        // Add estimate shipping
        if (cart_data) {
            cart_data.subscribe(function () {
                var totals = cart_data().totals;
                if (totals && totals.grand_total != cart_total) {
                    total = "&total=" + totals.grand_total;
                    id = "#acuotaz-add-on-cart"
                    requestaddon();
                }
            });
        }
        function requestaddon() {
            $.get( window.BASE_URL + "apuratafinancing/order/requestaddon?page=cart" + total,
                function (data) {
                    $(id).html(data.addon);
                });
        }
        return Component.extend({
            defaults: {
                template: 'Apurata_Financing/addon'
            },
            getAddOn: requestaddon
        });
    }
);
