<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Settings {

	public static function get_section_description( $section ) {
		return '';
	}

	public static function get_pointers( $section ) {
		$pointers = array();
		$next_url = admin_url( 'admin.php?page=wc-settings&tab=germanized-emails&tutorial=yes' );

		if ( \Vendidero\Germanized\DHL\Package::has_dependencies() ) {
			$next_url = admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&tutorial=yes' );
		}

		if ( '' === $section ) {
			$pointers = array(
				'pointers' => array(
					'menu'             => array(
						'target'       => '.wc-gzd-settings-breadcrumb .page-title-action',
						'next'         => 'default',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Manage shipments', 'shipments', 'woocommerce-germanized-shipments' ) . '</h3>' .
							              '<p>' . esc_html_x( 'To view all your existing shipments in a list you might follow this link or click on the shipments link within the WooCommerce sub-menu.', 'shipments', 'woocommerce-germanized-shipments' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'default'          => array(
						'target'       => '#woocommerce_gzd_shipments_notify_enable-toggle',
						'next'         => 'auto',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'E-Mail Notification', 'shipments', 'woocommerce-germanized-shipments' ) . '</h3>' .
							              '<p>' . esc_html_x( 'By enabling this option customers receive an email notification as soon as a shipment is marked as shipped.', 'shipments', 'woocommerce-germanized-shipments' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'auto'          => array(
						'target'       => '#woocommerce_gzd_shipments_auto_enable-toggle',
						'next'         => '',
						'next_url'     => $next_url,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Automation', 'shipments', 'woocommerce-germanized-shipments' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Decide whether you want to automatically create shipments to orders reaching a specific status. You can always adjust your shipments by manually editing the shipment within the edit order screen.', 'shipments', 'woocommerce-germanized-shipments' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}

	protected static function get_general_settings() {

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'shipments_options' ),

			array(
				'title' 	        => _x( 'Notify', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc' 		        => _x( 'Notify customers about new shipments.', 'shipments', 'woocommerce-germanized-shipments' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Notify customers by email as soon as a shipment is marked as shipped. %s the notification email.', 'shipments', 'woocommerce-germanized-shipments' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_gzd_email_customer_shipment' ) . '" target="_blank">' . _x( 'Manage', 'shipments notification', 'woocommerce-germanized-shipments' ) .'</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_shipments_notify_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_options' ),

			array( 'title' => _x( 'Automation', 'shipments', 'woocommerce-germanized-shipments' ), 'type' => 'title', 'id' => 'shipments_auto_options' ),

			array(
				'title' 	        => _x( 'Enable', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc' 		        => _x( 'Automatically create shipments for orders.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Order statuses', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip' 		    => _x( 'Create shipments as soon as the order reaches one of the following status(es).', 'shipments', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_statuses',
				'default'	        => array( 'wc-processing', 'wc-on-hold' ),
				'class' 	        => 'wc-enhanced-select-nostd',
				'options'           => wc_get_order_statuses(),
				'type'              => 'multiselect',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_auto_enable' => '',
					'data-placeholder' => _x( 'On new order creation', 'shipments', 'woocommerce-germanized-shipments' )
				),
			),

			array(
				'title' 	        => _x( 'Default status', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc_tip' 		    => _x( 'Choose a default status for the automatically created shipment.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'woocommerce_gzd_shipments_auto_default_status',
				'default'	        => 'gzd-processing',
				'class' 	        => 'wc-enhanced-select',
				'options'           => wc_gzd_get_shipment_statuses(),
				'type'              => 'select',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_shipments_auto_enable' => '',
				),
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_auto_options' ),

			array( 'title' => _x( 'Customer Account', 'shipments', 'woocommerce-germanized-shipments' ), 'type' => 'title', 'id' => 'shipments_customer_options' ),

			array(
				'title' 	        => _x( 'List', 'shipments', 'woocommerce-germanized-shipments' ),
				'desc' 		        => _x( 'List shipments on customer account order screen.', 'shipments', 'woocommerce-germanized-shipments' ),
				'id' 		        => 'woocommerce_gzd_shipments_customer_account_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_customer_options' ),

			array( 'title' => _x( 'Return Address', 'shipments', 'woocommerce-germanized-shipments' ), 'type' => 'title', 'id' => 'shipments_return_options' ),

			array(
				'title'             => _x( 'First Name', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_first_name',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Last Name', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_last_name',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Company', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_company',
				'default'           => get_bloginfo( 'name' ),
			),

			array(
				'title'             => _x( 'Address 1', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_address_1',
				'default'           => get_option( 'woocommerce_store_address' ),
			),

			array(
				'title'             => _x( 'Address 2', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_address_2',
				'default'           => get_option( 'woocommerce_store_address_2' ),
			),

			array(
				'title'             => _x( 'City', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_city',
				'default'           => get_option( 'woocommerce_store_city' ),
			),

			array(
				'title'             => _x( 'Country / State', 'shipments', 'woocommerce-germanized-shipments' ),
				'id'                => 'woocommerce_gzd_shipments_return_address_country',
				'default'           => get_option( 'woocommerce_default_country' ),
				'type'              => 'single_select_country',
				'desc_tip'          => true,
			),

			array(
				'title'             => _x( 'Postcode', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_shipments_return_address_postcode',
				'default'           => get_option( 'woocommerce_store_postcode' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipments_return_options' ),
		);

		return $settings;
	}

	public static function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = self::get_general_settings();
		}

		return $settings;
	}

	public static function get_sections() {
		return array();
	}
}
