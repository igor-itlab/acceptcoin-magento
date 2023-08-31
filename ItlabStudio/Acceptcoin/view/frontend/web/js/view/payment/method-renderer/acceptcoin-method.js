/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Setting template file to show payment form on checkout page
 */
/* @api */
define([
    "Magento_Checkout/js/view/payment/default",
    "Magento_Checkout/js/action/redirect-on-success"
], function (Component) {
    "use strict";

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: "ItlabStudio_Acceptcoin/payment/acceptcoin"
        },

        afterPlaceOrder: function () {
            fetch(`${window.location.origin}/rest/default/V1/acceptcoin/get-iframe`, {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            }).then(response => response.json()).then(data => {
                console.log(data);
                let iframeBody = document.getElementById("acceptcoin-iframe-body");
                let iframe = JSON.parse(data);

                iframeBody.innerHTML = `<iframe class="iframe" src=${iframe}></iframe>`;
            });
        },

        getCode: function () {
            return "acceptcoin";
        },

        getData: function () {
            return {
                "method": this.item.method
            };
        },

        getAcceptCoinLogo: function () {
            return window.checkoutConfig.payment.acceptcoin.acceptCoinIcon;
        }
    });
});
