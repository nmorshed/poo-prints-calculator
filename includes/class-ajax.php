<?php
namespace PooPrints;

if ( ! defined( 'ABSPATH' ) ) exit;

class Ajax {

    public static function init() {
        // @future: waiting for client to confirm unit count is needed to update Keap field.
        // add_action( 'wp_ajax_pooprints_update_units', [ __CLASS__, 'update_units' ] );
    }

    public static function update_units() {
        check_ajax_referer( 'pooprints_update_units', 'nonce' );

        if ( ! function_exists( 'memb_setContactField' ) ) {
            wp_send_json_error( [ 'message' => 'Memberium not active.' ] );
        }

        $units = absint( $_POST['units'] ?? 0 );
        memb_setContactField( '_ofUnits', $units );

        wp_send_json_success( [ 'units' => $units ] );
    }
}
