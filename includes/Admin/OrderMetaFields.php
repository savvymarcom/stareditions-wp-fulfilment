<?php

namespace SavvyWebFulfilment\Admin;

use WP_Post;
use WC_Order;

class OrderMetaFields {

    private SavvyPluginConfig $savvyPluginConfig;
    private string             $brandName;

    public function __construct( SavvyPluginConfig $savvyPluginConfig ) {
        $this->savvyPluginConfig = $savvyPluginConfig;
        $this->brandName         = $this->savvyPluginConfig->getSavvyBrandName();

        // Only on the 'edit order' screen:
        add_action(
            'add_meta_boxes_shop_order',
            [ $this, 'addOrderPageMetaBox' ],
            10,
            2
        );
    }

    /**
     * Register our meta box on the shop_order edit screen.
     *
     * @param string   $postType
     * @param WP_Post  $post
     */
    public function addOrderPageMetaBox( string $postType, WP_Post $post ): void {
        add_meta_box(
            'savvy_web_order_tracking',
            sprintf( '%s Order Tracking', esc_html( $this->brandName ) ),
            [ $this, 'renderSavvyTrackingMetaBox' ],
            $postType,   // 'shop_order'
            'side',
            'high'
        );
    }

    /**
     * Output the tracking / error status for a given order.
     *
     * @param WP_Post $post
     */
    public function renderSavvyTrackingMetaBox( WP_Post $post ): void {
        $order = wc_get_order( $post->ID );

        if ( ! $order instanceof WC_Order ) {
            echo '<p><em>Order not found.</em></p>';
            return;
        }

        // Pull out our meta values (always use getters!)
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
