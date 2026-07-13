<?php
if ( ! defined( 'ABSPATH' ) ) exit;

return [
    // Property Defaults
    'dogs'                      => 15,
    'units'                     => 100,
    // Option 1
    'o1_qty_swab'               => 15,
    'o1_qty_waste'              => 5,
    // Option 2
    'o2_qty_swab'               => 5,
    // Kit Prices & Fees
    'price_swab_kit'            => 57.97,
    'price_waste_kit'           => 39.97,
    'price_waste_test_report'   => 102.97,
    'price_waste_sample_test'   => 142.94,
    'price_setup_fee'           => 97.00,
    'price_subscription_fee'       => 227.00,
    'price_subscription_fee_small' => 97.00,
    'price_single_pay_discount'    => 90,
    'price_unentered_reg_card'     => 24.97,
    'price_dna_verification_test'  => 48.97,
    'price_failed_waste_testing'   => 102.97,
    'price_resident_pay_charge'    => 69.97,
    // Waste Prediction Formula
    'regression_a'              => 0.2177,
    'regression_b'              => 0.001345,
    'regression_c'              => 0.004391,
    // Profit & Rates
    'rate_profit_per_test'      => 52.00,
    'minutes_email'             => 8,
    'minutes_phone'             => 12,
    'minutes_social'            => 9,
    // ROI Input Defaults
    'rent_per_dog'              => 50.00,
    'dog_fee'                   => 300.00,
    'hidden_dogs'               => 5,
    'staff_wage'                => 31.60,
    'turnover_cost'             => 2000.00,
    'acquisition_cost'          => 2110.00,
    'renewals_saved'            => 2,
    'complaints_per_week'       => 2,
    'pickup_hours_per_week'     => 3.00,
    // Stats
    'stat_waste_samples_analyzed'        => 40000,
    'stat_pooprints_properties'          => '9,000+',
    'stat_five_star_reviews'             => '400+',
    'stat_years_in_business'             => '15+',
    'stat_avg_match_rate'                => 0.52,
    'stat_loss_charge_sample_only'       => 69.00,
    'stat_recommended_fine'              => 375.00,
    'stat_recommended_fine_row'          => '$375 (row 8)',
    'stat_breakeven_fine_row'            => '$280 (row 5)',
    'stat_breakeven_prime_tiered_small'  => 6.5,
    'stat_breakeven_prime_tiered_large'  => 13.1,
    'stat_pet_rent_increase_tiered'      => 5,
    'stat_pet_rent_increase_prime'       => 5,
    'stat_daily_fine_refuse_register'    => 10.00,
    'stat_waste_test_cost_match_no_match'=> 102.97,
    'stat_fine_low_end'                  => 250.00,
    'stat_fine_high_end'                 => 500.00,
    'stat_prime_pet_fee_increase'        => 60.00,
    'stat_tiered_pet_fee_increase'       => 70.00,
    // ROI Calculator
    'roi_masthead_title'       => 'PooPrints Saves Time and Money',
    // Side navigation (shortcode: [pooprints_sidenav])
    'sidenav_menu_id'          => 0,
];
