<?php
namespace PooPrints;

if ( ! defined( 'ABSPATH' ) ) exit;

class Menu {

    public static function init() {
        add_action( 'admin_menu',                    [ __CLASS__, 'register_submenu' ] );
        add_filter( 'plugin_action_links_' . POOPRINTS_BASENAME, [ __CLASS__, 'action_links' ] );
    }

    public static function action_links( $links ) {
        $url = add_query_arg(
            [ 'page' => 'pooprints-calculator' ],
            admin_url( 'options-general.php' )
        );
        array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'pooprints-calculator' ) . '</a>' );
        return $links;
    }

    public static function register_submenu() {
        add_submenu_page(
            'options-general.php',
            __( 'PooPrints Calculator', 'pooprints-calculator' ),
            __( 'PooPrints Calculator', 'pooprints-calculator' ),
            'manage_options',
            'pooprints-calculator',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
        $tabs = [
            'settings'       => __( 'General', 'pooprints-calculator' ),
            'pricing-rates'  => __( 'Pricing & Rates', 'pooprints-calculator' ),
            'how-to-use'     => __( 'How to Use', 'pooprints-calculator' ),
        ];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'PooPrints Calculator', 'pooprints-calculator' ); ?></h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $tabs as $tab_key => $tab_label ) :
                    $url = add_query_arg( [
                        'page' => 'pooprints-calculator',
                        'tab'  => $tab_key,
                    ], admin_url( 'options-general.php' ) );
                    $class = ( $current_tab === $tab_key ) ? 'nav-tab nav-tab-active' : 'nav-tab';
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>">
                        <?php echo esc_html( $tab_label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php
                if ( $current_tab === 'settings' ) {
                    Settings_Page::render_settings_tab();
                } elseif ( $current_tab === 'pricing-rates' ) {
                    Settings_Page::render_pricing_tab();
                } elseif ( $current_tab === 'how-to-use' ) {
                    Settings_Page::render_how_to_use_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }
}
