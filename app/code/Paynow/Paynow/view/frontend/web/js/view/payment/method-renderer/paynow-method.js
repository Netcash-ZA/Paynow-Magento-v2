/**
 * Copyright � 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url'
    ],
    function ($,
              Component,
              placeOrderAction,
              selectPaymentMethodAction,
              customer,
              checkoutData,
              additionalValidators,
              url)  {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paynow_Paynow/payment/paynow'
            },
            getCode: function() {
                return 'paynow';
            },
            /**
             * Get value of instruction field.
             * @returns {String}
             */
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            isAvailable: function() {
                return quote.totals().grand_total <= 0;
            },
            afterPlaceOrder: function () {
                window.location.replace( url.build(window.checkoutConfig.payment.paynow.redirectUrl.paynow) );
            },
            /** Returns payment acceptance mark link path */
            getPaymentAcceptanceMarkHref: function() {
                return window.checkoutConfig.payment.paynow.paymentAcceptanceMarkHref;
            },
            /** Returns payment acceptance mark image path */
            getPaymentAcceptanceMarkSrc: function() {
                return window.checkoutConfig.payment.paynow.paymentAcceptanceMarkSrc;
            }

        });
    }
);
