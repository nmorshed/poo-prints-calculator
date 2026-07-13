<?php
/**
 * Plugin Name: PooPrints Calculator
 * Plugin URI:  https://pooprints.com
 * Description: Embeddable DNA waste management calculators (Option 1, Option 2, ROI) and optional menu-driven side nav via shortcodes.
 * Version:     1.2.0
 * Author:      PooPrints
 * Text Domain: pooprints-calculator
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'POOPRINTS_VERSION', '1.2.0' );
define( 'POOPRINTS_DIR',     plugin_dir_path( __FILE__ ) );
define( 'POOPRINTS_URL',     plugin_dir_url( __FILE__ ) );
define( 'POOPRINTS_BASENAME', plugin_basename( __FILE__ ) );

require_once POOPRINTS_DIR . 'includes/settings-defaults.php';
require_once POOPRINTS_DIR . 'includes/class-settings-page.php';
require_once POOPRINTS_DIR . 'includes/class-menu.php';
require_once POOPRINTS_DIR . 'includes/class-assets.php';
require_once POOPRINTS_DIR . 'includes/class-shortcodes.php';
require_once POOPRINTS_DIR . 'includes/class-ajax.php';

add_action( 'init', function () {
    PooPrints\Menu::init();
    PooPrints\Assets::init();
    PooPrints\Shortcodes::init();
    PooPrints\Ajax::init();
} );

add_action( 'admin_init', function () {
    PooPrints\Settings_Page::register();
} );


add_shortcode('test_pooprints_fields', function() {
    if ( ! function_exists('memb_getContactField') ) {
        return '<p>Memberium not active.</p>';
    }

    $fields = [
        'Property Type'       => '_KindofPropertyQuoteFor',
        '# of Properties'     => '_QuoteforHowManyProperties',
        '# of Units'          => '_ofUnits',
        'Est. # of Dogs'      => '_ofDogs',
        'Metro Market'        => '_MetroMarket',
        'Quote Comments'      => '_QuoteComments',
        'How Did You Hear?'   => '_HowDidYouHearAboutUs',
        'Owner/Manager'       => '_OwnerManager',
    ];

    $output = '<ul>';
    foreach ( $fields as $label => $api_name ) {
        $val = memb_getContactField( $api_name, true );
        $output .= "<li><strong>{$label}:</strong> " . ( $val ?: '(empty)' ) . '</li>';
    }
    $output .= '</ul>';

    return $output;
});

add_shortcode('test_pooprints_set', function() {
    if ( ! function_exists('memb_setContactField') ) {
        return '<p>Memberium not active.</p>';
    }

    $output = '';

    // Only SET when ?action=set is in the URL
    if ( isset($_GET['action']) && $_GET['action'] === 'set' ) {
        $result1 = memb_setContactField('_MetroMarket', 'Nashvillee');
        $result2 = memb_setContactField('_QuoteComments', 'Test comment from shortcodee');
        $output .= '<p>Set called. Result1: ' . var_export($result1, true) . '</p>';
        $output .= '<p>Set called. Result2: ' . var_export($result2, true) . '</p>';
        $output .= '<p><em>Now reload WITHOUT ?action=set to read back the values.</em></p>';
    } else {
        // Just READ (no sync, reads from local Memberium cache)
        $metro    = memb_getContactField('_MetroMarket', true);
        $comments = memb_getContactField('_QuoteComments', true);
        $output .= "<p><strong>Metro Market:</strong> " . ($metro ?: '(empty)') . "</p>";
        $output .= "<p><strong>Quote Comments:</strong> " . ($comments ?: '(empty)') . "</p>";
        $output .= '<p><a href="?action=set">Click to SET values</a></p>';
    }

    return $output;
});