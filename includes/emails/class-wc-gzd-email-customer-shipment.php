<?php
/**
 * Class WC_GZD_Email_Customer_Shipment file.
 *
 * @package Vendidero/Germanized/Shipments/Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;

if ( ! class_exists( 'WC_GZD_Email_Customer_Shipment', false ) ) :

    /**
     * Customer Shipment notification.
     *
     * Shipment notification are sent as soon as a shipment is marked as shipped.
     *
     * @class    WC_GZD_Email_Customer_Shipment
     * @version  1.0.0
     * @package  Vendidero/Germanized/Shipments/Emails
     * @extends  WC_Email
     */
    class WC_GZD_Email_Customer_Shipment extends WC_Email {

        /**
         * Shipment.
         *
         * @var Shipment|bool
         */
        public $shipment;

        /**
         * Is the order partial shipped?
         *
         * @var bool
         */
        public $partial_shipment;

        /**
         * Constructor.
         */
        public function __construct() {
            $this->customer_email = true;
            $this->id             = 'customer_shipment';
            $this->title          = _x( 'Order shipped', 'shipments', 'woocommerce-germanized-shipments' );
            $this->description    = __( 'Shipment notifications are sent to the customer when a shipment gets shipped.', 'woocommerce' );

            $this->template_html  = 'emails/customer-shipment.php';
            $this->template_plain = 'emails/plain/customer-shipment.php';
            $this->template_base  = Package::get_path() . '/templates/';

            $this->placeholders   = array(
                '{site_title}'      => $this->get_blogname(),
                '{shipment_number}' => '',
                '{order_number}'    => '',
                '{order_date}'      => '',
                '{date_sent}'       => '',
            );

            // Triggers for this email.
            add_action( 'woocommerce_gzd_shipment_status_draft_to_shipped_notification', array( $this, 'trigger' ), 10 );
            add_action( 'woocommerce_gzd_shipment_status_processing_to_shipped_notification', array( $this, 'trigger' ), 10 );

            // Call parent constructor.
            parent::__construct();
        }

        /**
         * Get email subject.
         *
         * @param bool $partial Whether it is a partial refund or a full refund.
         * @since  3.1.0
         * @return string
         */
        public function get_default_subject( $partial = false ) {
            if ( $partial ) {
                return _x( 'Your {site_title} order #{order_number} has been partially shipped', 'shipments', 'woocommerce-germanized-shipments' );
            } else {
                return _x( 'Your {site_title} order #{order_number} has been shipped', 'shipments', 'woocommerce-germanized-shipments' );
            }
        }

        /**
         * Get email heading.
         *
         * @param bool $partial Whether it is a partial refund or a full refund.
         * @since  3.1.0
         * @return string
         */
        public function get_default_heading( $partial = false ) {
            if ( $partial ) {
                return _x( 'Partial shipment to your order: {order_number}', 'shipments', 'woocommerce-germanized-shipments' );
            } else {
                return _x( 'Shipment to your order: {order_number}', 'shipments', 'woocommerce-germanized-shipments' );
            }
        }

        /**
         * Get email subject.
         *
         * @return string
         */
        public function get_subject() {
            if ( $this->partial_shipment ) {
                $subject = $this->get_option( 'subject_partial', $this->get_default_subject( true ) );
            } else {
                $subject = $this->get_option( 'subject_full', $this->get_default_subject() );
            }

	        /**
	         * Filter to adjust the email subject for a shipped Shipment.
	         *
	         * @param string                         $subject The subject.
	         * @param WC_GZD_Email_Customer_Shipment $email The email instance.
	         *
	         * @since 3.0.0
	         */
            return apply_filters( 'woocommerce_email_subject_customer_shipment', $this->format_string( $subject ), $this->object );
        }

        /**
         * Get email heading.
         *
         * @return string
         */
        public function get_heading() {
            if ( $this->partial_shipment ) {
                $heading = $this->get_option( 'heading_partial', $this->get_default_heading( true ) );
            } else {
                $heading = $this->get_option( 'heading_full', $this->get_default_heading() );
            }

	        /**
	         * Filter to adjust the email heading for a shipped Shipment.
	         *
	         * @param string                         $heading The heading.
	         * @param WC_GZD_Email_Customer_Shipment $email The email instance.
	         *
	         * @since 3.0.0
	         */
            return apply_filters( 'woocommerce_email_heading_customer_shipment', $this->format_string( $heading ), $this->object );
        }

        /**
         * Trigger.
         *
         * @param int  $shipment_id Shipment ID.
         */
        public function trigger( $shipment_id ) {
            $this->setup_locale();

            $this->partial_shipment = false;

            if ( $this->shipment = wc_gzd_get_shipment( $shipment_id ) ) {

                $this->placeholders['{shipment_number}'] = $this->shipment->get_shipment_number();

                if ( $order_shipment = wc_gzd_get_shipment_order( $this->shipment->get_order() ) ) {

                    $this->object = $this->shipment->get_order();

                    if ( $order_shipment->needs_shipping() || sizeof( $order_shipment->get_simple_shipments() ) > 1 ) {
                        $this->partial_shipment = true;
                    }

                    $this->recipient                      = $order_shipment->get_order()->get_billing_email();
                    $this->placeholders['{order_date}']   = wc_format_datetime( $order_shipment->get_order()->get_date_created() );
                    $this->placeholders['{order_number}'] = $order_shipment->get_order()->get_order_number();

                    if ( $this->shipment->get_date_sent() ) {
                        $this->placeholders['{date_sent}'] = wc_format_datetime( $this->shipment->get_date_sent() );
                    }
                }
            }

            $this->id = $this->partial_shipment ? 'customer_partial_shipment' : 'customer_shipment';

            if ( $this->is_enabled() && $this->get_recipient() ) {
                $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            }

            $this->restore_locale();
        }

        /**
         * Get content html.
         *
         * @return string
         */
        public function get_content_html() {
            return wc_get_template_html(
                $this->template_html, array(
                    'shipment'         => $this->shipment,
                    'order'            => $this->object,
                    'partial_shipment' => $this->partial_shipment,
                    'email_heading'    => $this->get_heading(),
                    'sent_to_admin'    => false,
                    'plain_text'       => false,
                    'email'            => $this,
                )
            );
        }

        /**
         * Get content plain.
         *
         * @return string
         */
        public function get_content_plain() {
            return wc_get_template_html(
                $this->template_plain, array(
                    'shipment'         => $this->shipment,
                    'order'            => $this->object,
                    'partial_shipment' => $this->partial_shipment,
                    'email_heading'    => $this->get_heading(),
                    'sent_to_admin'    => false,
                    'plain_text'       => true,
                    'email'            => $this,
                )
            );
        }

        /**
         * Initialise settings form fields.
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled'         => array(
                    'title'   => _x( 'Enable/Disable', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'    => 'checkbox',
                    'label'   => _x( 'Enable this email notification', 'shipments', 'woocommerce-germanized-shipments' ),
                    'default' => 'yes',
                ),
                'subject_full'    => array(
                    'title'       => _x( 'Full shipment subject', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    /* translators: %s: list of placeholders */
                    'description' => sprintf( _x( 'Available placeholders: %s', 'shipments', 'woocommerce-germanized-shipments' ), '<code>{site_title}, {order_date}, {order_number}, {shipment_number}, {date_sent}</code>' ),
                    'placeholder' => $this->get_default_subject(),
                    'default'     => '',
                ),
                'subject_partial' => array(
                    'title'       => _x( 'Partial shipment subject', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    /* translators: %s: list of placeholders */
                    'description' => sprintf( _x( 'Available placeholders: %s', 'shipments', 'woocommerce-germanized-shipments' ), '<code>{site_title}, {order_date}, {order_number}, {shipment_number}, {date_sent}</code>' ),
                    'placeholder' => $this->get_default_subject( true ),
                    'default'     => '',
                ),
                'heading_full'    => array(
                    'title'       => _x( 'Full shipment email heading', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    /* translators: %s: list of placeholders */
                    'description' => sprintf( _x( 'Available placeholders: %s', 'shipments', 'woocommerce-germanized-shipments' ), '<code>{site_title}, {order_date}, {order_number}, {shipment_number}, {date_sent}</code>' ),
                    'placeholder' => $this->get_default_heading(),
                    'default'     => '',
                ),
                'heading_partial' => array(
                    'title'       => _x( 'Partial shipment email heading', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    /* translators: %s: list of placeholders */
                    'description' => sprintf( _x( 'Available placeholders: %s', 'shipments', 'woocommerce-germanized-shipments' ), '<code>{site_title}, {order_date}, {order_number}, {shipment_number}, {date_sent}</code>' ),
                    'placeholder' => $this->get_default_heading( true ),
                    'default'     => '',
                ),
                'email_type'      => array(
                    'title'       => _x( 'Email type', 'shipments', 'woocommerce-germanized-shipments' ),
                    'type'        => 'select',
                    'description' => _x( 'Choose which format of email to send.', 'shipments', 'woocommerce-germanized-shipments' ),
                    'default'     => 'html',
                    'class'       => 'email_type wc-enhanced-select',
                    'options'     => $this->get_email_type_options(),
                    'desc_tip'    => true,
                ),
            );
        }
    }

endif;

return new WC_GZD_Email_Customer_Shipment();
