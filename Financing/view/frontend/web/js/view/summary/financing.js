define(
    [
        'uiComponent'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Apurata_Financing/summary/financing'
            },

            getAddOn: function () {
                console.log('making request of addon')
                console.log(window.checkoutConfig)
                var config = window.checkoutConfig.payment['apurata_financing'];
                console.log('####################################')
                if (!config) {
                    return;
                }

                this.financingAddOnUrl = config.financingAddOnUrl;
                console.log(this.financingAddOnUrl)
                var r = new XMLHttpRequest();
                r.open("GET", this.financingAddOnUrl, true);
                r.onreadystatechange = function () {
                    console.log(r);
                    console.log(r.responseText);
                    if (r.readyState != 4 || r.status != 200) return;
                    var elem = document.getElementById("acuotaz-add-on");
                    elem.innerHTML = r.responseText;
                };
                r.send();
            }

        });
    }
);