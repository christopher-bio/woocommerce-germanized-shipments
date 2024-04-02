<?php

namespace Vendidero\Germanized\Shipments\ShippingProvider;

use Vendidero\Germanized\Shipments\ShipmentError;

defined( 'ABSPATH' ) || exit;

class PickupLocation {

	protected $code = '';

	protected $type = '';

	protected $label = '';

	protected $latitude = '';

	protected $longitude = '';

	protected $supports_customer_number = false;

	protected $customer_number_is_mandatory = false;

	protected $address = array();

	protected $address_replacement_map = array();

	protected $customer_number_validation_cb = null;

	public function __construct( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'code'                          => '',
				'type'                          => '',
				'label'                         => '',
				'latitude'                      => '',
				'longitude'                     => '',
				'supports_customer_number'      => false,
				'customer_number_is_mandatory'  => false,
				'customer_number_validation_cb' => null,
				'address'                       => array(),
				'address_replacement_map'       => array(),
			)
		);

		if ( empty( $args['code'] ) ) {
			$args['code'] = sanitize_key( $args['label'] );
		}

		if ( empty( $args['code'] ) ) {
			throw new \Exception( 'A pickup location needs a code.', 500 );
		}

		$this->code                          = $args['code'];
		$this->type                          = $args['type'];
		$this->label                         = $args['label'];
		$this->latitude                      = $args['latitude'];
		$this->longitude                     = $args['longitude'];
		$this->supports_customer_number      = wc_string_to_bool( $args['supports_customer_number'] );
		$this->customer_number_is_mandatory  = wc_string_to_bool( $args['customer_number_is_mandatory'] );
		$this->customer_number_validation_cb = $args['customer_number_validation_cb'];
		$this->address                       = wp_parse_args(
			(array) $args['address'],
			array(
				'address_1',
				'city',
				'postcode',
				'country',
			)
		);

		if ( $this->customer_number_validation_cb instanceof \Closure ) {
			throw new \Exception( 'Closures as callbacks for customer number validation are not supported', 500 );
		}

		$this->address_replacement_map = (array) $args['address_replacement_map'];
	}

	public function get_id() {
		return $this->get_code();
	}

	public function get_code() {
		return $this->code;
	}

	public function get_label() {
		return $this->label;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_address() {
		return $this->address;
	}

	public function supports_customer_number() {
		return $this->supports_customer_number;
	}

	public function customer_number_is_mandatory() {
		return $this->customer_number_is_mandatory;
	}

	/**
	 * @param $customer_number
	 *
	 * @return bool|\WP_Error
	 */
	public function customer_number_is_valid( $customer_number ) {
		$is_valid = $this->customer_number_is_mandatory() ? ! empty( $customer_number ) : true;

		if ( null !== $this->customer_number_validation_cb ) {
			$is_valid = call_user_func_array( $this->customer_number_validation_cb, array( $customer_number, $this ) );
		}

		return $is_valid;
	}

	public function get_latitude() {
		return $this->latitude;
	}

	public function get_longitude() {
		return $this->longitude;
	}

	public function get_formatted_address( $separator = ', ' ) {
		$address = $this->get_address();

		if ( empty( $address['company'] ) ) {
			$address['company'] = $this->get_label();
		}

		return WC()->countries->get_formatted_address( $address, $separator );
	}

	public function get_address_replacement_map() {
		return $this->address_replacement_map;
	}

	public function get_address_replacements() {
		$replacements              = array();
		$location_address          = $this->get_address();
		$location_address['label'] = $this->get_label();
		$location_address['code']  = $this->get_code();

		foreach ( $this->get_address_replacement_map() as $address_key => $location_address_key ) {
			if ( isset( $location_address[ $location_address_key ] ) ) {
				$replacements[ $address_key ] = $location_address[ $location_address_key ];
			}
		}

		return $replacements;
	}

	public function replace_address( $object ) {
		foreach ( $this->get_address_replacements() as $address_field => $address_value ) {
			$setter = "set_shipping_{$address_field}";

			if ( is_callable( array( $object, $setter ) ) ) {
				$object->{$setter}( $address_value );
			}
		}
	}

	public function get_data() {
		return array(
			'code'                         => $this->get_code(),
			'type'                         => $this->get_type(),
			'label'                        => $this->get_label(),
			'latitude'                     => $this->get_latitude(),
			'longitude'                    => $this->get_longitude(),
			'supports_customer_number'     => $this->supports_customer_number(),
			'customer_number_is_mandatory' => $this->customer_number_is_mandatory(),
			'address'                      => $this->get_address(),
			'address_replacement_map'      => $this->get_address_replacement_map(),
			'address_replacements'         => $this->get_address_replacements(),
			'formatted_address'            => $this->get_formatted_address(),
		);
	}
}
