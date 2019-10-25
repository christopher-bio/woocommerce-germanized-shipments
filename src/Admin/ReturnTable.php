<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ReturnShipment;
use WP_List_Table;
use Vendidero\Germanized\Shipments\ShipmentQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 * @package Vendidero/Germanized/Shipments\Admin
 */
class ReturnTable extends Table {

	protected function get_custom_columns() {
		$columns = array();

		$columns['cb']         = '<input type="checkbox" />';
		$columns['title']      = _x( 'Title', 'shipments', 'woocommerce-germanized-shipments' );
		$columns['date']       = _x( 'Date', 'shipments', 'woocommerce-germanized-shipments' );
		$columns['status']     = _x( 'Status', 'shipments', 'woocommerce-germanized-shipments' );
		$columns['items']      = _x( 'Items', 'shipments', 'woocommerce-germanized-shipments' );
		$columns['sender']     = _x( 'Sender', 'shipments', 'woocommerce-germanized-shipments' );
		$columns['shipment']   = _x( 'Shipment', 'shipments', 'woocommerce-germanized-shipments' );
		$columns['actions']    = _x( 'Actions', 'shipments', 'woocommerce-germanized-shipments' );

		return $columns;
	}

	protected function get_custom_actions( $shipment, $actions ) {

		if ( isset( $actions['shipped'] ) ) {
			unset( $actions['shipped'] );
		}

		if ( ! $shipment->has_status( 'delivered' ) ) {
			$actions['received'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzd_update_shipment_status&status=delivered&shipment_id=' . $shipment->get_id() ), 'update-shipment-status' ),
				'name'   => _x( 'Delivered', 'shipments', 'woocommerce-germanized-shipments' ),
				'action' => 'delivered',
			);
		}

		return $actions;
	}

	public function get_main_page() {
		return 'admin.php?page=wc-gzd-return-shipments';
	}

	protected function get_custom_bulk_actions( $actions ) {
		return $actions;
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param ReturnShipment $shipment The current shipment object.
	 */
	public function column_sender( $shipment ) {
		echo '<address>' . $shipment->get_formatted_sender_address() . '</address>';
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param ReturnShipment $shipment The current shipment object.
	 */
	public function column_shipment( $shipment ) {
		if ( ( $parent = $shipment->get_parent() ) ) {
			echo '<a href="' . $parent->get_edit_shipment_url() . '">' . $parent->get_shipment_number() . '</a>';
		} else {
			echo '&ndash;';
		}
	}
}