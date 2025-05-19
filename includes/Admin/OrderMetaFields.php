<?php

namespace SavvyWebFulfilment\Admin;

use WC_Order;

class OrderMetaFields {

    private SavvyPluginConfig $savvyPluginConfig;
    private string            $brandName;

    public function __construct( SavvyPluginConfig $savvyPluginConfig ) {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName         = $this->savvyPluginConfig->getSavvyBrandName();

        // WooCommerce’s internal screen ID for the Orders page:
        $screen = function_exists( 'wc_get_page_screen_id' )
            ? wc_get_page_screen_id( 'shop-order' )
            : 'woocommerce_page_wc-orders';

        // Hook only on that screen, passing WP_Screen + the $order object
        add_action(
            "add_meta_boxes_{$screen}",
            [ $this, 'addOrderPageMetaBox' ],
            10,
            2
        );
    }

    /**
     * Register the meta box on the edit‐order screen.
     *
     * @param \WP_Screen $screen Current admin screen object
     * @param WC_Order   $order  The order being edited
     */
    public function addOrderPageMetaBox( $screen, $order ) {
        add_meta_box(
            'savvy_web_order_tracking',
            sprintf( '%s Order Tracking', esc_html( $this->brandName ) ),
            [ $this, 'renderSavvyTrackingMetaBox' ],
            $screen->id,   // e.g. 'woocommerce_page_wc-orders'
            'side',
            'high'
        );
    }

    /**
     * Output our custom tracking/status info.
     *
     * @param WC_Order $order The current order object
     */
    public function renderSavvyTrackingMetaBox( WC_Order $order ): void {
        // sanity check
        if ( ! $order instanceof WC_Order ) {
            echo '<p><em>Order not found.</em></p>';
            return;
        }

        // Always use getters!
        $status       = $order->get_meta( '_savvy_fulfilment_status', true ) ?: 'Not Sent';
        $lastAttempt  = $order->get_meta( '_savvy_fulfilment_last_attempt', true );
        $errorMessage = $order->get_meta( '_savvy_fulfilment_error_message', true );
        $trackingNo   = $order->get_meta( '_savvy_fulfilment_tracking_number', true );
        $carrierName  = $order->get_meta( '_savvy_fulfilment_carrier', true );

        echo '<p><strong>Status:</strong> ' . esc_html( ucfirst( $status ) ) . '</p>';

        if ( $lastAttempt ) {
            echo '<p><strong>Last Attempt:</strong><br>' . esc_html( $lastAttempt ) . '</p>';
        }

        if ( $errorMessage ) {
            echo '<p style="color:red;"><strong>Error:</strong><br>' . esc_html( $errorMessage ) . '</p>';

            // Proper concatenation rather than in‐string interpolation
            $resendUrl = admin_url(
                'admin-post.php?action=savvy_resend_fulfilment&order_id=' . $order->get_id()
            );
            echo '<p><a href="' . esc_url( $resendUrl ) . '" class="button">Resend to Fulfilment</a></p>';
        }

        if ( $trackingNo ) {
            echo '<p><strong>Tracking Number:</strong><br>' . esc_html( $trackingNo ) . '</p>';
        }

        if ( $carrierName ) {
            echo '<p><strong>Carrier Name:</strong><br>' . esc_html( $carrierName ) . '</p>';
        }
    }
}
