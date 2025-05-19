<?php

namespace SavvyWebFulfilment\Admin;

use WC_Order;

class OrderMetaFields {

    private SavvyPluginConfig $savvyPluginConfig;
    private string            $brandName;

    public function __construct( SavvyPluginConfig $savvyPluginConfig ) {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName         = $this->savvyPluginConfig->getSavvyBrandName();

        // hook into the general meta-box registration
        add_action( 'add_meta_boxes', [ $this, 'addOrderPageMetaBox' ] );
    }

    /**
     * Fire on every screen; bail unless we're on the WC 'edit order' page.
     */
    public function addOrderPageMetaBox(): void {
        // determine the WC order-edit screen ID
        $wc_screen = function_exists( 'wc_get_page_screen_id' )
            ? wc_get_page_screen_id( 'shop-order' )           // e.g. 'woocommerce_page_wc-orders'
            : 'woocommerce_page_wc-orders';

        // bail if we're not on that screen
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== $wc_screen ) {
            return;
        }

        // now register our box
        add_meta_box(
            'savvy_web_order_tracking',
            sprintf( '%s Order Tracking', esc_html( $this->brandName ) ),
            [ $this, 'renderSavvyTrackingMetaBox' ],
            $wc_screen,  // show on this screen
            'side',      // context
            'high'       // priority
        );
    }

    /**
     * Render the contents of our Order Tracking box.
     *
     * @param \WP_Post $post
     */
    public function renderSavvyTrackingMetaBox( $post ): void {
        $order = wc_get_order( $post->ID );

        if ( ! $order instanceof WC_Order ) {
            echo '<p><em>Order not found.</em></p>';
            return;
        }

        // get all our meta via getters
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
