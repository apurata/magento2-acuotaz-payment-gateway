define(
    [
        'uiComponent',
        'jquery'
    ],
    function (Component, $) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Apurata_Financing/addon'
            },

            getAddOn: function () {
                $.get( window.BASE_URL + "apuratafinancing/order/requestaddon", function( data ) {
                    var elems = document.getElementsByClassName("acuotaz-add-on");
                    for (let index = 0; index < elems.length; ++index){
                        elems[index].innerHTML = data.addon;
                    }
                  });
            }

        });
    }
);