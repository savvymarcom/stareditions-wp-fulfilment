<?php

namespace SavvyWebFulfilment\Admin;

use WC_Order;
use WP_Post;

class OrderMetaFields {

    private SavvyPluginConfig $savvyPluginConfig;
    private string            $brandName;

    public function __construct( SavvyPluginConfig $savvyPluginConfig ) {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName         = $this->savvyPluginConfig->getSavvyBrandName();

        // Hook globally, then bail unless we're on the WC edit‐order screen
        add_action( 'add_meta_boxes', [ $this, 'addOrderPageMetaBox' ] );
    }

    /**
     * Only register our box on the WooCommerce 'Edit Order' screen.
     */
    public function addOrderPageMetaBox(): void {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();
        $wc_screen = function_exists( 'wc_get_page_screen_id' )
            ? wc_get_page_screen_id( 'shop-order' )
            // fallback in very old WooCommerce
            : 'woocommerce_page_wc-orders';

        if ( ! $screen || $screen->id !== $wc_screen ) {
            return;
        }

        add_meta_box(
            'savvy_web_order_tracking',
            sprintf( '%s Order Tracking', esc_html( $this->brandName ) ),
            [ $this, 'renderSavvyTrackingMetaBox' ],
            $wc_screen,
            'side',
            'high'
        );
    }

    /**
     * The meta-box callback. WP will pass us *either*:
     *  - a \WP_Post (on classic post screens), or
     *  - a WC_Order instance (on WC’s custom order page).
     *
     * We detect which we got, then never access $order->ID directly.
     *
     * @param WP_Post|WC_Order $object
     */
    public function renderSavvyTrackingMetaBox( $object ): void {
        // Resolve the real WC_Order object
        if ( $object instanceof WC_Order ) {
            $order = $object;
        } elseif ( $object instanceof WP_Post ) {
            $order = wc_get_order( $object->ID );
        } else {
            echo '<p><em>Order not found.</em></p>';
            return;
        }

        if ( ! $order instanceof WC_Order ) {
            echo '<p><em>Order not found.</em></p>';
            return;
        }

        // Always use the getters—never $order->ID or $order->order_date directly.
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

            // Build URL by concatenation so no {$order->get_id()} in quotes
            $resendUrl = admin_url(
                'admin-post.php?action=savvy_resend_fulfilment&order_id='
                . $order->get_id()
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
