/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * rendering front
 */
/* @api */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'acceptcoin',
            component: 'SoftileLimited_Acceptcoin/js/view/payment/method-renderer/acceptcoin-method'
        }
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
