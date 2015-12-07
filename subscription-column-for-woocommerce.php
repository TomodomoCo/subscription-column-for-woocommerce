<?php
/*
Plugin Name: Subscription Column for WooCommerce
Plugin URI: https://www.vanpattenmedia.com/
Description: View information about an order's parent subscription on the WooCommerce orders page
Version: 1.0.0
Author: Van Patten Media Inc.
Author URI: https://www.vanpattenmedia.com/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: subcolumnforwoocommerce
*/

namespace Vanpattenmedia;

class SubColumnForWooCommerce {

	public function __construct() {
		// Init
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		// Make sure WooCommerce Subscriptions is installed
		if ( ! class_exists( 'WC_Subscriptions' ) )
			return;

		// Add the custom column
		add_filter( 'manage_shop_order_posts_columns', array( $this, 'shop_order_columns' ), 99 );

		// Set the column content
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_shop_order_columns' ), 99 );
	}

	/**
	 * Add the custom column
	 *
	 * @since 1.0.0
	 * @param $columns array
	 * @return array
	 */
	public function shop_order_columns( $columns ) {
		$columns['order_subscription'] = __( 'Subscription', 'subcolumnforwoocommerce' );

		return $columns;
	}

	/**
	 * Render the column value
	 *
	 * @since 1.0.0
	 * @param $column string
	 */
	public function render_shop_order_columns( $column ) {
		global $post, $woocommerce, $the_order;

		if ( empty( $the_order ) || $the_order->id != $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		switch ( $column ) {
			case 'order_subscription' :

				$subscriptions = wcs_get_subscriptions_for_order( $the_order->id, array( 'order_type' => array( 'parent', 'renewal' ) ) );

				if ( count( $subscriptions ) === 0 )
					return;

				foreach ( $subscriptions as $subscription ) {
					echo '<p><a href="' . get_edit_post_link( $subscription->id ) . '" class="button">' . __( 'View subscription', 'subcolumnforwoocommerce' ) . '</a></p>';
					echo $this->render_subscriptions_info( $subscription );
				}

			break;
		}
	}

	/**
	 * Reimplement subscription price string function
	 *
	 * Get the details of the subscription for use with @see wcs_price_string()
	 *
	 * @return array
	 */
	function get_price_string_details( $subscription, $amount = 0, $display_ex_tax_label = false ) {

		$subscription_details = array(
			'currency'              => $subscription->get_order_currency(),
			'recurring_amount'      => $amount,
			'subscription_period'   => $subscription->billing_period,
			'subscription_interval' => $subscription->billing_interval,
			'display_ex_tax_label'  => $display_ex_tax_label,
		);

		return apply_filters( 'woocommerce_subscription_price_string_details', $subscription_details, $this );
	}

	/**
	 * Render the subscription information
	 * 
	 * @param $subscription WC_Subscription
	 * @return string
	 */
	public function render_subscriptions_info( $subscription ) {
		$data = array(
			'schedule' => array(
				'label' => __( 'Schedule', 'subcolumnforwoocommerce' ),
				'value' => wcs_price_string( $this->get_price_string_details( $subscription, $subscription->get_total() ) ),
			),
			'next_payment' => array(
				'label' => __( 'Next Payment', 'subcolumnforwoocommerce' ),
				'value' => $subscription->get_date_to_display( 'next_payment' ),
			),
		);

		$data = apply_filters( 'subcolumnforwoocommerce_subscription_info', $data, $subscription );

		$content = '';

		foreach ( $data as $item ) {
			$content .= sprintf( '<dt style="margin: 0.3em 0 0"><small style="color: #999">%1$s</small></dt><dd style="margin: 0">%2$s</dd>', $item['label'], $item['value'] );
		}

		return '<dl style="margin: 0.1em 0 0">' . $content . '</dl>';
	}

}

// Get this party started
$subcolumnforwoocommerce = new SubColumnForWooCommerce;
