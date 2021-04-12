define(
    [
        'uiComponent',
        'jquery',
        'Magento_Customer/js/customer-data'
    ],
    function (Component, $, customerData) {
        'use strict';
        var total = "";
        var id = "#acuotaz-add-on-minicart";
        var cart = customerData.get('cart');
        try {
            var cart_data = customerData.get('cart-data');
            var count = cart().summary_count;
            var cart_total = cart().subtotalAmount;
            var subtotal = cart_data().totals.grand_total;
        } catch (error) {}
        if (cart) {
            cart.subscribe(function () {
                if (cart().summary_count !== count) {
                    requestaddon();
                }
            });
        }
        if (cart_total) {
            cart_data.subscribe(function () {
                if (subtotal != cart_total) {
                    total = "&total=" + cart_data().totals.grand_total;
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
