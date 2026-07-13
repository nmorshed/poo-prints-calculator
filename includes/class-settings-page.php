<?php
namespace PooPrints;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings_Page {

    const OPTION = 'pooprints_settings';

    /**
     * Single source of truth for all setting defaults.
     * Loaded from settings-defaults.php.
     */
    public static function defaults() {
        static $defaults = null;
        if ( $defaults === null ) {
            $defaults = require POOPRINTS_DIR . 'includes/settings-defaults.php';
        }
        return $defaults;
    }

    public static function get( $key ) {
        $saved    = get_option( self::OPTION, [] );
        $defaults = self::defaults();
        $default  = $defaults[ $key ] ?? '';

        $value = $saved[ $key ] ?? $default;

        // Fall back to default if saved value is empty string or numeric zero
        if ( is_string( $default ) ) {
            // Text/textarea: fall back only on empty string
            if ( $value === '' ) {
                $value = $default;
            }
        } else {
            // Numeric: fall back on empty string or zero
            if ( $value === '' || $value == 0 ) {
                $value = $default;
            }
        }

        return $value;
    }

    public static function register() {
        register_setting(
            self::OPTION . '_group',
            self::OPTION,
            [ 'sanitize_callback' => [ __CLASS__, 'sanitize' ] ]
        );

        // Section 1 — Property Defaults
        add_settings_section(
            'pp_section_property',
            __( 'Property Input Defaults', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION
        );
        add_settings_field(
            'dogs',
            __( 'Total # of Dogs', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_property',
            [ 'key' => 'dogs', 'size' => 'md' ]
        );
        add_settings_field(
            'units',
            __( 'Total # of Units', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_property',
            [ 'key' => 'units', 'size' => 'md' ]
        );

        // Section 2 — Option 1 Defaults
        add_settings_section(
            'pp_section_option1',
            __( 'Option 1 Input Defaults', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION
        );
        add_settings_field(
            'o1_qty_swab',
            __( 'Qty DNA Swab Kits', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_option1',
            [ 'key' => 'o1_qty_swab', 'size' => 'md' ]
        );
        add_settings_field(
            'o1_qty_waste',
            __( 'Qty Waste Collection Kits', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_option1',
            [ 'key' => 'o1_qty_waste', 'size' => 'md' ]
        );

        // Section 3 — Option 2 Defaults
        add_settings_section(
            'pp_section_option2',
            __( 'Option 2 Input Defaults', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION
        );
        add_settings_field(
            'o2_qty_swab',
            __( 'Qty DNA Swab Kits', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_option2',
            [ 'key' => 'o2_qty_swab', 'size' => 'md' ]
        );

        // Sections for Pricing & Rates tab (registered on same option so they save together)
        // Section: Kit Prices & Fees
        add_settings_section(
            'pp_section_kit_prices',
            __( 'Kit Prices & Fees', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION . '_pricing'
        );
        add_settings_field(
            'price_swab_kit',
            __( 'DNA Swab Kit Price ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_swab_kit', 'size' => 'md' ]
        );
        add_settings_field(
            'price_waste_kit',
            __( 'Waste Collection Kit Price ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_waste_kit', 'size' => 'md' ]
        );
        add_settings_field(
            'price_waste_test_report',
            __( 'DNA Waste Test Report Price ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_waste_test_report', 'size' => 'md' ]
        );
        add_settings_field(
            'price_waste_sample_test',
            __( 'Total Cost to Process a Waste Sample Test ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_waste_sample_test', 'size' => 'md' ]
        );
        add_settings_field(
            'price_setup_fee',
            __( 'Setup Fee ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_setup_fee', 'size' => 'md' ]
        );
        add_settings_field(
            'price_subscription_fee',
            __( 'Annual Property Subscription Fee 50 or More Units ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_subscription_fee', 'size' => 'md' ]
        );
        add_settings_field(
            'price_subscription_fee_small',
            __( 'Annual Property Subscription Fee Less Than 50 Units ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_subscription_fee_small', 'size' => 'md' ]
        );
        add_settings_field(
            'price_single_pay_discount',
            __( 'Single-Pay Discount (% of full price kept, e.g. 90 = 10% off)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_single_pay_discount', 'size' => 'md' ]
        );

        add_settings_field(
            'price_unentered_reg_card',
            __( 'Unentered Dog Owner Registration Card Cost ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_unentered_reg_card', 'size' => 'md' ]
        );
        add_settings_field(
            'price_dna_verification_test',
            __( 'DNA Verification Test Cost ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_dna_verification_test', 'size' => 'md' ]
        );
        add_settings_field(
            'price_failed_waste_testing',
            __( 'Failed Waste Testing Cost ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_failed_waste_testing', 'size' => 'md' ]
        );
        add_settings_field(
            'price_resident_pay_charge',
            __( 'Resident Pay Charge ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_kit_prices',
            [ 'key' => 'price_resident_pay_charge', 'size' => 'md' ]
        );

        // Section: Waste Prediction Formula
        add_settings_section(
            'pp_section_regression',
            __( 'Waste Prediction Formula', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION . '_pricing'
        );
        add_settings_field(
            'regression_a',
            __( 'Variable A', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal_precise' ],
            self::OPTION . '_pricing',
            'pp_section_regression',
            [ 'key' => 'regression_a', 'desc' => 'Baseline constant', 'size' => 'md' ]
        );
        add_settings_field(
            'regression_b',
            __( 'Variable B', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal_precise' ],
            self::OPTION . '_pricing',
            'pp_section_regression',
            [ 'key' => 'regression_b', 'desc' => 'Units multiplier', 'size' => 'md' ]
        );
        add_settings_field(
            'regression_c',
            __( 'Variable C', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal_precise' ],
            self::OPTION . '_pricing',
            'pp_section_regression',
            [ 'key' => 'regression_c', 'desc' => 'Dogs multiplier', 'size' => 'md' ]
        );

        // Section: Profit & Rates
        add_settings_section(
            'pp_section_profit_rates',
            __( 'Profit & Rates', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION . '_pricing'
        );
        add_settings_field(
            'rate_profit_per_test',
            __( 'Average Waste Testing Profit ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION . '_pricing',
            'pp_section_profit_rates',
            [ 'key' => 'rate_profit_per_test', 'size' => 'md' ]
        );
        add_settings_field(
            'minutes_email',
            __( 'Minutes Per Email Complaint Reply', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION . '_pricing',
            'pp_section_profit_rates',
            [ 'key' => 'minutes_email', 'size' => 'md' ]
        );
        add_settings_field(
            'minutes_phone',
            __( 'Minutes Per Phone Call About Dog Waste', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION . '_pricing',
            'pp_section_profit_rates',
            [ 'key' => 'minutes_phone', 'size' => 'md' ]
        );
        add_settings_field(
            'minutes_social',
            __( 'Minutes Per Social Media Reply', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION . '_pricing',
            'pp_section_profit_rates',
            [ 'key' => 'minutes_social', 'size' => 'md' ]
        );

        // Section: ROI Calculator Labels
        add_settings_section(
            'pp_section_roi_labels',
            __( 'ROI Calculator Labels', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION
        );
        add_settings_field(
            'roi_masthead_title',
            __( 'ROI Section Heading', 'pooprints-calculator' ),
            [ __CLASS__, 'field_text' ],
            self::OPTION,
            'pp_section_roi_labels',
            [ 'key' => 'roi_masthead_title' ]
        );

        // Section 4 — ROI Input Defaults
        add_settings_section(
            'pp_section_roi_defaults',
            __( 'ROI Calculator Input Defaults', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION
        );
        add_settings_field(
            'rent_per_dog',
            __( 'Monthly Rent per Dog ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'rent_per_dog', 'size' => 'md' ]
        );
        add_settings_field(
            'dog_fee',
            __( 'Non-Refundable Dog Fee per Dog ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'dog_fee', 'size' => 'md' ]
        );
        add_settings_field(
            'hidden_dogs',
            __( 'Estimated # of Hidden Dogs', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'hidden_dogs', 'size' => 'md' ]
        );
        add_settings_field(
            'staff_wage',
            __( 'Average Staff Wage per Hour ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'staff_wage', 'size' => 'md' ]
        );
        add_settings_field(
            'turnover_cost',
            __( 'Average Turnover Cost per Unit ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'turnover_cost', 'size' => 'md' ]
        );
        add_settings_field(
            'acquisition_cost',
            __( 'Average Renter Acquisition Cost ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'acquisition_cost', 'size' => 'md' ]
        );
        add_settings_field(
            'renewals_saved',
            __( 'Estimated Lease Renewals Saved per Year', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'renewals_saved', 'size' => 'md' ]
        );
        add_settings_field(
            'complaints_per_week',
            __( 'Dog Waste Complaints per Week', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'complaints_per_week', 'size' => 'md' ]
        );
        add_settings_field(
            'pickup_hours_per_week',
            __( 'Staff Pickup Hours per Week', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_roi_defaults',
            [ 'key' => 'pickup_hours_per_week', 'size' => 'md' ]
        );

        // Section: Stats
        add_settings_section(
            'pp_section_stats',
            __( 'Stats', 'pooprints-calculator' ),
            '__return_false',
            self::OPTION
        );
        add_settings_field(
            'stat_waste_samples_analyzed',
            __( 'Waste Testing Results Are Based On This Number of Waste Samples Analyzed', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_waste_samples_analyzed', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_pooprints_properties',
            __( 'Number of PooPrints Properties', 'pooprints-calculator' ),
            [ __CLASS__, 'field_text' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_pooprints_properties' ]
        );
        add_settings_field(
            'stat_five_star_reviews',
            __( 'Number of 5 Star Reviews', 'pooprints-calculator' ),
            [ __CLASS__, 'field_text' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_five_star_reviews' ]
        );
        add_settings_field(
            'stat_years_in_business',
            __( 'Years in Business', 'pooprints-calculator' ),
            [ __CLASS__, 'field_text' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_years_in_business' ]
        );
        add_settings_field(
            'stat_avg_match_rate',
            __( 'Average Waste Testing Match Rate (decimal, e.g. 0.52 = 52%)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal_precise' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_avg_match_rate', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_loss_charge_sample_only',
            __( 'Loss Amount When Charging Only The Cost to Process a Waste Sample ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_loss_charge_sample_only', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_recommended_fine',
            __( 'Recommended Fine Amount ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_recommended_fine', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_recommended_fine_row',
            __( 'Recommended Fine Amount and Row # in Table', 'pooprints-calculator' ),
            [ __CLASS__, 'field_text' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_recommended_fine_row' ]
        );
        add_settings_field(
            'stat_breakeven_fine_row',
            __( 'Breakeven Fine Amount and Row # in Table', 'pooprints-calculator' ),
            [ __CLASS__, 'field_text' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_breakeven_fine_row' ]
        );
        add_settings_field(
            'stat_breakeven_prime_tiered_small',
            __( 'Breakeven Analysis Prime vs Tiered Less than 50 Units', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_breakeven_prime_tiered_small', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_breakeven_prime_tiered_large',
            __( 'Breakeven Analysis Prime vs Tiered 50 or More Units', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_breakeven_prime_tiered_large', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_pet_rent_increase_tiered',
            __( 'Amount to Increase Pet Rent to Cover Swab/Registration Cost on Tiered Plan', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_pet_rent_increase_tiered', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_pet_rent_increase_prime',
            __( 'Amount to Increase Pet Rent to Cover Swab/Registration Cost on Prime Plan', 'pooprints-calculator' ),
            [ __CLASS__, 'field_number' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_pet_rent_increase_prime', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_daily_fine_refuse_register',
            __( 'Recommended Per Day Fine for Residents Refusing to Register Their Dogs ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_daily_fine_refuse_register', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_waste_test_cost_match_no_match',
            __( 'Waste Testing Cost for Match and No Match ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_waste_test_cost_match_no_match', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_fine_low_end',
            __( 'Low End Fine Amount ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_fine_low_end', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_fine_high_end',
            __( 'High End Fine Amount ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_fine_high_end', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_prime_pet_fee_increase',
            __( 'Prime Pet Fee Increase Amount ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_prime_pet_fee_increase', 'size' => 'md' ]
        );
        add_settings_field(
            'stat_tiered_pet_fee_increase',
            __( 'Tiered Pet Fee Increase Amount ($)', 'pooprints-calculator' ),
            [ __CLASS__, 'field_decimal' ],
            self::OPTION,
            'pp_section_stats',
            [ 'key' => 'stat_tiered_pet_fee_increase', 'size' => 'md' ]
        );
    }

    public static function sanitize( $input ) {
        $clean         = [];
        $saved         = get_option( self::OPTION, [] );
        $text_keys     = [
            'roi_masthead_title',
            'stat_pooprints_properties', 'stat_five_star_reviews', 'stat_years_in_business',
            'stat_recommended_fine_row', 'stat_breakeven_fine_row',
        ];
        $textarea_keys = [];
        $float_keys    = [
            'price_swab_kit', 'price_waste_kit', 'price_waste_test_report', 'price_waste_sample_test', 'price_setup_fee',
            'price_subscription_fee', 'price_subscription_fee_small', 'rate_profit_per_test',
            'price_unentered_reg_card', 'price_dna_verification_test', 'price_failed_waste_testing', 'price_resident_pay_charge',
            'stat_fine_low_end', 'stat_fine_high_end', 'stat_prime_pet_fee_increase', 'stat_tiered_pet_fee_increase',
            'stat_avg_match_rate', 'stat_loss_charge_sample_only', 'stat_recommended_fine', 'stat_daily_fine_refuse_register',
            'stat_waste_test_cost_match_no_match', 'stat_breakeven_prime_tiered_small', 'stat_breakeven_prime_tiered_large',
            'regression_a', 'regression_b', 'regression_c',
            'rent_per_dog', 'dog_fee', 'staff_wage',
            'turnover_cost', 'acquisition_cost', 'pickup_hours_per_week',
        ];
        $absint_keys   = [
            'price_single_pay_discount',
            'minutes_email', 'minutes_phone', 'minutes_social',
            'hidden_dogs', 'renewals_saved', 'complaints_per_week',
            'sidenav_menu_id', 'stat_waste_samples_analyzed',
            'stat_pet_rent_increase_tiered', 'stat_pet_rent_increase_prime',
        ];

        foreach ( self::defaults() as $key => $default ) {
            $fallback = $saved[ $key ] ?? $default;
            if ( in_array( $key, $textarea_keys, true ) ) {
                $clean[ $key ] = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : $fallback;
            } elseif ( in_array( $key, $text_keys, true ) ) {
                $clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $fallback;
            } elseif ( in_array( $key, $float_keys, true ) ) {
                $clean[ $key ] = isset( $input[ $key ] ) ? floatval( $input[ $key ] ) : $fallback;
            } elseif ( in_array( $key, $absint_keys, true ) ) {
                $clean[ $key ] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : $fallback;
            } else {
                $clean[ $key ] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : $fallback;
            }
        }
        return $clean;
    }

    public static function format_smart_decimal( $value ) {
        if ( ! is_numeric( $value ) ) {
            return $value;
        }
        $float = (float) $value;
        return number_format( $float, ( $float - floor( $float ) > 0 ) ? 2 : 0, '.', '' );
    }

    private static function input_size_attr( $args ) {
        $size_classes = [ 'sm' => 'pp-input-sm', 'md' => 'pp-input-md', 'lg' => 'pp-input-lg' ];
        if ( ! empty( $args['width'] ) ) {
            return sprintf( 'style="width:%s"', esc_attr( $args['width'] ) );
        }
        $class = $size_classes[ $args['size'] ?? 'md' ] ?? 'pp-input-md';
        return sprintf( 'class="%s"', esc_attr( $class ) );
    }

    public static function field_number( $args ) {
        $key         = $args['key'];
        $value       = self::get( $key );
        $placeholder = self::defaults()[ $key ] ?? '';
        printf(
            '<input type="number" id="%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" step="1" min="0" %5$s>',
            esc_attr( $key ),
            esc_attr( self::OPTION ),
            esc_attr( $value ),
            esc_attr( $placeholder ),
            self::input_size_attr( $args )
        );
    }

    public static function field_decimal( $args ) {
        $key         = $args['key'];
        $value       = self::get( $key );
        $display     = self::format_smart_decimal( $value );
        $placeholder = self::defaults()[ $key ] ?? '';
        $desc        = $args['desc'] ?? '';
        printf(
            '<input type="number" id="%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" step="0.01" min="0" %5$s>',
            esc_attr( $key ),
            esc_attr( self::OPTION ),
            esc_attr( $display ),
            esc_attr( $placeholder ),
            self::input_size_attr( $args )
        );
        if ( $desc ) {
            echo '<p class="description">' . esc_html( $desc ) . '</p>';
        }
    }

    public static function field_decimal_precise( $args ) {
        $key         = $args['key'];
        $value       = self::get( $key );
        $placeholder = self::defaults()[ $key ] ?? '';
        $desc        = $args['desc'] ?? '';
        printf(
            '<input type="number" id="%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" step="0.000001" min="0" %5$s>',
            esc_attr( $key ),
            esc_attr( self::OPTION ),
            esc_attr( $value ),
            esc_attr( $placeholder ),
            self::input_size_attr( $args )
        );
        if ( $desc ) {
            echo '<p class="description">' . esc_html( $desc ) . '</p>';
        }
    }

    public static function field_text( $args ) {
        $key         = $args['key'];
        $value       = self::get( $key );
        $placeholder = self::defaults()[ $key ] ?? '';
        printf(
            '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" class="regular-text">',
            esc_attr( $key ),
            esc_attr( self::OPTION ),
            esc_attr( $value ),
            esc_attr( $placeholder )
        );
    }

    public static function field_nav_menu( $args ) {
        $key   = $args['key'];
        $value = absint( self::get( $key ) );
        $menus = wp_get_nav_menus();

        printf(
            '<select id="%1$s" name="%2$s[%1$s]">',
            esc_attr( $key ),
            esc_attr( self::OPTION )
        );
        printf(
            '<option value="0"%s>%s</option>',
            selected( $value, 0, false ),
            esc_html__( '— Select a menu —', 'pooprints-calculator' )
        );
        foreach ( $menus as $menu ) {
            printf(
                '<option value="%d"%s>%s</option>',
                (int) $menu->term_id,
                selected( $value, (int) $menu->term_id, false ),
                esc_html( $menu->name )
            );
        }
        echo '</select>';
        if ( ! empty( $args['desc'] ) ) {
            echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
        }
    }

    public static function field_textarea( $args ) {
        $key   = $args['key'];
        $value = self::get( $key );
        $desc  = $args['desc'] ?? '';
        printf(
            '<textarea id="%1$s" name="%2$s[%1$s]" rows="5" class="large-text">%3$s</textarea>',
            esc_attr( $key ),
            esc_attr( self::OPTION ),
            esc_textarea( $value )
        );
        if ( $desc ) {
            echo '<p class="description">' . esc_html( $desc ) . '</p>';
        }
    }

    private static function section_styles() {
        ?>
        <style>
            .pp-settings h2 {
                background: var(--wp-editor-canvas-background);
                padding: 8px 12px;
                margin-top: 32px;
                font-size: 15px;
                color: #1d2327;
            }
            .pp-input-sm  { width: 65px;  }
            .pp-input-md  { width: 100px; }
            .pp-input-lg  { width: 160px; }
            .pp-settings .form-table th { width: 320px; }
        </style>
        <?php
    }

    public static function render_settings_tab() {
        self::section_styles();
        ?>
        <form method="post" action="options.php" class="pp-settings">
            <?php
            settings_fields( self::OPTION . '_group' );
            do_settings_sections( self::OPTION );
            submit_button( __( 'Save Settings', 'pooprints-calculator' ) );
            ?>
        </form>
        <?php
    }

    public static function render_pricing_tab() {
        self::section_styles();
        ?>
        <form method="post" action="options.php" class="pp-settings">
            <?php
            settings_fields( self::OPTION . '_group' );
            do_settings_sections( self::OPTION . '_pricing' );
            submit_button( __( 'Save Settings', 'pooprints-calculator' ) );
            ?>
        </form>
        <?php
    }

    public static function render_how_to_use_tab() {
        $shortcodes = [
            '[pooprints_option1]'          => __( 'Renders the Option 1 calculator card.', 'pooprints-calculator' ),
            '[pooprints_option2]'          => __( 'Renders the Option 2 calculator card.', 'pooprints-calculator' ),
            '[pooprints_roi]'              => __( 'Renders the ROI calculator.', 'pooprints-calculator' ),
            '[pooprints_sidenav]'          => __( 'Renders a side navigation from the menu chosen under Settings → General → Side navigation menu. Top-level links only (Appearance → Menus). Use Elementor’s sticky on the column or widget if you want it fixed while scrolling.', 'pooprints-calculator' ),
            '[pooprints_sidenav menu="slug"]' => __( 'Same as [pooprints_sidenav] but uses a specific menu: WordPress menu ID, slug, or name (overrides the setting).', 'pooprints-calculator' ),
            '[pooprints_value key="..."]'  => __( 'Renders a live dynamic value span inside prose text. Alias: [pp_value key="..."]', 'pooprints-calculator' ),
            '[pooprints_fine_tables]'      => __( 'Renders both fine reference tables side by side (breakeven + profit/loss).', 'pooprints-calculator' ),
            '[pooprints_fine_tables show="breakeven"]' => __( 'Renders only the Breakeven Fine Amount table.', 'pooprints-calculator' ),
            '[pooprints_fine_tables show="profit"]'    => __( 'Renders only the Waste Testing Profit (Loss) table.', 'pooprints-calculator' ),
        ];

        $value_groups = [
            'Option 1' => [
                'opt1_qty_swab'        => 'Qty DNA swab kits',
                'opt1_qty_waste'       => 'Qty waste collection kits',
                'opt1_payment_monthly'  => 'Monthly payment amount (12-month plan, no discount)',
                'opt1_payment_single'   => 'Pay in full amount (10% discount applied)',
                'opt1_payment_12total'  => 'Total cost over 12 monthly payments (no discount)',
                'opt1_payment_savings'  => 'Dollar amount saved by paying in full vs. monthly plan (whole number)',
            ],
            'Option 2' => [
                'opt2_qty_swab'        => 'Qty DNA swab kits',
                'opt2_payment_single'  => 'Total cost',
            ],
            'Community' => [
                'total_units'          => 'Total units',
                'total_dogs'           => 'Total dogs (including ESA)',
            ],
            'ROI' => [
                'roi_savings_total'    => 'Total annual savings',
                'roi_cash_savings'     => 'Total cash savings',
                'roi_fees_recovered'   => 'Pet fees recovered from hidden dogs',
                'roi_turnover_saved'   => 'Turnover cost savings',
                'roi_acquisition_saved'=> 'Acquisition cost savings',
                'roi_hours_saved'      => 'Total staff hours saved per year',
                'roi_net_return'       => 'Net return (savings minus investment)',
            ],
            'Prices & Rates' => [
                'price_swab_kit'               => 'DNA Swab Kit price',
                'price_waste_kit'              => 'Waste Collection Kit price',
                'price_waste_test_report'      => 'DNA Waste Test Report price',
                'price_waste_sample_test'      => 'Total cost to process a waste sample test',
                'price_subscription_fee'       => 'Annual property subscription fee (50 or more units)',
                'price_subscription_fee_small' => 'Annual property subscription fee (less than 50 units)',
                'price_unentered_reg_card'     => 'Unentered dog owner registration card cost',
                'price_dna_verification_test'  => 'DNA verification test cost',
                'price_failed_waste_testing'   => 'Failed waste testing cost',
                'price_resident_pay_charge'    => 'Resident pay charge',
                'rate_profit_per_test'         => 'Average waste testing profit',
            ],
            'Stats' => [
                'stat_waste_samples_analyzed'        => 'Number of waste samples analyzed (basis for waste testing results)',
                'stat_pooprints_properties'          => 'Number of PooPrints properties',
                'stat_five_star_reviews'             => 'Number of 5 star reviews',
                'stat_years_in_business'             => 'Years in business',
                'stat_avg_match_rate'                => 'Average waste testing match rate (decimal, used in fine tables)',
                'stat_loss_charge_sample_only'       => 'Loss amount when charging only the cost to process a waste sample',
                'stat_recommended_fine'              => 'Recommended fine amount',
                'stat_recommended_fine_row'          => 'Recommended fine amount and row # in table',
                'stat_breakeven_fine_row'            => 'Breakeven fine amount and row # in table',
                'stat_breakeven_prime_tiered_small'  => 'Breakeven analysis Prime vs Tiered (less than 50 units)',
                'stat_breakeven_prime_tiered_large'  => 'Breakeven analysis Prime vs Tiered (50 or more units)',
                'stat_pet_rent_increase_tiered'      => 'Amount to increase pet rent to cover swab/registration cost on Tiered plan',
                'stat_pet_rent_increase_prime'       => 'Amount to increase pet rent to cover swab/registration cost on Prime plan',
                'stat_daily_fine_refuse_register'    => 'Recommended per day fine for residents refusing to register their dogs',
                'stat_waste_test_cost_match_no_match'=> 'Waste testing cost for match and no match',
                'stat_fine_low_end'                  => 'Low end fine amount',
                'stat_fine_high_end'                 => 'High end fine amount',
                'stat_prime_pet_fee_increase'        => 'Prime pet fee increase amount',
                'stat_tiered_pet_fee_increase'       => 'Tiered pet fee increase amount',
            ],
        ];

        $url_params = [
            'o1_qty_swab'           => 'Option 1 DNA swab kit qty',
            'o1_qty_waste'          => 'Option 1 waste collection kit qty',
            'o2_qty_swab'           => 'Option 2 DNA swab kit qty',
            'units'                 => 'Total # of units',
            'dogs'                  => 'Total # of dogs',
            'rent_per_dog'          => 'Monthly rent per dog',
            'dog_fee'               => 'One-time dog fee',
            'hidden_dogs'           => 'Estimated hidden dogs',
            'staff_wage'            => 'Staff hourly wage',
            'turnover_cost'         => 'Turnover cost per unit',
            'acquisition_cost'      => 'Renter acquisition cost',
            'renewals_saved'        => 'Lease renewals saved per year',
            'complaints_per_week'   => 'Dog waste complaints per week',
            'minutes_email'         => 'Minutes per email complaint',
            'minutes_phone'         => 'Minutes per phone complaint',
            'minutes_social'        => 'Minutes per social complaint',
            'pickup_hours_per_week' => 'Staff pickup hours per week',
        ];
        ?>
        <div style="max-width: 760px;">

            <h2><?php esc_html_e( 'Shortcode Reference', 'pooprints-calculator' ); ?></h2>
            <p><?php esc_html_e( 'Copy any shortcode below and paste it into a page or post.', 'pooprints-calculator' ); ?></p>
            <p><?php esc_html_e( 'For the side nav, assign links in Appearance → Menus, then pick that menu under Settings → General → Side navigation menu (or pass menu="…" on the shortcode).', 'pooprints-calculator' ); ?></p>

            <table class="widefat striped" style="margin-top: 16px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Shortcode', 'pooprints-calculator' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'pooprints-calculator' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $shortcodes as $code => $desc ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $code ); ?></code></td>
                            <td><?php echo esc_html( $desc ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3 style="margin-top: 32px;"><?php esc_html_e( 'Available keys for [pooprints_value key="..."]', 'pooprints-calculator' ); ?></h3>
            <p><?php esc_html_e( 'Place live calculated values inline within paragraph text. Both shortcodes are identical:', 'pooprints-calculator' ); ?><br>
                <code>[pooprints_value key="roi_savings_total"]</code> &nbsp;or&nbsp; <code>[pp_value key="roi_savings_total"]</code>
            </p>

            <table class="widefat striped" style="margin-top: 12px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Key', 'pooprints-calculator' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'pooprints-calculator' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $value_groups as $group => $keys ) : ?>
                        <tr>
                            <td colspan="2" style="background:#f6f7f7; color:#2271b1; font-weight:600; padding: 6px 10px;">
                                <?php echo esc_html( $group ); ?>
                            </td>
                        </tr>
                        <?php foreach ( $keys as $key => $desc ) : ?>
                            <tr>
                                <td><code><?php echo esc_html( $key ); ?></code></td>
                                <td><?php echo esc_html( $desc ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3 style="margin-top: 32px;"><?php esc_html_e( 'URL Query Params (for pre-filling via forwarded links)', 'pooprints-calculator' ); ?></h3>
            <p><?php esc_html_e( 'Append these to any page URL containing the calculators to pre-fill inputs. Example:', 'pooprints-calculator' ); ?>
                <code>?o1_qty_swab=15&amp;units=100&amp;dogs=15</code>
            </p>

            <table class="widefat striped" style="margin-top: 12px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Param', 'pooprints-calculator' ); ?></th>
                        <th><?php esc_html_e( 'Maps to', 'pooprints-calculator' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $url_params as $param => $desc ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $param ); ?></code></td>
                            <td><?php echo esc_html( $desc ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
        <?php
    }
}
