<?php
namespace PooPrints;

if ( ! defined( 'ABSPATH' ) ) exit;

class Ajax {

    public static function init() {
        // @future: waiting for client to confirm unit count is needed to update Keap field.
        // add_action( 'wp_ajax_pooprints_update_units', [ __CLASS__, 'update_units' ] );
        add_action( 'wp_ajax_pooprints_form_v2_submit', [ __CLASS__, 'submit_form_v2' ] );
        add_action( 'wp_ajax_nopriv_pooprints_form_v2_submit', [ __CLASS__, 'submit_form_v2' ] );
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

    /**
     * PooPrints Form v2: save one step to Keap and append non-field data to ContactNotes.
     */
    public static function submit_form_v2() {
        check_ajax_referer( 'pooprints_form_v2', 'nonce' );

        if ( ! function_exists( 'memb_setContactField' ) || ! function_exists( 'memb_getContactField' ) ) {
            wp_send_json_error( [ 'message' => 'Memberium not active.' ] );
        }

        $step = absint( $_POST['step'] ?? 0 );
        if ( $step < 1 || $step > 4 ) {
            wp_send_json_error( [ 'message' => 'Invalid form step.' ] );
        }

        $posted = self::sanitize_form_v2_post( $_POST );
        $tags   = Shortcodes::form_v2_tags();

        if ( isset( $tags[ 'step_' . $step ] ) ) {
            self::apply_form_v2_tags( [ $tags[ 'step_' . $step ] ] );
        }

        if ( 1 === $step ) {
            if ( 'yes' === ( $posted['shipping_same'] ?? '' ) ) {
                $posted['Address2Street1'] = $posted['Address3Street1'] ?? '';
                $posted['Address2Street2'] = $posted['Address3Street2'] ?? '';
                $posted['City2']           = $posted['City3'] ?? '';
                $posted['State2']          = $posted['State3'] ?? '';
                $posted['PostalCode2']     = $posted['PostalCode3'] ?? '';
                $posted['StreetAddress1']  = $posted['Address3Street1'] ?? '';
                $posted['StreetAddress2']  = $posted['Address3Street2'] ?? '';
                $posted['City']            = $posted['City3'] ?? '';
                $posted['State']           = $posted['State3'] ?? '';
                $posted['PostalCode']      = $posted['PostalCode3'] ?? '';
            } elseif ( 'no' === ( $posted['shipping_same'] ?? '' ) ) {
                $posted['StreetAddress1'] = $posted['shipping_address1'] ?? '';
                $posted['StreetAddress2'] = $posted['shipping_address2'] ?? '';
                $posted['City']           = $posted['shipping_city'] ?? '';
                $posted['State']          = $posted['shipping_state'] ?? '';
                $posted['PostalCode']     = $posted['shipping_zip'] ?? '';
            }

            self::save_form_v2_direct_fields( $posted, [
                'Company',
                'Address3Street1',
                'Address3Street2',
                'City3',
                'State3',
                'PostalCode3',
                'Address2Street1',
                'Address2Street2',
                'City2',
                'State2',
                'PostalCode2',
                'StreetAddress1',
                'StreetAddress2',
                'City',
                'State',
                'PostalCode',
                'Phone1',
                '_ofUnits',
            ] );
            self::append_form_v2_notes( 'Property & Shipping', $posted, [
                'shipping_same'          => 'Property address is shipping address',
                'shipping_name'          => 'Shipping property/company name',
                'shipping_address1'      => 'Shipping address line 1',
                'shipping_address2'      => 'Shipping address line 2',
                'shipping_city'          => 'Shipping city',
                'shipping_state'         => 'Shipping state',
                'shipping_zip'           => 'Shipping zip',
                'usps_receive'           => 'Can receive USPS packages',
                'temp_shipping_name'     => 'Temporary shipping property/company name',
                'temp_shipping_address1' => 'Temporary shipping address line 1',
                'temp_shipping_address2' => 'Temporary shipping address line 2',
                'temp_shipping_city'     => 'Temporary shipping city',
                'temp_shipping_state'    => 'Temporary shipping state',
                'temp_shipping_zip'      => 'Temporary shipping zip',
                'delay_shipping'         => 'Delay shipment',
                'delay_date'             => 'Requested arrival date',
            ] );
        }

        if ( 2 === $step ) {
            $software = $posted['_PropertyManagementSoftwareUsed'] ?? '';
            if ( 'Other' === $software && ! empty( $posted['software_other'] ) ) {
                $posted['_PropertyManagementSoftwareUsed'] = $posted['software_other'];
            }
            self::save_form_v2_direct_fields( $posted, [ '_PropertyManagementSoftwareUsed' ] );

            if ( 'yes' === ( $posted['petscreening'] ?? '' ) && isset( $tags['petscreening_yes'] ) ) {
                self::apply_form_v2_tags( [ $tags['petscreening_yes'] ] );
            } elseif ( 'no' === ( $posted['petscreening'] ?? '' ) && isset( $tags['petscreening_no'] ) ) {
                self::apply_form_v2_tags( [ $tags['petscreening_no'] ] );
            }

            self::append_form_v2_notes( 'Billing & Contact', $posted, [
                'billing_company'     => 'Billing company name',
                'billing_address1'    => 'Billing mailing address',
                'billing_address2'    => 'Billing unit/suite/leasing office',
                'billing_city'        => 'Billing city',
                'billing_state'       => 'Billing state',
                'billing_zip'         => 'Billing zip',
                'primary_first_name'  => 'Primary PooPrints contact first name',
                'primary_last_name'   => 'Primary PooPrints contact last name',
                'primary_email'       => 'Primary PooPrints contact email',
                'primary_job_title'   => 'Primary PooPrints contact job title',
                'ap_email'            => 'Accounts payable email',
                'petscreening'        => 'Uses PetScreening',
                '_PropertyManagementSoftwareUsed' => 'Property management software',
            ] );
        }

        if ( 3 === $step ) {
            self::append_form_v2_notes( 'Organization & Submit', $posted, [
                'has_management_company'          => 'Has management company',
                'community_type'                  => 'Community type',
                'self_managed'                    => 'Rental community is self managed',
                'management_company_name'         => 'Management company name',
                'management_address1'             => 'Management company USPS address',
                'management_address2'             => 'Management company unit/suite',
                'management_city'                 => 'Management company city',
                'management_state'                => 'Management company state',
                'management_zip'                  => 'Management company zip',
                'community_manager_first_name'    => 'Community manager first name',
                'community_manager_last_name'     => 'Community manager last name',
                'community_manager_email'         => 'Community manager email',
                'community_manager_job_title'     => 'Community manager job title',
                'regional_manager_first_name'     => 'Regional/asset manager first name',
                'regional_manager_last_name'      => 'Regional/asset manager last name',
                'regional_manager_email'          => 'Regional/asset manager email',
                'regional_manager_job_title'      => 'Regional/asset manager job title',
                'owner_first_name'                => 'Property owner first name',
                'owner_last_name'                 => 'Property owner last name',
                'owner_email'                     => 'Property owner email',
                'owner_phone'                     => 'Property owner phone',
                'owner_company_name'              => 'Property owner company name',
                'hoa_first_name'                  => 'HOA contact first name',
                'hoa_last_name'                   => 'HOA contact last name',
                'hoa_email'                       => 'HOA contact email',
                'hoa_role'                        => 'HOA contact role',
                'hoa_on_board'                    => 'HOA contact on association board',
            ] );
        }

        wp_send_json_success( [
            'message' => sprintf( 'Step %d saved.', $step ),
            'step'    => $step,
        ] );
    }

    private static function sanitize_form_v2_post( $data ) {
        $clean = [];
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, [ 'action', 'nonce', 'step', 'pooprints_form_v2_nonce' ], true ) ) {
                continue;
            }

            $key = preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $key );
            if ( '' === $key ) {
                continue;
            }
            if ( is_array( $value ) ) {
                $clean[ $key ] = array_map( 'sanitize_text_field', wp_unslash( $value ) );
            } elseif ( false !== strpos( $key, 'email' ) ) {
                $clean[ $key ] = sanitize_email( wp_unslash( $value ) );
            } elseif ( false !== strpos( $key, 'notes' ) ) {
                $clean[ $key ] = sanitize_textarea_field( wp_unslash( $value ) );
            } else {
                $clean[ $key ] = sanitize_text_field( wp_unslash( $value ) );
            }
        }
        return $clean;
    }

    private static function save_form_v2_direct_fields( $posted, $keys ) {
        $direct_fields = Shortcodes::form_v2_direct_fields();
        foreach ( $keys as $key ) {
            if ( ! isset( $direct_fields[ $key ] ) || ! array_key_exists( $key, $posted ) ) {
                continue;
            }
            memb_setContactField( $direct_fields[ $key ], $posted[ $key ] );
        }
    }

    private static function append_form_v2_notes( $section, $posted, $labels ) {
        $lines = [];
        foreach ( $labels as $key => $label ) {
            if ( ! array_key_exists( $key, $posted ) ) {
                continue;
            }
            $value = $posted[ $key ];
            if ( is_array( $value ) ) {
                $value = implode( ', ', array_filter( $value, 'strlen' ) );
            }
            if ( '' === trim( (string) $value ) ) {
                continue;
            }
            $lines[] = $label . ': ' . $value;
        }

        if ( empty( $lines ) ) {
            return;
        }

        $existing = memb_getContactField( 'ContactNotes', true );
        if ( ! is_string( $existing ) ) {
            $existing = '';
        }

        $entry = sprintf(
            "[%s] PooPrints Form v2 - %s\n%s",
            current_time( 'mysql' ),
            $section,
            implode( "\n", $lines )
        );

        $updated = trim( $existing );
        $updated = $updated ? $entry . "\n\n" . $updated : $entry;

        memb_setContactField( 'ContactNotes', $updated );
    }

    private static function apply_form_v2_tags( $tag_ids ) {
        if ( ! function_exists( 'memb_setTags' ) || empty( $tag_ids ) ) {
            return;
        }

        $tag_ids = array_filter( array_map( 'absint', $tag_ids ) );
        if ( empty( $tag_ids ) ) {
            return;
        }

        memb_setTags( implode( ',', $tag_ids ), false );
    }
}
