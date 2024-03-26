<?php
namespace Vendidero\Germanized\Shipments\Blocks;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Vendidero\Germanized\Shipments\Package;

final class Checkout {

	public function __construct() {
		$this->register_endpoint_data();
		$this->register_integrations();
		$this->register_validation_and_storage();
	}

	private function register_validation_and_storage() {
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function( $order, $request ) {
				$this->validate_checkout_data( $order, $request );
			},
			10,
			2
		);
	}

	private function has_checkout_data( $param, $request ) {
		$request_data = isset( $request['extensions']['woocommerce-gzd-shipments'] ) ? (array) $request['extensions']['woocommerce-gzd-shipments'] : array();

		return isset( $request_data[ $param ] ) && null !== $request_data[ $param ];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	private function get_checkout_data_from_request( $request ) {
		$data = array_filter( (array) wc_clean( $request['extensions']['woocommerce-gzd-shipments'] ) );
		$data = wp_parse_args(
			$data,
			array(
				'pickup_location'                 => '',
				'pickup_location_customer_number' => '',
			)
		);

		return $data;
	}

	/**
	 * @param \WC_Order $order
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 */
	private function validate_checkout_data( $order, $request ) {
		$gzd_data = $this->get_checkout_data_from_request( $request );

		if ( $this->has_checkout_data( 'pickup_location', $request ) ) {
			$pickup_location_code            = $gzd_data['pickup_location'];
			$pickup_location_customer_number = $gzd_data['pickup_location_customer_number'];
			$needs_customer_number           = false;
			$is_valid                        = false;
			$pickup_location                 = false;
			$address_data                    = array(
				'country'   => $order->get_shipping_country(),
				'postcode'  => $order->get_shipping_postcode(),
				'city'      => $order->get_shipping_city(),
				'address_1' => $order->get_shipping_address_1(),
			);

			if ( $provider = wc_gzd_get_order_shipping_provider( $order ) ) {
				if ( is_a( $provider, 'Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
					$pickup_location       = $provider->get_pickup_location_by_code( $pickup_location_code, $address_data );
					$is_valid              = $provider->is_valid_pickup_location( $pickup_location_code, $address_data );
					$needs_customer_number = $provider->pickup_location_needs_customer_number( $pickup_location_code, $address_data );

					if ( $is_valid && $needs_customer_number ) {
						if ( ! $provider->is_valid_pickup_location_customer_number( $pickup_location_customer_number ) ) {
							throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pickup_location_customer_number_invalid', _x( 'Sorry, your pickup location customer number is invalid.', 'shipments', 'woocommerce-germanized-shipments' ), 400 );
						}
					}
				}
			}

			if ( $is_valid && $pickup_location ) {
				$pickup_location_code = $pickup_location['code'];

				foreach ( $pickup_location['address_replacements'] as $address_field => $replacement ) {
					$setter = "set_shipping_{$address_field}";

					if ( is_callable( array( $order, $setter ) ) ) {
						$order->{ $setter }( $replacement );
					}
				}

				$order->update_meta_data( '_pickup_location_code', $pickup_location_code );

				if ( $needs_customer_number ) {
					$order->update_meta_data( '_pickup_location_customer_number', $pickup_location_customer_number );
				}

				/**
				 * Persist customer changes for logged-in customers.
				 */
				if ( $order->get_customer_id() ) {
					$wc_customer = new \WC_Customer( $order->get_customer_id() );

					$wc_customer->update_meta_data( 'pickup_location_code', $pickup_location_code );

					foreach ( $pickup_location['address_replacements'] as $address_field => $replacement ) {
						$setter = "set_shipping_{$address_field}";

						if ( is_callable( array( $wc_customer, $setter ) ) ) {
							$wc_customer->{ $setter }( $replacement );
						}
					}

					if ( $needs_customer_number ) {
						$wc_customer->update_meta_data( 'pickup_location_customer_number', $pickup_location_customer_number );
					}

					$wc_customer->save();
				}

				$customer = wc()->customer;
				$customer->update_meta_data( 'pickup_location_code', $pickup_location_code );

				foreach ( $pickup_location['address_replacements'] as $address_field => $replacement ) {
					$setter = "set_shipping_{$address_field}";

					if ( is_callable( array( $customer, $setter ) ) ) {
						$customer->{ $setter }( $replacement );
					}
				}

				if ( $needs_customer_number ) {
					$customer->update_meta_data( 'pickup_location_customer_number', $pickup_location_customer_number );
				}
				$customer->save();
			} else {
				throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'pickup_location_unknown', _x( 'Sorry, your current pickup location is not supported.', 'shipments', 'woocommerce-germanized-shipments' ), 400 );
			}
		}
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( Package::container()->get( Integrations\CheckoutPickupLocationSelect::class ) );
			}
		);
	}

	/**
	 * Use woocommerce-gzd-shipments as namespace to not conflict with the
	 * woocommerce-germanized-shipments textdomain which might get replaced within js files
	 * while bundling the package.
	 *
	 * @return void
	 */
	private function register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-gzd-shipments',
				'data_callback'   => function() {
					return $this->get_cart_data();
				},
				'schema_callback' => function () {
					return $this->get_cart_schema();
				},
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-gzd-shipments',
				'schema_callback' => function () {
					return $this->get_checkout_schema();
				},
			)
		);
	}

	private function get_checkout_schema() {
		return array(
			'pickup_location'                 => array(
				'description' => _x( 'Pickup location', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
			'pickup_location_customer_number' => array(
				'description' => _x( 'Pickup location customer number', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'default'     => '',
			),
		);
	}

	private function get_cart_schema() {
		return array(
			'pickup_location_delivery_available' => array(
				'description' => _x( 'Whether pickup location delivery is available', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'pickup_locations'                   => array(
				'description' => _x( 'Available pickup locations', 'shipments', 'woocommerce-germanized-shipments' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'code'                  => array(
							'description' => _x( 'The location code.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'title'                 => array(
							'description' => _x( 'The location title.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'lat'                   => array(
							'description' => _x( 'The location latitude.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'long'                  => array(
							'description' => _x( 'The location longitude.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'needs_customer_number' => array(
							'description' => _x( 'Whether the location needs a customer number or not.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'default'     => false,
						),
						'type'                  => array(
							'description' => _x( 'The location type, e.g. locker.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'enum',
							'enum'        => array(
								'locker',
								'shop',
								'servicepoint',
							),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'formatted_address'     => array(
							'description' => _x( 'The location\'s formatted address.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'address_replacements'  => array(
							'description' => _x( 'The location\'s address replacements.', 'shipments', 'woocommerce-germanized-shipments' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'address_1' => array(
										'description' => _x( 'The location address.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
										'default'     => '',
									),
									'address_2' => array(
										'description' => _x( 'The location address 2.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
										'default'     => '',
									),
									'postcode'  => array(
										'description' => _x( 'The location postcode.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'city'      => array(
										'description' => _x( 'The location city.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
									'country'   => array(
										'description' => _x( 'The location country.', 'shipments', 'woocommerce-germanized-shipments' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								),
							),
						),
					),
				),
			),
		);
	}

	private function get_cart_data() {
		$customer     = wc()->customer;
		$provider     = false;
		$is_available = false;
		$locations    = array();

		if ( $shipping_method = wc_gzd_get_current_shipping_provider_method() ) {
			$provider = $shipping_method->get_shipping_provider_instance();
		}

		if ( $provider && is_a( $provider, '\Vendidero\Germanized\Shipments\Interfaces\ShippingProviderAuto' ) ) {
			$address = array(
				'postcode'  => $customer->get_shipping_postcode(),
				'country'   => $customer->get_shipping_country(),
				'address_1' => $customer->get_shipping_address_1(),
				'city'      => $customer->get_shipping_city(),
			);

			$locations    = $provider->get_pickup_locations( $address );
			$is_available = $provider->supports_pickup_location_delivery( $address );
		}

		return array(
			'pickup_location_delivery_available' => $is_available && ! empty( $locations ),
			'pickup_locations'                   => $locations,
		);
	}
}
