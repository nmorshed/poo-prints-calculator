<?php
namespace PooPrints;

if ( ! defined( 'ABSPATH' ) ) exit;

class Assets {

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
        add_action( 'wp_footer', [ __CLASS__, 'maybe_enqueue' ], 20 );
        add_action( 'wp_footer', [ __CLASS__, 'maybe_print_late_styles' ], 21 );
    }

    public static function register_assets() {
        wp_register_style(
            'pooprints-calculator',
            POOPRINTS_URL . 'assets/style.css',
            [],
            POOPRINTS_VERSION
        );
        wp_register_style(
            'pooprints-get-started',
            POOPRINTS_URL . 'assets/get-started.css',
            [],
            POOPRINTS_VERSION
        );
        wp_register_script(
            'pooprints-calculator',
            POOPRINTS_URL . 'assets/main.js',
            [],
            POOPRINTS_VERSION,
            true
        );
        wp_register_script(
            'pooprints-get-started',
            POOPRINTS_URL . 'assets/get-started.js',
            [],
            POOPRINTS_VERSION,
            true
        );
    }

    public static function maybe_enqueue() {
        if ( ! Shortcodes::$enqueue_assets && ! Shortcodes::$enqueue_sidenav ) {
            return;
        }

        wp_enqueue_style( 'pooprints-calculator' );
        wp_enqueue_script( 'pooprints-calculator' );

        if ( Shortcodes::$enqueue_get_started ) {
            wp_enqueue_style( 'pooprints-get-started' );
            wp_enqueue_script( 'pooprints-get-started' );
        }

        wp_localize_script( 'pooprints-calculator', 'ppPrices', [
            'ajax_url'             => admin_url( 'admin-ajax.php' ),
            'nonce'                => wp_create_nonce( 'pooprints_update_units' ),
            'swab_kit'             => (float) Settings_Page::get('price_swab_kit'),
            'waste_kit'            => (float) Settings_Page::get('price_waste_kit'),
            'setup_fee'            => (float) Settings_Page::get('price_setup_fee'),
            'subscription_fee'     => (float) Settings_Page::get('price_subscription_fee'),
            'single_pay_discount'  => (float) Settings_Page::get('price_single_pay_discount') / 100,
            'profit_per_test'      => (float) Settings_Page::get('rate_profit_per_test'),
            'minutes_email'        => (int)   Settings_Page::get('minutes_email'),
            'minutes_phone'        => (int)   Settings_Page::get('minutes_phone'),
            'minutes_social'       => (int)   Settings_Page::get('minutes_social'),
            'regression_a'         => (float) Settings_Page::get('regression_a'),
            'regression_b'         => (float) Settings_Page::get('regression_b'),
            'regression_c'         => (float) Settings_Page::get('regression_c'),
            'field_map'            => self::build_field_map(),
        ] );

    }

    /**
     * Print any styles enqueued late (after wp_head) directly into the footer.
     * Needed when Elementor processes shortcodes during wp_footer, after wp_head
     * has already fired, so wp_enqueue_style alone won't output the <link> tag.
     */
    public static function maybe_print_late_styles() {
        if ( ! Shortcodes::$enqueue_assets && ! Shortcodes::$enqueue_sidenav ) {
            return;
        }
        wp_print_styles( 'pooprints-calculator' );
        wp_print_styles( 'pooprints-inter' );
        wp_print_scripts( 'pooprints-calculator' );
        if ( Shortcodes::$enqueue_get_started ) {
            wp_print_styles( 'pooprints-get-started' );
            wp_print_scripts( 'pooprints-get-started' );
        }
    }

    /**
     * Build the field map for JS from Shortcodes::field_map().
     * Key = URL param = HTML input ID. Default is index 2 of each entry.
     */
    private static function build_field_map() {
        $result = [];
        foreach ( Shortcodes::field_map() as $param => $entry ) {
            $result[ $param ] = [
                'id'      => $param,     // key IS the input element ID
                'default' => $entry[2],  // default value
            ];
        }
        return $result;
    }
}
