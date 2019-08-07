<?php

namespace Vendidero\Germanized\Shipments;
use WC_Object_Query;
use WC_Data_Store;
use WP_Meta_Query;
use WP_Date_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract WC Object Query Class
 *
 * Extended by classes to provide a query abstraction layer for safe object searching.
 *
 * @version  3.1.0
 * @package  WooCommerce/Abstracts
 */
class ShipmentQuery extends WC_Object_Query {

    protected $args = array();

    protected $query_fields = array();

    protected $query_from = '';

    protected $query_where = '';

    protected $meta_query = null;

    protected $query_limit = '';

    protected $query_orderby = '';

    protected $request = '';

    protected $results = null;

    protected $total_shipments = 0;

    /**
     * Get the default allowed query vars.
     *
     * @return array
     */
    protected function get_default_query_vars() {
        return array(
            'status'         => array_keys( wc_gzd_get_shipment_statuses() ),
            'limit'          => 10,
            'order_id'       => '',
            'country'        => '',
            'tracking_id'    => '',
            'order'          => 'DESC',
            'orderby'        => 'date_created',
            'return'         => 'objects',
            'page'           => 1,
            'offset'         => '',
            'paginate'       => false,
            'search'         => '',
            'search_columns' => array(),
        );
    }

    /**
     * Get shipments matching the current query vars.
     *
     * @return array|object of WC_GZD_Shipment objects
     *
     * @throws Exception When WC_Data_Store validation fails.
     */
    public function get_shipments() {
        $args    = apply_filters( 'woocommerce_gzd_shipment_query_args', $this->get_query_vars() );
        $args    = WC_Data_Store::load( 'shipment' )->get_query_args( $args );

        $this->query( $args );

        return apply_filters( 'woocommerce_gzd_shipment_query', $this->results, $args );
    }

    public function get_total() {
        return $this->total_shipments;
    }

    /**
     * Query shipments.
     *
     * @param array $query_args
     */
    protected function query( $query_args ) {
        global $wpdb;

        $this->args = $query_args;
        $this->parse_query();
        $this->prepare_query();

        $qv =& $this->args;

        /**
         * Filters the users array before the query takes place.
         *
         * Return a non-null value to bypass WordPress's default user queries.
         * Filtering functions that require pagination information are encouraged to set
         * the `total_users` property of the WP_User_Query object, passed to the filter
         * by reference. If WP_User_Query does not perform a database query, it will not
         * have enough information to generate these values itself.
         *
         * @since 5.1.0
         *
         * @param array|null $results Return an array of user data to short-circuit WP's user query
         *                            or null to allow WP to run its normal queries.
         * @param WP_User_Query $this The WP_User_Query instance (passed by reference).
         */
        $this->results = apply_filters_ref_array( 'woocommerce_gzd_shipments_pre_query', array( null, &$this ) );

        if ( null === $this->results ) {
            $this->request = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

            if ( is_array( $qv['fields'] ) || 'objects' == $qv['fields'] ) {
                $this->results = $wpdb->get_results( $this->request );
            } else {
                $this->results = $wpdb->get_col( $this->request );
            }

            if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
                /**
                 * Filters SELECT FOUND_ROWS() query for the current WP_User_Query instance.
                 *
                 * @since 3.2.0
                 * @since 5.1.0 Added the `$this` parameter.
                 *
                 * @global wpdb $wpdb WordPress database abstraction object.
                 *
                 * @param string $sql         The SELECT FOUND_ROWS() query for the current WP_User_Query.
                 * @param WP_User_Query $this The current WP_User_Query instance.
                 */
                $found_shipments_query = apply_filters( 'woocommerce_gzd_found_shipments_query', 'SELECT FOUND_ROWS()', $this );

                $this->total_shipments = (int) $wpdb->get_var( $found_shipments_query );
            }
        }

        if ( ! $this->results ) {
            return;
        }

        if ( 'objects' == $qv['fields'] ) {
            foreach ( $this->results as $key => $shipment ) {
                $this->results[ $key ] = wc_gzd_get_shipment( $shipment );
            }
        }
    }

    /**
     * Parse the query before preparing it.
     */
    protected function parse_query() {

        if ( isset( $this->args['order_id'] ) ) {
            $this->args['order_id'] = absint( $this->args['order_id'] );
        }

        if ( isset( $this->args['tracking_id'] ) ) {
            $this->args['tracking_id'] = sanitize_key( $this->args['tracking_id'] );
        }

        if ( isset( $this->args['status'] ) ) {
            $this->args['status'] = (array) $this->args['status'];
            $this->args['status'] = array_map( 'sanitize_key', $this->args['status'] );
        }

        if ( isset( $this->args['country'] ) ) {
            $countries = isset( WC()->countries ) ? WC()->countries : false;

            if ( $countries && is_a( $countries, 'WC_Countries' ) ) {

                // Reverse search by country name
                if ( $key = array_search( $this->args['country'], $countries->get_countries() ) ) {
                    $this->args['country'] = $key;
                }
            }

            // Country Code ISO
            $this->args['country'] = strtoupper( substr( $this->args['country'], 0, 2 ) );
        }

        if ( isset( $this->args['search'] ) ) {
            $this->args['search'] = wc_clean( $this->args['search'] );

            if ( ! isset( $this->args['search_columns'] ) ) {
                $this->args['search_columns'] = array();
            }
        }
    }

    /**
     * Prepare the query for DB usage.
     */
    protected function prepare_query() {
        global $wpdb;

        if ( is_array( $this->args['fields'] ) ) {
            $this->args['fields'] = array_unique( $this->args['fields'] );

            $this->query_fields = array();

            foreach ( $this->args['fields'] as $field ) {
                $field                = 'ID' === $field ? 'shipment_id' : sanitize_key( $field );
                $this->query_fields[] = "$wpdb->gzd_shipments.$field";
            }

            $this->query_fields = implode( ',', $this->query_fields );

        } elseif ( 'objects' == $this->args['fields'] ) {
            $this->query_fields = "$wpdb->gzd_shipments.*";
        } else {
            $this->query_fields = "$wpdb->gzd_shipments.shipment_id";
        }

        if ( isset( $this->args['count_total'] ) && $this->args['count_total'] ) {
            $this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
        }

        $this->query_from  = "FROM $wpdb->gzd_shipments";
        $this->query_where = 'WHERE 1=1';

        // order id
        if ( isset( $this->args['order_id'] ) ) {
            $this->query_where .= $wpdb->prepare( ' AND shipment_order_id = %d', $this->args['order_id'] );
        }

        // tracking id
        if ( isset( $this->args['tracking_id'] ) ) {
            $this->query_where .= $wpdb->prepare( " AND shipment_tracking_id IN ('%s')", $this->args['tracking_id'] );
        }

        // country
        if ( isset( $this->args['country'] ) ) {
            $this->query_where .= $wpdb->prepare( " AND shipment_country IN ('%s')", $this->args['country'] );
        }

        // status
        if ( isset( $this->args['status'] ) ) {
            $stati    = $this->args['status'];
            $p_status = array();

            foreach( $stati as $status ) {
                $p_status[] = $wpdb->prepare( "shipment_status = '%s'", $status );
            }

            $where_status = implode( ' OR ', $p_status );

            if ( ! empty( $where_status ) ) {
                $this->query_where .= " AND ($where_status)";
            }
        }

        // Search
        $search = '';

        if ( isset( $this->args['search'] ) ) {
            $search = trim( $this->args['search'] );
        }

        if ( $search ) {

            $leading_wild  = ( ltrim( $search, '*' ) != $search );
            $trailing_wild = ( rtrim( $search, '*' ) != $search );

            if ( $leading_wild && $trailing_wild ) {
                $wild = 'both';
            } elseif ( $leading_wild ) {
                $wild = 'leading';
            } elseif ( $trailing_wild ) {
                $wild = 'trailing';
            } else {
                $wild = false;
            }
            if ( $wild ) {
                $search = trim( $search, '*' );
            }

            $search_columns = array();

            if ( $this->args['search_columns'] ) {
                $search_columns = array_intersect( $this->args['search_columns'], array( 'shipment_id', 'shipment_country', 'shipment_tracking_id', 'shipment_order_id' ) );
            }

            if ( ! $search_columns ) {
                if ( is_numeric( $search ) ) {
                    $search_columns = array( 'shipment_id', 'shipment_order_id' );
                } elseif ( strlen( $search ) === 2 ) {
                    $search_columns = array( 'shipment_country' );
                } else {
                    $search_columns = array( 'shipment_id', 'shipment_country', 'shipment_tracking_id', 'shipment_order_id' );
                }
            }

            /**
             * Filters the columns to search in a WP_User_Query search.
             *
             * The default columns depend on the search term, and include 'user_email',
             * 'user_login', 'ID', 'user_url', 'display_name', and 'user_nicename'.
             *
             * @since 3.6.0
             *
             * @param string[]      $search_columns Array of column names to be searched.
             * @param string        $search         Text being searched.
             * @param WP_User_Query $this           The current WP_User_Query instance.
             */
            $search_columns = apply_filters( 'woocommerce_gzd_shipment_search_columns', $search_columns, $search, $this );

            $this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
        }

        // Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
        if ( ! empty( $this->args['include'] ) ) {
            $include = wp_parse_id_list( $this->args['include'] );
        } else {
            $include = false;
        }

        // Meta query.
        $this->meta_query = new WP_Meta_Query();
        $this->meta_query->parse_query_vars( $this->args );

        if ( ! empty( $this->meta_query->queries ) ) {
            $clauses            = $this->meta_query->get_sql( 'gzd_shipment', $wpdb->gzd_shipments, 'shipment_id', $this );
            $this->query_from  .= $clauses['join'];
            $this->query_where .= $clauses['where'];

            if ( $this->meta_query->has_or_relation() ) {
                $this->query_fields = 'DISTINCT ' . $this->query_fields;
            }
        }

        // sorting
        $this->args['order'] = isset( $this->args['order'] ) ? strtoupper( $this->args['order'] ) : '';
        $order               = $this->parse_order( $this->args['order'] );

        if ( empty( $this->args['orderby'] ) ) {
            // Default order is by 'user_login'.
            $ordersby = array( 'date_created' => $order );
        } elseif ( is_array( $this->args['orderby'] ) ) {
            $ordersby = $this->args['orderby'];
        } else {
            // 'orderby' values may be a comma- or space-separated list.
            $ordersby = preg_split( '/[,\s]+/', $this->args['orderby'] );
        }

        $orderby_array = array();

        foreach ( $ordersby as $_key => $_value ) {
            if ( ! $_value ) {
                continue;
            }

            if ( is_int( $_key ) ) {
                // Integer key means this is a flat array of 'orderby' fields.
                $_orderby = $_value;
                $_order   = $order;
            } else {
                // Non-integer key means this the key is the field and the value is ASC/DESC.
                $_orderby = $_key;
                $_order   = $_value;
            }

            $parsed = $this->parse_orderby( $_orderby );

            if ( ! $parsed ) {
                continue;
            }

            $orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
        }

        // If no valid clauses were found, order by user_login.
        if ( empty( $orderby_array ) ) {
            $orderby_array[] = "shipment_id $order";
        }

        $this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

        // limit
        if ( isset( $this->args['posts_per_page'] ) && $this->args['posts_per_page'] > 0 ) {
            if ( isset( $this->args['offset'] ) ) {
                $this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $this->args['offset'], $this->args['posts_per_page'] );
            } else {
                $this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $this->args['posts_per_page'] * ( $this->args['page'] - 1 ), $this->args['posts_per_page'] );
            }
        }

        if ( ! empty( $include ) ) {
            // Sanitized earlier.
            $ids                = implode( ',', $include );
            $this->query_where .= " AND $wpdb->gzd_shipments.shipment_id IN ($ids)";
        } elseif ( ! empty( $this->args['exclude'] ) ) {
            $ids                = implode( ',', wp_parse_id_list( $this->args['exclude'] ) );
            $this->query_where .= " AND $wpdb->gzd_shipments.shipment_id NOT IN ($ids)";
        }

        // Date queries are allowed for the user_registered field.
        if ( ! empty( $this->args['date_query'] ) && is_array( $this->args['date_query'] ) ) {
            $date_query         = new WP_Date_Query( $this->args['date_query'], 'shipment_date_created' );
            $this->query_where .= $date_query->get_sql();
        }
    }

    /**
     * Used internally to generate an SQL string for searching across multiple columns
     *
     * @since 3.1.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param string $string
     * @param array  $cols
     * @param bool   $wild   Whether to allow wildcard searches. Default is false for Network Admin, true for single site.
     *                       Single site allows leading and trailing wildcards, Network Admin only trailing.
     * @return string
     */
    protected function get_search_sql( $string, $cols, $wild = false ) {
        global $wpdb;

        $searches      = array();
        $leading_wild  = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
        $trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
        $like          = $leading_wild . $wpdb->esc_like( $string ) . $trailing_wild;

        foreach ( $cols as $col ) {
            if ( 'ID' == $col ) {
                $searches[] = $wpdb->prepare( "$col = %s", $string );
            } else {
                $searches[] = $wpdb->prepare( "$col LIKE %s", $like );
            }
        }

        return ' AND (' . implode( ' OR ', $searches ) . ')';
    }

    /**
     * Parse orderby statement.
     *
     * @param string $orderby
     * @return string
     */
    protected function parse_orderby( $orderby ) {
        global $wpdb;

        $meta_query_clauses = $this->meta_query->get_clauses();

        $_orderby = '';

        if ( in_array( $orderby, array( 'country', 'status', 'tracking_id', 'date_created' ) ) ) {
            $_orderby = 'shipment_' . $orderby;
        } elseif ( 'ID' == $orderby || 'id' == $orderby ) {
            $_orderby = 'shipment_id';
        } elseif ( 'meta_value' == $orderby || $this->get( 'meta_key' ) == $orderby ) {
            $_orderby = "$wpdb->gzd_shipmentmeta.meta_value";
        } elseif ( 'meta_value_num' == $orderby ) {
            $_orderby = "$wpdb->gzd_shipmentmeta.meta_value+0";
        } elseif ( 'include' === $orderby && ! empty( $this->args['include'] ) ) {
            $include     = wp_parse_id_list( $this->args['include'] );
            $include_sql = implode( ',', $include );
            $_orderby    = "FIELD( $wpdb->gzd_shipments.shipment_id, $include_sql )";
        } elseif ( isset( $meta_query_clauses[ $orderby ] ) ) {
            $meta_clause = $meta_query_clauses[ $orderby ];
            $_orderby    = sprintf( 'CAST(%s.meta_value AS %s)', esc_sql( $meta_clause['alias'] ), esc_sql( $meta_clause['cast'] ) );
        }

        return $_orderby;
    }

    /**
     * Parse order statement.
     *
     * @param string $order
     * @return string
     */
    protected function parse_order( $order ) {
        if ( ! is_string( $order ) || empty( $order ) ) {
            return 'DESC';
        }

        if ( 'ASC' === strtoupper( $order ) ) {
            return 'ASC';
        } else {
            return 'DESC';
        }
    }
}
