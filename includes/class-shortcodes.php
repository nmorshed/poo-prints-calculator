<?php
namespace PooPrints;

if ( ! defined( 'ABSPATH' ) ) exit;

class Shortcodes {

    /** Set to true by any calculator shortcode so Assets knows to enqueue. */
    public static $enqueue_assets = false;

    /** Set to true by [pooprints_sidenav] so base styles (incl. sidenav) load. */
    public static $enqueue_sidenav = false;

    /** Set to true by get-started shortcodes so get-started.css/.js load. */
    public static $enqueue_get_started = false;

    /** PooPrints Form v2: set by the four-step order form shortcode. */
    public static $enqueue_form_v2 = false;

    /*
     * value_defaults() removed — JS is the single source of truth for span values.
     * Keys are now maintained inline in render_value() for validation only.
     *
     * private static function value_defaults() {
     *     return [
     *         'opt1_qty_swab'        => '15',
     *         'opt1_qty_waste'       => '5',
     *         'total_units'          => '100',
     *         'total_dogs'           => '15',
     *         'opt1_payment_monthly' => '127.73',
     *         'opt1_payment_single'  => '1,393.40',
     *         'roi_savings_total'    => '18,623',
     *         'roi_net_return'       => '17,229.60',
     *         'opt2_qty_swab'        => '5',
     *         'opt2_payment_single'  => '613.85',
     *         'roi_hours_saved'      => '178.5',
     *         'roi_cash_savings'     => '8,481',
     *         'roi_fees_recovered'   => '4,500',
     *         'roi_turnover_saved'   => '4,000',
     *         'roi_acquisition_saved'=> '4,220',
     *     ];
     * }
     */

    /**
     * Single source of truth: URL param = HTML input ID → [ Keap field (Memberium API) or null, 'int'|'float', settings_key ].
     * The array key is used as the URL query param AND the HTML input id attribute.
     * Index 2 is the settings key passed to Settings_Page::get() — literals live only in settings-defaults.php.
     * Only the Keap field name (index 0) is separate — it is external and not under our control.
     */
    public static function field_map() {
        static $map = null;
        if ( $map === null ) {
            $map = [
                // Option 1
                'o1_qty_swab'               => [ null,       'int',   (int)   Settings_Page::get( 'o1_qty_swab' )               ],
                'o1_qty_waste'              => [ null,       'int',   (int)   Settings_Page::get( 'o1_qty_waste' )              ],

                // Option 2
                'o2_qty_swab'               => [ null,       'int',   (int)   Settings_Page::get( 'o2_qty_swab' )               ],

                // Community
                'units'                 => [ '_ofUnits', 'int',   (int)   Settings_Page::get( 'units' )              ],
                'dogs'                  => [ '_ofDogs',  'int',   (int)   Settings_Page::get( 'dogs' )               ],
                'rent_per_dog'          => [ null,       'float', (float) Settings_Page::get( 'rent_per_dog' )       ],
                'dog_fee'               => [ null,       'float', (float) Settings_Page::get( 'dog_fee' )            ],
                'hidden_dogs'           => [ null,       'int',   (int)   Settings_Page::get( 'hidden_dogs' )        ],
                'staff_wage'            => [ null,       'float', (float) Settings_Page::get( 'staff_wage' )         ],

                // Turnover
                'turnover_cost'         => [ null,       'float', (float) Settings_Page::get( 'turnover_cost' )      ],
                'acquisition_cost'      => [ null,       'float', (float) Settings_Page::get( 'acquisition_cost' )   ],
                'renewals_saved'        => [ null,       'int',   (int)   Settings_Page::get( 'renewals_saved' )     ],

                // Time spent on dog waste issues
                'complaints_per_week'   => [ null,       'int',   (int)   Settings_Page::get( 'complaints_per_week' )   ],
                'minutes_email'         => [ null,       'int',   (int)   Settings_Page::get( 'minutes_email' )          ],
                'minutes_phone'         => [ null,       'int',   (int)   Settings_Page::get( 'minutes_phone' )          ],
                'minutes_social'        => [ null,       'int',   (int)   Settings_Page::get( 'minutes_social' )         ],
                'pickup_hours_per_week' => [ null,       'float', (float) Settings_Page::get( 'pickup_hours_per_week' ) ],
            ];
        }
        return $map;
    }

    /**
     * Resolve a calculator field: URL param → Keap (Memberium) → default from field_map().
     *
     * @param string $param_key URL query key, e.g. 'd', 'u', 'hd'.
     * @return int|float
     */
    private static function resolve_field( $param_key ) {
        $map = self::field_map();
        if ( ! isset( $map[ $param_key ] ) ) {
            return 0;
        }
        list( $keap, $type, $default ) = $map[ $param_key ];

        if ( isset( $_GET[ $param_key ] ) && $_GET[ $param_key ] !== '' ) {
            $raw_get = wp_unslash( $_GET[ $param_key ] );
            return 'float' === $type ? floatval( $raw_get ) : absint( $raw_get );
        }

        if ( $keap && function_exists( 'memb_getContactField' ) ) {
            $raw = memb_getContactField( $keap, true );
            if ( is_string( $raw ) ) {
                $raw = trim( $raw );
            }
            if ( $raw !== '' && $raw !== null && false !== $raw ) {
                return 'float' === $type ? floatval( $raw ) : absint( $raw );
            }
        }

        return 'float' === $type ? floatval( $default ) : (int) $default;
    }

    /**
     * Option card swab qty: ?o1_qty_swab → ?dogs → Memberium dogs → settings default.
     * When $fallback_to_dogs is false, falls back to the swab param's own settings default instead.
     */
    private static function resolve_option_swab_qty( $swab_param, $fallback_to_dogs = false ) {
        if ( isset( $_GET[ $swab_param ] ) && $_GET[ $swab_param ] !== '' ) {
            return absint( wp_unslash( $_GET[ $swab_param ] ) );
        }
        if ( $fallback_to_dogs ) {
            return (int) self::resolve_field( 'dogs' );
        }
        return (int) self::resolve_field( $swab_param );
    }

    public static function init() {
        add_shortcode( 'pooprints_value',         [ __CLASS__, 'render_value' ] );
        add_shortcode( 'pp_value',                [ __CLASS__, 'render_value' ] );
        add_shortcode( 'pooprints_option1',       [ __CLASS__, 'render_option1' ] );
        add_shortcode( 'pooprints_option2',       [ __CLASS__, 'render_option2' ] );
        add_shortcode( 'pooprints_roi',           [ __CLASS__, 'render_roi' ] );
        add_shortcode( 'pooprints_sidenav',        [ __CLASS__, 'render_sidenav' ] );
        add_shortcode( 'pooprints_get_started',   [ __CLASS__, 'render_get_started' ] );
        add_shortcode( 'pooprints_gs_open',       [ __CLASS__, 'render_gs_open' ] );
        add_shortcode( 'pooprints_gs_close',      [ __CLASS__, 'render_gs_close' ] );
        add_shortcode( 'pooprints_fine_tables',   [ __CLASS__, 'render_fine_tables' ] );
        add_shortcode( 'pooprints_form_v2',        [ __CLASS__, 'render_form_v2' ] );
    }

    /**
     * PooPrints Form v2: values persisted directly to Keap custom fields.
     */
    public static function form_v2_direct_fields() {
        return [
            'Company'                         => 'Company',
            'Address3Street1'                 => 'Address3Street1',
            'Address3Street2'                 => 'Address3Street2',
            'City3'                           => 'City3',
            'State3'                          => 'State3',
            'PostalCode3'                     => 'PostalCode3',
            'Address2Street1'                 => 'Address2Street1',
            'Address2Street2'                 => 'Address2Street2',
            'City2'                           => 'City2',
            'State2'                          => 'State2',
            'PostalCode2'                     => 'PostalCode2',
            'StreetAddress1'                  => 'StreetAddress1',
            'StreetAddress2'                  => 'StreetAddress2',
            'City'                            => 'City',
            'State'                           => 'State',
            'PostalCode'                      => 'PostalCode',
            'Phone1'                          => 'Phone1',
            '_ofUnits'                        => '_ofUnits',
            '_PropertyManagementSoftwareUsed' => '_PropertyManagementSoftwareUsed',
        ];
    }

    /**
     * PooPrints Form v2: tag ids applied by step/answer.
     */
    public static function form_v2_tags() {
        return [
            'step_1'            => 14868,
            'step_2'            => 14870,
            'step_3'            => 14872,
            'step_4'            => 14874,
            'petscreening_yes'  => 14808,
            'petscreening_no'   => 14810,
        ];
    }

    /**
     * PooPrints Form v2: software options. Memberium can read the selected
     * contact value, but the available Keap dropdown options are configured here.
     */
    public static function form_v2_software_options() {
        $options = [
            'Appfolio',
            'DoorLoop',
            'Entrata',
            'Fortress',
            'MicrosoftDynamicsGP',
            'Propertyware',
            'RealPage OneSite',
            'Rent Manager',
            'ResMan',
            'Yardi 7S',
            'Yardi Breeze',
            'Yardi RentCafe CRM',
            'Yardi RentCafe CRM IQ',
            'Yardi Voyager',
            'Unknown',
            'None',
            'Other',
        ];

        return apply_filters( 'pooprints_form_v2_software_options', $options );
    }

    /**
     * [pooprints_form_v2]
     * PooPrints Form v2: four-step order form with Keap field updates,
     * ContactNotes appends, conditional flows, and stage tags.
     */
    public static function render_form_v2() {
        self::$enqueue_form_v2 = true;

        wp_enqueue_style(
            'pooprints-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
            [],
            null
        );

        $direct_fields = self::form_v2_direct_fields();
        $values        = [];
        foreach ( $direct_fields as $key => $field_name ) {
            $values[ $key ] = '';
            if ( function_exists( 'memb_getContactField' ) ) {
                $raw = memb_getContactField( $field_name, true );
                if ( $raw !== false && $raw !== null ) {
                    $values[ $key ] = (string) $raw;
                }
            }
        }

        $software_options = self::form_v2_software_options();
        if ( $values['_PropertyManagementSoftwareUsed'] && ! in_array( $values['_PropertyManagementSoftwareUsed'], $software_options, true ) ) {
            array_unshift( $software_options, $values['_PropertyManagementSoftwareUsed'] );
        }

        ob_start();
        ?>
        <div class="pp-form-v2" data-pp-form-v2>
            <ol class="pp-form-v2__steps" aria-label="<?php esc_attr_e( 'Order form progress', 'pooprints-calculator' ); ?>">
                <li class="is-active" data-pp-step-dot="1"><span>1</span> Property &amp; Shipping</li>
                <li data-pp-step-dot="2"><span>2</span> Billing &amp; Contact</li>
                <li data-pp-step-dot="3"><span>3</span> Organization &amp; Submit</li>
                <li data-pp-step-dot="4"><span>4</span> Confirmation</li>
            </ol>

            <div class="pp-form-v2__card">
                <form class="pp-form-v2__form" novalidate>
                    <?php wp_nonce_field( 'pooprints_form_v2', 'pooprints_form_v2_nonce' ); ?>

                    <section class="pp-form-v2__panel is-active" data-pp-step="1">
                        <h2>Property Information</h2>
                        <div class="pp-form-v2__grid">
                            <label class="pp-field pp-field--full">Property Name
                                <input name="Company" type="text" value="<?php echo esc_attr( $values['Company'] ); ?>" placeholder="Enter property name" required>
                            </label>
                            <label class="pp-field pp-field--span-8">Property Mailing Address
                                <input name="Address3Street1" type="text" value="<?php echo esc_attr( $values['Address3Street1'] ); ?>" placeholder="Enter U.S. Postal (USPS) address" required>
                            </label>
                            <label class="pp-field pp-field--span-4">Unit or Suite # or Leasing Office
                                <input name="Address3Street2" type="text" value="<?php echo esc_attr( $values['Address3Street2'] ); ?>" placeholder="Unit # or leasing office">
                            </label>
                            <label class="pp-field pp-field--span-6">City
                                <input name="City3" type="text" value="<?php echo esc_attr( $values['City3'] ); ?>" placeholder="Enter city" required>
                            </label>
                            <label class="pp-field pp-field--span-3">State
                                <input name="State3" type="text" value="<?php echo esc_attr( $values['State3'] ); ?>" placeholder="State" required>
                            </label>
                            <label class="pp-field pp-field--span-3">Zip Code
                                <input name="PostalCode3" type="text" value="<?php echo esc_attr( $values['PostalCode3'] ); ?>" placeholder="Zip code" required>
                            </label>
                            <label class="pp-field pp-field--span-6">Phone Number
                                <input name="Phone1" type="tel" value="<?php echo esc_attr( $values['Phone1'] ); ?>" placeholder="(000) 000-0000">
                            </label>
                            <label class="pp-field pp-field--span-6">Total Number of Units
                                <input name="_ofUnits" type="number" min="0" value="<?php echo esc_attr( $values['_ofUnits'] ); ?>" placeholder="Enter number of units" required>
                            </label>
                        </div>

                        <h3>Shipping Details</h3>
                        <fieldset class="pp-choice">
                            <legend>Is the property's address the shipping address?</legend>
                            <label><input type="radio" name="shipping_same" value="yes" checked> Yes</label>
                            <label><input type="radio" name="shipping_same" value="no"> No</label>
                        </fieldset>
                        <div class="pp-form-v2__conditional" data-pp-show-if="shipping_same:no">
                            <div class="pp-form-v2__grid">
                                <label class="pp-field pp-field--full">Shipping Property or Company Name
                                    <input name="shipping_name" type="text" placeholder="Property or company name">
                                </label>
                                <label class="pp-field pp-field--full">Shipping Address Line 1
                                    <input name="shipping_address1" type="text" placeholder="Address line 1">
                                </label>
                                <label class="pp-field pp-field--full">Shipping Address Line 2
                                    <input name="shipping_address2" type="text" placeholder="Address line 2">
                                </label>
                                <label class="pp-field pp-field--span-6">City
                                    <input name="shipping_city" type="text" placeholder="Enter city">
                                </label>
                                <label class="pp-field pp-field--span-3">State
                                    <input name="shipping_state" type="text" placeholder="State">
                                </label>
                                <label class="pp-field pp-field--span-3">Zip Code
                                    <input name="shipping_zip" type="text" placeholder="Zip code">
                                </label>
                            </div>
                        </div>

                        <fieldset class="pp-choice">
                            <legend>Can your property address receive USPS packages?</legend>
                            <label><input type="radio" name="usps_receive" value="yes" checked> Yes</label>
                            <label><input type="radio" name="usps_receive" value="no"> No</label>
                        </fieldset>
                        <div class="pp-form-v2__conditional" data-pp-show-if="usps_receive:no">
                            <h3>Temporary Shipping Address</h3>
                            <div class="pp-form-v2__grid">
                                <label class="pp-field pp-field--full">Property or Company Name
                                    <input name="temp_shipping_name" type="text" placeholder="Property or company name">
                                </label>
                                <label class="pp-field pp-field--full">Address Line 1
                                    <input name="temp_shipping_address1" type="text" placeholder="Address line 1">
                                </label>
                                <label class="pp-field pp-field--full">Address Line 2
                                    <input name="temp_shipping_address2" type="text" placeholder="Address line 2">
                                </label>
                                <label class="pp-field pp-field--span-6">City
                                    <input name="temp_shipping_city" type="text" placeholder="Enter city">
                                </label>
                                <label class="pp-field pp-field--span-3">State
                                    <input name="temp_shipping_state" type="text" placeholder="State">
                                </label>
                                <label class="pp-field pp-field--span-3">Zip Code
                                    <input name="temp_shipping_zip" type="text" placeholder="Zip code">
                                </label>
                            </div>
                        </div>

                        <fieldset class="pp-choice">
                            <legend>Do you want us to delay the shipment?</legend>
                            <label><input type="radio" name="delay_shipping" value="yes"> Yes</label>
                            <label><input type="radio" name="delay_shipping" value="no" checked> No</label>
                        </fieldset>
                        <div class="pp-form-v2__conditional" data-pp-show-if="delay_shipping:yes">
                            <label class="pp-field pp-field--date">If yes, what date do you want the shipment to arrive?
                                <input name="delay_date" type="date">
                            </label>
                        </div>
                    </section>

                    <section class="pp-form-v2__panel" data-pp-step="2">
                        <h2>Billing Address for Property</h2>
                        <div class="pp-form-v2__grid">
                            <label class="pp-field pp-field--full">Company Name
                                <input name="billing_company" type="text" placeholder="Enter company name">
                            </label>
                            <label class="pp-field pp-field--span-8">Billing Mailing Address
                                <input name="billing_address1" type="text" placeholder="Enter U.S. Postal (USPS) address">
                            </label>
                            <label class="pp-field pp-field--span-4">Unit or Suite # or Leasing Office
                                <input name="billing_address2" type="text" placeholder="Unit # or leasing office">
                            </label>
                            <label class="pp-field pp-field--span-6">City
                                <input name="billing_city" type="text" placeholder="Enter city">
                            </label>
                            <label class="pp-field pp-field--span-3">State
                                <input name="billing_state" type="text" placeholder="State">
                            </label>
                            <label class="pp-field pp-field--span-3">Zip Code
                                <input name="billing_zip" type="text" placeholder="Zip code">
                            </label>
                        </div>

                        <h2>Primary PooPrints Contact</h2>
                        <p class="pp-form-v2__hint">Who will be primarily handling PooPrints at your community?</p>
                        <div class="pp-form-v2__grid">
                            <label class="pp-field pp-field--span-6">First Name
                                <input name="primary_first_name" type="text" placeholder="Enter first name">
                            </label>
                            <label class="pp-field pp-field--span-6">Last Name
                                <input name="primary_last_name" type="text" placeholder="Enter last name">
                            </label>
                            <label class="pp-field pp-field--span-6">Email Address
                                <input name="primary_email" type="email" placeholder="email@example.com">
                            </label>
                            <label class="pp-field pp-field--span-6">Job Title
                                <input name="primary_job_title" type="text" placeholder="e.g. Property Manager">
                            </label>
                        </div>

                        <h2>Billing &amp; Invoicing</h2>
                        <label class="pp-field pp-field--medium">Accounts Payable Email Address for Invoices
                            <input name="ap_email" type="email" placeholder="email@example.com">
                        </label>
                        <fieldset class="pp-choice">
                            <legend>Do you currently use PetScreening?</legend>
                            <label><input type="radio" name="petscreening" value="yes"> Yes</label>
                            <label><input type="radio" name="petscreening" value="no"> No</label>
                        </fieldset>
                        <label class="pp-field pp-field--medium">What property management system software do you use?
                            <select name="_PropertyManagementSoftwareUsed">
                                <option value="">Select your property management software</option>
                                <?php foreach ( $software_options as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $values['_PropertyManagementSoftwareUsed'], $option ); ?>><?php echo esc_html( $option ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="pp-form-v2__conditional" data-pp-show-if="_PropertyManagementSoftwareUsed:Other">
                            <label class="pp-field pp-field--medium">Software Name
                                <input name="software_other" type="text" placeholder="Enter software name">
                            </label>
                        </div>
                    </section>

                    <section class="pp-form-v2__panel" data-pp-step="3">
                        <h2>Organization &amp; Submit</h2>
                        <fieldset class="pp-choice">
                            <legend>Is there a management company?</legend>
                            <label><input type="radio" name="has_management_company" value="yes" checked> Yes</label>
                            <label><input type="radio" name="has_management_company" value="no"> No</label>
                        </fieldset>

                        <div class="pp-form-v2__conditional" data-pp-show-if="has_management_company:yes">
                            <h3>Management Company</h3>
                            <div class="pp-form-v2__grid">
                                <label class="pp-field pp-field--full">Management Company Name
                                    <input name="management_company_name" type="text" placeholder="Enter management company name">
                                </label>
                                <label class="pp-field pp-field--span-8">USPS Address
                                    <input name="management_address1" type="text" placeholder="Enter U.S. Postal (USPS) address">
                                </label>
                                <label class="pp-field pp-field--span-4">Unit or Suite #
                                    <input name="management_address2" type="text" placeholder="Unit or Suite #">
                                </label>
                                <label class="pp-field pp-field--span-6">City
                                    <input name="management_city" type="text" placeholder="Enter city">
                                </label>
                                <label class="pp-field pp-field--span-3">State
                                    <input name="management_state" type="text" placeholder="State">
                                </label>
                                <label class="pp-field pp-field--span-3">Zip Code
                                    <input name="management_zip" type="text" placeholder="Zip code">
                                </label>
                            </div>
                            <h3>Community Manager</h3>
                            <div class="pp-form-v2__grid">
                                <label class="pp-field pp-field--span-6">Community Manager First Name
                                    <input name="community_manager_first_name" type="text" placeholder="First name">
                                </label>
                                <label class="pp-field pp-field--span-6">Community Manager Last Name
                                    <input name="community_manager_last_name" type="text" placeholder="Last name">
                                </label>
                                <label class="pp-field pp-field--span-6">Community Manager Email
                                    <input name="community_manager_email" type="email" placeholder="email@example.com">
                                </label>
                                <label class="pp-field pp-field--span-6">Job Title
                                    <input name="community_manager_job_title" type="text" placeholder="e.g. Community Manager">
                                </label>
                                <label class="pp-field pp-field--span-6">Regional/Asset Manager First Name
                                    <input name="regional_manager_first_name" type="text" placeholder="First name">
                                </label>
                                <label class="pp-field pp-field--span-6">Regional/Asset Manager Last Name
                                    <input name="regional_manager_last_name" type="text" placeholder="Last name">
                                </label>
                                <label class="pp-field pp-field--span-6">Regional/Asset Manager Email
                                    <input name="regional_manager_email" type="email" placeholder="email@example.com">
                                </label>
                                <label class="pp-field pp-field--span-6">Job Title
                                    <input name="regional_manager_job_title" type="text" placeholder="e.g. Regional Manager">
                                </label>
                            </div>
                        </div>

                        <div class="pp-form-v2__conditional" data-pp-show-if="has_management_company:no">
                            <fieldset class="pp-choice">
                                <legend>What type of community is this?</legend>
                                <label><input type="radio" name="community_type" value="hoa" checked> HOA</label>
                                <label><input type="radio" name="community_type" value="rental_community"> Rental Community</label>
                            </fieldset>
                        </div>

                        <div class="pp-form-v2__conditional" data-pp-show-if="community_type:rental_community">
                            <fieldset class="pp-choice">
                                <legend>Is this rental community self managed?</legend>
                                <label><input type="radio" name="self_managed" value="yes" checked> Yes</label>
                                <label><input type="radio" name="self_managed" value="no"> No</label>
                            </fieldset>
                        </div>

                        <div class="pp-form-v2__conditional" data-pp-show-if="self_managed:yes">
                            <h3>Property Owner</h3>
                            <div class="pp-form-v2__grid">
                                <label class="pp-field pp-field--span-6">Owner First Name
                                    <input name="owner_first_name" type="text" placeholder="First name">
                                </label>
                                <label class="pp-field pp-field--span-6">Owner Last Name
                                    <input name="owner_last_name" type="text" placeholder="Last name">
                                </label>
                                <label class="pp-field pp-field--span-6">Owner Email
                                    <input name="owner_email" type="email" placeholder="email@example.com">
                                </label>
                                <label class="pp-field pp-field--span-6">Phone Number
                                    <input name="owner_phone" type="tel" placeholder="(555) 555-5555">
                                </label>
                                <label class="pp-field pp-field--full">Company Name
                                    <input name="owner_company_name" type="text" placeholder="If no company name, leave blank">
                                </label>
                            </div>
                        </div>

                        <div class="pp-form-v2__conditional" data-pp-show-if="community_type:hoa">
                            <h3>Community Association Contacts</h3>
                            <div class="pp-form-v2__repeat" data-pp-hoa-list>
                                <div class="pp-form-v2__grid pp-form-v2__repeat-item">
                                    <label class="pp-field pp-field--span-6">First Name
                                        <input name="hoa_first_name[]" type="text" placeholder="First name">
                                    </label>
                                    <label class="pp-field pp-field--span-6">Last Name
                                        <input name="hoa_last_name[]" type="text" placeholder="Last name">
                                    </label>
                                    <label class="pp-field pp-field--span-6">Email
                                        <input name="hoa_email[]" type="email" placeholder="email@example.com">
                                    </label>
                                    <label class="pp-field pp-field--span-6">Role in the Association
                                        <input name="hoa_role[]" type="text" placeholder="Role in the association">
                                    </label>
                                    <fieldset class="pp-choice pp-field--full">
                                        <legend>Are you on the association board?</legend>
                                        <label><input type="radio" name="hoa_on_board[0]" value="yes"> Yes</label>
                                        <label><input type="radio" name="hoa_on_board[0]" value="no"> No</label>
                                    </fieldset>
                                </div>
                            </div>
                            <button type="button" class="pp-form-v2__ghost" data-pp-add-hoa>+ Add another contact</button>
                        </div>

                        <div class="pp-form-v2__conditional" data-pp-show-if="self_managed:no">
                            <h3>Community Manager</h3>
                            <div class="pp-form-v2__grid">
                                <label class="pp-field pp-field--span-6">Community Manager First Name
                                    <input name="community_manager_first_name" type="text" placeholder="First name">
                                </label>
                                <label class="pp-field pp-field--span-6">Community Manager Last Name
                                    <input name="community_manager_last_name" type="text" placeholder="Last name">
                                </label>
                                <label class="pp-field pp-field--span-6">Community Manager Email
                                    <input name="community_manager_email" type="email" placeholder="email@example.com">
                                </label>
                                <label class="pp-field pp-field--span-6">Job Title
                                    <input name="community_manager_job_title" type="text" placeholder="e.g. Community Manager">
                                </label>
                                <label class="pp-field pp-field--span-6">Regional/Asset Manager First Name
                                    <input name="regional_manager_first_name" type="text" placeholder="First name">
                                </label>
                                <label class="pp-field pp-field--span-6">Regional/Asset Manager Last Name
                                    <input name="regional_manager_last_name" type="text" placeholder="Last name">
                                </label>
                                <label class="pp-field pp-field--span-6">Regional/Asset Manager Email
                                    <input name="regional_manager_email" type="email" placeholder="email@example.com">
                                </label>
                                <label class="pp-field pp-field--span-6">Job Title
                                    <input name="regional_manager_job_title" type="text" placeholder="e.g. Regional Manager">
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="pp-form-v2__panel pp-form-v2__thanks" data-pp-step="4">
                        <div class="pp-form-v2__check" aria-hidden="true">&#10003;</div>
                        <h2>Thank you!</h2>
                        <p>Once your order is processed (usually the same business day), we'll email you tracking details, your invoice, and next steps.</p>
                        <hr>
                        <p>To make sure our emails reach your inbox, click the link below to add us to your contacts.</p>
                        <a class="pp-form-v2__outline" href="mailto:hello@pooprints.com">Add to Contacts</a>
                        <a class="pp-form-v2__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">Return to Home</a>
                    </section>

                    <div class="pp-form-v2__notice" data-pp-form-notice role="status" aria-live="polite"></div>
                    <div class="pp-form-v2__actions">
                        <button type="button" class="pp-form-v2__back" data-pp-prev>Back</button>
                        <button type="button" class="pp-form-v2__next" data-pp-next>Continue</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [pooprints_value key="units"]
     * Outputs <span data-key="{key}">{default}</span>.
     * JS updates the span content live as inputs change.
     */
    public static function render_value( $atts ) {
        self::$enqueue_assets = true;

        $atts = shortcode_atts( [ 'key' => '' ], $atts, 'pooprints_value' );
        $key  = sanitize_key( $atts['key'] );

        // Numeric price/stat keys rendered by PHP (no $ prefix — template adds it where needed).
        $php_dollar_keys = [
            'price_swab_kit', 'price_waste_kit', 'price_waste_test_report', 'price_waste_sample_test',
            'price_subscription_fee', 'price_subscription_fee_small',
            'price_unentered_reg_card', 'price_dna_verification_test', 'price_failed_waste_testing', 'price_resident_pay_charge',
            'rate_profit_per_test',
            'stat_loss_charge_sample_only', 'stat_recommended_fine', 'stat_daily_fine_refuse_register',
            'stat_waste_test_cost_match_no_match', 'stat_breakeven_prime_tiered_small', 'stat_breakeven_prime_tiered_large',
            'stat_fine_low_end', 'stat_fine_high_end', 'stat_prime_pet_fee_increase', 'stat_tiered_pet_fee_increase',
            'stat_avg_match_rate', 'stat_waste_samples_analyzed',
        ];
        // Raw text stat keys rendered by PHP as-is.
        $php_stat_keys = [
            'stat_pooprints_properties', 'stat_five_star_reviews', 'stat_years_in_business',
            'stat_recommended_fine_row', 'stat_breakeven_fine_row',
            'stat_pet_rent_increase_tiered', 'stat_pet_rent_increase_prime',
        ];
        $allowed_keys = array_merge(
            [
                'opt1_qty_swab', 'opt1_qty_waste', 'total_units', 'total_dogs',
                'opt1_payment_monthly', 'opt1_payment_single', 'opt1_payment_12total', 'opt1_payment_savings',
                'roi_savings_total', 'roi_net_return',
                'opt2_qty_swab', 'opt2_payment_single',
                'roi_hours_saved', 'roi_cash_savings', 'roi_fees_recovered',
                'roi_turnover_saved', 'roi_acquisition_saved',
            ],
            $php_dollar_keys,
            $php_stat_keys
        );

        if ( ! in_array( $key, $allowed_keys, true ) ) {
            return '';
        }
        if ( in_array( $key, $php_dollar_keys, true ) ) {
            $val = (float) Settings_Page::get( $key );
            return sprintf(
                '<span data-pp-key="%s">%s</span>',
                esc_attr( $key ),
                esc_html( number_format( $val, 2 ) )
            );
        }

        if ( in_array( $key, $php_stat_keys, true ) ) {
            $val = Settings_Page::get( $key );
            return sprintf(
                '<span data-pp-key="%s">%s</span>',
                esc_attr( $key ),
                esc_html( $val )
            );
        }

        return sprintf(
            '<span data-pp-key="%s">—</span>',
            esc_attr( $key )
        );
    }

    /* ----------------------------------------------------------------
       [pooprints_option1]
       Pre-fill priority: $_GET param → saved setting → hardcoded default
    ---------------------------------------------------------------- */
    public static function render_option1( $atts = [] ) {
        self::$enqueue_assets = true;

        $atts     = shortcode_atts( [ 'template' => '1' ], $atts, 'pooprints_option1' );
        $template = sanitize_key( $atts['template'] );

        $qty_swab  = self::resolve_option_swab_qty( 'o1_qty_swab', true );
        $qty_waste = (int) self::resolve_field( 'o1_qty_waste' );

        // Pre-calculate display values so the card renders correctly before JS runs.
        $swab_kit         = (float) Settings_Page::get('price_swab_kit');
        $waste_kit        = (float) Settings_Page::get('price_waste_kit');
        $setup_fee        = (float) Settings_Page::get('price_setup_fee');
        $subscription_fee = (float) Settings_Page::get('price_subscription_fee');
        $single_discount  = (float) Settings_Page::get('price_single_pay_discount') / 100;
        $savings_pct      = round( ( 1 - $single_discount ) * 100 );

        $swab_cost             = $qty_swab  * $swab_kit;
        $waste_cost            = $qty_waste * $waste_kit;
        $total_before_discount = $swab_cost + $waste_cost + $setup_fee + $subscription_fee;
        $single_payment        = $total_before_discount;
        $monthly               = $total_before_discount / $single_discount / 12;

        if ( '2' === $template ) {
            ob_start();
            ?>
            <div class="pp-getstarted">
              <div class="pp-table-wrap" id="pp-table-opt1">
                <div class="pp-table-title">Option 1 &#8212; Fastest Results &#8212; Full Rollout</div>
                <div class="pp-table-banner">Register every dog at the start for fastest results.</div>
                <table class="pp-table">
                  <thead>
                    <tr>
                      <th></th>
                      <th>Qty</th>
                      <th>$ Each</th>
                      <th>Cost</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <span class="pp-row-label">DNA Swab Kit</span>
                        <span class="pp-qty-badge">&#215; <?php echo esc_html( $qty_swab ); ?></span>
                      </td>
                      <td class="pp-text-center pp-col-qty"><input type="number" class="pp-qty" value="<?php echo esc_attr( $qty_swab ); ?>" min="0" data-pp-price="<?php echo esc_attr( $swab_kit ); ?>" data-pp-line="dna"></td>
                      <td class="pp-text-center pp-col-each">$<?php echo esc_html( number_format( $swab_kit, 2 ) ); ?></td>
                      <td class="pp-cost" data-pp-cost="dna">$<?php echo esc_html( number_format( $swab_cost, 2 ) ); ?></td>
                    </tr>
                    <tr>
                      <td>
                        <span class="pp-row-label">Waste Sample Collection Kit</span>
                        <span class="pp-qty-badge">&#215; <?php echo esc_html( $qty_waste ); ?></span>
                      </td>
                      <td class="pp-text-center pp-col-qty"><input type="number" class="pp-qty" value="<?php echo esc_attr( $qty_waste ); ?>" min="0" data-pp-price="<?php echo esc_attr( $waste_kit ); ?>" data-pp-line="waste"></td>
                      <td class="pp-text-center pp-col-each">$<?php echo esc_html( number_format( $waste_kit, 2 ) ); ?></td>
                      <td class="pp-cost" data-pp-cost="waste">$<?php echo esc_html( number_format( $waste_cost, 2 ) ); ?></td>
                    </tr>
                    <tr class="pp-row-noqty">
                      <td>
                        <span class="pp-row-label">One-Time Setup Fee <span class="pp-info" title="Setup fee details">i</span></span>
                        <span class="pp-row-sub">Includes setup in World Pet Registry, training, and resident materials.</span>
                      </td>
                      <td class="pp-col-qty"></td>
                      <td class="pp-col-each"></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $setup_fee, 2 ) ); ?></td>
                    </tr>
                    <tr class="pp-row-noqty">
                      <td>
                        <span class="pp-row-label">Annual Subscription Fee <span class="pp-info" title="Subscription details">i</span></span>
                        <span class="pp-row-sub">Ongoing support and enhancements.</span>
                      </td>
                      <td class="pp-col-qty"></td>
                      <td class="pp-col-each"></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $subscription_fee, 2 ) ); ?></td>
                    </tr>
                  </tbody>
                </table>
                <div class="pp-table-footer">
                  <div class="pp-table-footer-row">
                    <strong>Single Payment &#8212; Total Cost (Save <?php echo esc_html( $savings_pct ); ?>%)</strong>
                    <span class="pp-amount" data-pp-total="opt1-t2-single">$<?php echo esc_html( number_format( $single_payment, 2 ) ); ?></span>
                  </div>
                  <div class="pp-table-footer-row">
                    <strong>12 Monthly Payments (Per Month)</strong>
                    <span class="pp-amount" data-pp-total="opt1-t2-monthly">$<?php echo esc_html( number_format( $monthly, 2 ) ); ?></span>
                  </div>
                </div>
              </div>
            </div>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="card-container">
          <div id="option1Card" class="option-card">
            <div class="option-card-header">Option 1 &mdash; Register All Dogs Now</div>
            <div class="option-card-subheader">Full compliance and enforcement on day one.</div>
            <div class="option-card-grid-scroll">
              <div class="option-card-grid">
                <!-- Header row -->
                <div class="option-card-corner"></div>
                <div class="option-card-cell card-head">Qty</div>
                <div class="option-card-cell card-head">$ Each</div>
                <div class="option-card-cell card-head card-cost">Cost</div>
                <!-- DNA Swab Kit -->
                <div class="option-card-cell card-label">DNA Swab Kit</div>
                <div class="option-card-cell card-qty-cell">
                  <input class="option-card-qty-input" type="number" id="o1_qty_swab" value="<?php echo esc_attr( $qty_swab ); ?>" min="0">
                </div>
                <div class="option-card-cell">$<?php echo esc_html( number_format( $swab_kit, 2 ) ); ?></div>
                <div class="option-card-cell card-cost" id="o1_swab_cost">$<?php echo esc_html( number_format( $swab_cost, 2 ) ); ?></div>
                <!-- Waste Sample Collection Kit -->
                <div class="option-card-cell card-label">Waste Sample Collection Kit</div>
                <div class="option-card-cell card-qty-cell">
                  <input class="option-card-qty-input" type="number" id="o1_qty_waste" value="<?php echo esc_attr( $qty_waste ); ?>" min="0">
                </div>
                <div class="option-card-cell">$<?php echo esc_html( number_format( $waste_kit, 2 ) ); ?></div>
                <div class="option-card-cell card-cost" id="o1_waste_cost">$<?php echo esc_html( number_format( $waste_cost, 2 ) ); ?></div>
                <!-- One-Time Setup Fee -->
                <div class="option-card-cell card-label card-span3">One-Time Setup Fee</div>
                <div class="option-card-cell card-cost">$<?php echo esc_html( number_format( $setup_fee, 2 ) ); ?></div>
                <!-- Annual Subscription Fee -->
                <div class="option-card-cell card-label card-span3">Annual Subscription Fee</div>
                <div class="option-card-cell card-cost">$<?php echo esc_html( number_format( $subscription_fee, 2 ) ); ?></div>
                <!-- Summary rows -->
                <div class="option-card-cell card-label card-span3 card-highlight">Single Payment &mdash; Total Cost (Save <?php echo esc_html( $savings_pct ); ?>%)</div>
                <div class="option-card-cell card-highlight-cost" id="o1_single_payment">$<?php echo esc_html( number_format( $single_payment, 2 ) ); ?></div>
                <div class="option-card-cell card-label card-span3 card-highlight">12 Monthly Payments (Per Month)</div>
                <div class="option-card-cell card-highlight-cost" id="o1_monthly_payment">$<?php echo esc_html( number_format( $monthly, 2 ) ); ?></div>
              </div>
            </div>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ----------------------------------------------------------------
       [pooprints_option2]
       Pre-fill priority: $_GET param → saved setting → hardcoded default
    ---------------------------------------------------------------- */
    public static function render_option2( $atts = [] ) {
        self::$enqueue_assets = true;

        $atts     = shortcode_atts( [ 'template' => '1' ], $atts, 'pooprints_option2' );
        $template = sanitize_key( $atts['template'] );

        $qty_swab = self::resolve_option_swab_qty( 'o2_qty_swab' );

        $swab_kit         = (float) Settings_Page::get('price_swab_kit');
        $setup_fee        = (float) Settings_Page::get('price_setup_fee');
        $subscription_fee = (float) Settings_Page::get('price_subscription_fee');

        $swab_cost = $qty_swab * $swab_kit;
        $total_2   = $swab_cost + $setup_fee + $subscription_fee;

        if ( '2' === $template ) {
            ob_start();
            ?>
            <div class="pp-getstarted">
              <div class="pp-table-wrap" id="pp-table-opt2">
                <div class="pp-table-title">Option 2 &#8212; Save Money &#8212; Phased Rollout</div>
                <div class="pp-table-banner">Start with fewer dogs; add more as leases renew.</div>
                <table class="pp-table">
                  <thead>
                    <tr>
                      <th></th>
                      <th>Qty</th>
                      <th>$ Each</th>
                      <th>Cost</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <span class="pp-row-label">DNA Swab Kit</span>
                        <span class="pp-qty-badge">&#215; <?php echo esc_html( $qty_swab ); ?></span>
                      </td>
                      <td class="pp-text-center pp-col-qty"><input type="number" class="pp-qty" value="<?php echo esc_attr( $qty_swab ); ?>" min="0" data-pp-price="<?php echo esc_attr( $swab_kit ); ?>" data-pp-line="dna2"></td>
                      <td class="pp-text-center pp-col-each">$<?php echo esc_html( number_format( $swab_kit, 2 ) ); ?></td>
                      <td class="pp-cost" data-pp-cost="dna2">$<?php echo esc_html( number_format( $swab_cost, 2 ) ); ?></td>
                    </tr>
                    <tr class="pp-row-noqty">
                      <td>
                        <span class="pp-row-label" style="color:var(--pp-text-muted)">Waste Sample Collection Kit</span>
                        <span class="pp-row-sub">Add later</span>
                      </td>
                      <td class="pp-text-center pp-col-qty" style="color:var(--pp-text-muted);font-size:13px;font-weight:600;">Add later</td>
                      <td class="pp-text-center pp-col-each" style="color:var(--pp-text-muted)">$<?php echo esc_html( number_format( (float) Settings_Page::get('price_waste_kit'), 2 ) ); ?></td>
                      <td class="pp-cost" style="color:var(--pp-text-muted)">&#8212;</td>
                    </tr>
                    <tr class="pp-row-noqty">
                      <td>
                        <span class="pp-row-label">One-Time Setup Fee <span class="pp-info" title="Setup fee details">i</span></span>
                        <span class="pp-row-sub">Includes setup in World Pet Registry, training, and resident materials.</span>
                      </td>
                      <td class="pp-col-qty"></td>
                      <td class="pp-col-each"></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $setup_fee, 2 ) ); ?></td>
                    </tr>
                    <tr class="pp-row-noqty">
                      <td>
                        <span class="pp-row-label">Annual Subscription Fee <span class="pp-info" title="Subscription details">i</span></span>
                        <span class="pp-row-sub">Ongoing support and enhancements.</span>
                      </td>
                      <td class="pp-col-qty"></td>
                      <td class="pp-col-each"></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $subscription_fee, 2 ) ); ?></td>
                    </tr>
                  </tbody>
                </table>
                <div class="pp-table-footer">
                  <div class="pp-table-footer-row">
                    <strong>Single Payment &#8212; Total Cost</strong>
                    <span class="pp-amount" data-pp-total="opt2-t2-single">$<?php echo esc_html( number_format( $total_2, 2 ) ); ?></span>
                  </div>
                  <div class="pp-table-footer-row">
                    <strong>12 Monthly Payments (Per Month)</strong>
                    <span class="pp-amount" data-pp-total="opt2-t2-monthly">$<?php echo esc_html( number_format( $total_2 / 12, 2 ) ); ?></span>
                  </div>
                </div>
              </div>
            </div>
            <?php
            return ob_get_clean();
        }

        ob_start();
        ?>
        <div class="card-container">
          <div id="option2Card" class="option-card">
            <div class="option-card-header">Option 2 &mdash; Register at Lease Renewal</div>
            <div class="option-card-subheader">Lowest upfront cost with a smooth 12-month rollout.</div>
            <div class="option-card-grid-scroll">
              <div class="option-card-grid">
                <!-- Header row -->
                <div class="option-card-corner"></div>
                <div class="option-card-cell card-head">Qty</div>
                <div class="option-card-cell card-head">$ Each</div>
                <div class="option-card-cell card-head card-cost">Cost</div>
                <!-- DNA Swab Kit -->
                <div class="option-card-cell card-label">DNA Swab Kit</div>
                <div class="option-card-cell card-qty-cell">
                  <input class="option-card-qty-input" type="number" id="o2_qty_swab" value="<?php echo esc_attr( $qty_swab ); ?>" min="0">
                </div>
                <div class="option-card-cell">$<?php echo esc_html( number_format( $swab_kit, 2 ) ); ?></div>
                <div class="option-card-cell card-cost" id="o2_swab_cost">$<?php echo esc_html( number_format( $swab_cost, 2 ) ); ?></div>
                <!-- One-Time Setup Fee -->
                <div class="option-card-cell card-label card-span3">One-Time Setup Fee</div>
                <div class="option-card-cell card-cost">$<?php echo esc_html( number_format( $setup_fee, 2 ) ); ?></div>
                <!-- Annual Subscription Fee -->
                <div class="option-card-cell card-label card-span3">Annual Subscription Fee</div>
                <div class="option-card-cell card-cost">$<?php echo esc_html( number_format( $subscription_fee, 2 ) ); ?></div>
                <!-- Summary row -->
                <div class="option-card-cell card-label card-span3 card-highlight">Total Cost (Single Payment)</div>
                <div class="option-card-cell card-highlight-cost" id="o2_total">$<?php echo esc_html( number_format( $total_2, 2 ) ); ?></div>
              </div>
            </div>
            <div class="o2-footnote">(Waste collection kits are not needed until all dogs have been registered.)</div>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ----------------------------------------------------------------
       [pooprints_roi]
       Pre-fill priority: $_GET param → hardcoded default
       Whole-number inputs: absint()   Decimal inputs: floatval()
    ---------------------------------------------------------------- */
    public static function render_roi() {
        self::$enqueue_assets = true;

        // Whole-number inputs (URL → Keap → default via field_map)
        $units               = (int) self::resolve_field( 'units' );
        $dogs                = (int) self::resolve_field( 'dogs' );
        $hidden_dogs         = (int) self::resolve_field( 'hidden_dogs' );
        $renewals_saved      = (int) self::resolve_field( 'renewals_saved' );
        $complaints_per_week = (int) self::resolve_field( 'complaints_per_week' );
        $minutes_email       = (int) self::resolve_field( 'minutes_email' );
        $minutes_phone       = (int) self::resolve_field( 'minutes_phone' );
        $minutes_social      = (int) self::resolve_field( 'minutes_social' );

        // Decimal inputs
        $rent_per_dog     = (float) self::resolve_field( 'rent_per_dog' );
        $dog_fee          = (float) self::resolve_field( 'dog_fee' );
        $turnover_cost    = (float) self::resolve_field( 'turnover_cost' );
        $acquisition_cost = (float) self::resolve_field( 'acquisition_cost' );
        $staff_wage       = (float) self::resolve_field( 'staff_wage' );
        $pickup_hours_pw  = (float) self::resolve_field( 'pickup_hours_per_week' );

        ob_start();
        ?>
        <div class="roi-masthead">
          <span class="roi-masthead__title"><?php echo esc_html( Settings_Page::get( 'roi_masthead_title' ) ); ?></span>
          <a class="roi-masthead__toggle" id="roiExpandToggle" href="#" onclick="roiToggleAll(this); return false;">&#9660; Expand All</a>
          <a class="roi-masthead__reset" href="#" onclick="roiResetDefaults(); return false;">&#8635; Reset</a>
        </div>
        <div id="roiCard">
          <div class="roi-layout">

            <!-- ============ LEFT COLUMN: Results ============ -->
            <div class="roi-left">

              <div class="roi-section-header">PooPrints Return on Investment Summary</div>

              <!-- GROUP A wrapper: toggle first in DOM, detail second.
                   column-reverse makes detail render above toggle. -->
              <div class="roi-group-wrapper">
                <!-- GROUP A toggle (DOM first → visually bottom) -->
                <div class="roi-row green-toggle" onclick="toggleSection('groupA', this.querySelector('.row-arrow'))">
                  <span class="row-arrow">&#9658;</span>
                  <span class="row-label">Total Cash Savings and Recovered Fees</span>
                  <span class="row-value" id="roi_total_cash_and_fees">$12,981</span>
                </div>
                <!-- GROUP A detail (DOM second → visually top) -->
                <div id="groupA" style="display:none;">
                  <div class="roi-row">
                    <span class="row-label">Annual Turnover Cost Saved</span>
                    <span class="row-value" id="roi_turnover_saved">$4,000</span>
                  </div>
                  <div class="roi-row">
                    <span class="row-label">Annual Renter Acquisition Costs Saved (No Longer Needed)</span>
                    <span class="row-value" id="roi_acquisition_saved">$4,220</span>
                  </div>
                  <div class="roi-row">
                    <span class="row-label">Annual Profit Testing Un-Scooped Waste (Based on Average Test Results)</span>
                    <span class="row-value" id="roi_waste_test_profit">$261</span>
                  </div>
                  <div class="roi-row roi-bold-row">
                    <span class="row-label">Subtotal Cost Savings</span>
                    <span class="row-value" id="roi_total_cash_savings">$8,481</span>
                  </div>
                  <div class="roi-row">
                    <span class="row-label">Fees Recovered Due to Finding Hidden Dogs Using PooPrints</span>
                    <span class="row-value" id="roi_fees_recovered">$4,500</span>
                  </div>
                </div>
              </div>

              <!-- GROUP B wrapper: toggle first in DOM, detail second. -->
              <div class="roi-group-wrapper">
                <!-- GROUP B toggle (DOM first → visually bottom) -->
                <div class="roi-row green-toggle" onclick="toggleSection('groupB', this.querySelector('.row-arrow'))">
                  <span class="row-arrow">&#9658;</span>
                  <span class="row-label">Value of Time Saved Handling Dog Waste Complaints and Clean Up</span>
                  <span class="row-value" id="roi_time_value">$5,642</span>
                </div>
                <!-- GROUP B detail (DOM second → visually top) -->
                <div id="groupB" style="display:none;">
                  <div class="roi-row">
                    <span class="row-label">Annual Communication Hours Saved</span>
                    <span class="row-value" id="roi_complaint_hours_saved">30.33</span>
                  </div>
                  <div class="roi-row">
                    <span class="row-label">Annual Pickup Hours Saved</span>
                    <span class="row-value" id="roi_pickup_hours_saved">148.20</span>
                  </div>
                  <div class="roi-row roi-bold-row">
                    <span class="row-label">Total Annual Staff Time Saved in Hours</span>
                    <span class="row-value" id="roi_total_hours_saved">178.53</span>
                  </div>
                </div>
              </div>

              <!-- Always-visible summary rows -->
              <div class="roi-row green-summary">
                <span class="row-label">Total Savings All Items</span>
                <span class="row-value" id="roi_total_all_savings">$18,623</span>
              </div>
              <div class="roi-row grey-row">
                <span class="row-label">Subtract Your PooPrints Investment Quote</span>
                <span class="row-value" id="roi_investment_display">&minus;$1,393</span>
              </div>
              <div class="roi-row final-row">
                <span class="row-label">Return on Your PooPrints Investment in First Year</span>
                <span class="row-value" id="roi_final">$17,229</span>
              </div>

            </div><!-- /roi-left -->

            <!-- ============ RIGHT COLUMN: Inputs ============ -->
            <div class="roi-right">

              <div class="roi-column-header">Customize the Choices to Your Business</div>

              <!-- SECTION 1: Community Information (expanded) -->
              <div class="roi-input-section">
                <div class="roi-input-header" style="background: var(--color-roi-community-bg);"
                     onclick="toggleSection('roiCommunity', this.querySelector('.section-arrow'))">
                  <span class="section-arrow">&#9660;</span>
                  Community Information
                </div>
                <div id="roiCommunity" class="is-open" style="--section-border-color: var(--color-roi-community-bg);">
                  <div class="roi-input-row">
                    <div class="roi-input-label">Total # of Units</div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="units" value="<?php echo esc_attr( $units ); ?>" min="0">
                    </div>
                  </div>
                  <div class="roi-input-row">
                    <div class="roi-input-label">
                      Total # of Dogs (Including ESA Dogs)
                      <span class="roi-input-hint">If Unsure, Use 20% of Total # of Units</span>
                    </div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="dogs" value="<?php echo esc_attr( $dogs ); ?>" min="0">
                    </div>
                  </div>

                  <div class="fewer-choices-toggle" onclick="toggleSection('roiCommunityExtra', this)" id="fewerChoicesBtn">
                    &#9658; More Choices
                  </div>

                  <div id="roiCommunityExtra" style="display:none;">
                    <div class="roi-input-row">
                      <div class="roi-input-label">Monthly Rent per Dog</div>
                      <div class="roi-input-field roi-input-field--currency">
                        <span class="roi-input-prefix">$</span>
                        <input class="roi-num-input" type="number" id="rent_per_dog" value="<?php echo esc_attr( Settings_Page::format_smart_decimal( $rent_per_dog ) ); ?>" min="0">
                      </div>
                    </div>
                    <div class="roi-input-row">
                      <div class="roi-input-label">Non-Refundable Dog Fee per Dog</div>
                      <div class="roi-input-field roi-input-field--currency">
                        <span class="roi-input-prefix">$</span>
                        <input class="roi-num-input" type="number" id="dog_fee" value="<?php echo esc_attr( Settings_Page::format_smart_decimal( $dog_fee ) ); ?>" min="0">
                      </div>
                    </div>
                    <div class="roi-input-row">
                      <div class="roi-input-label">
                        Estimated # of Unknown (Hidden) Dogs
                        <span class="roi-input-hint">At Your Property</span>
                      </div>
                      <div class="roi-input-field">
                        <input class="roi-num-input" type="number" id="hidden_dogs" value="<?php echo esc_attr( $hidden_dogs ); ?>" min="0">
                      </div>
                    </div>
                    <div class="roi-input-row">
                      <div class="roi-input-label">
                        Average Staff Wage per Hour
                        <span class="roi-input-hint">(Including Bonuses and Benefit Costs)</span>
                      </div>
                      <div class="roi-input-field roi-input-field--currency">
                        <span class="roi-input-prefix">$</span>
                        <input class="roi-num-input" type="number" id="staff_wage" value="<?php echo esc_attr( Settings_Page::format_smart_decimal( $staff_wage ) ); ?>" min="0" step="0.01">
                      </div>
                    </div>
                  </div><!-- /roiCommunityExtra -->
                </div><!-- /roiCommunity -->
              </div><!-- /section 1 -->

              <!-- SECTION 2: Turnover Costs (collapsed) -->
              <div class="roi-input-section">
                <div class="roi-input-header" style="background: var(--color-roi-turnover-bg);"
                     onclick="toggleSection('roiTurnover', this.querySelector('.section-arrow'))">
                  <span class="section-arrow">&#9658;</span>
                  Turnover Costs
                </div>
                <div id="roiTurnover" style="display:none; --section-border-color: var(--color-roi-turnover-bg);">
                  <div class="roi-input-row">
                    <div class="roi-input-label">Average Turnover Cost per Unit</div>
                    <div class="roi-input-field roi-input-field--currency">
                      <span class="roi-input-prefix">$</span>
                      <input class="roi-num-input" type="number" id="turnover_cost" value="<?php echo esc_attr( Settings_Page::format_smart_decimal( $turnover_cost ) ); ?>" min="0">
                    </div>
                  </div>
                  <div class="roi-input-row">
                    <div class="roi-input-label">
                      Average Renter Acquisition Cost
                      <span class="roi-input-hint">Advertising, Showing, Screening &amp; Admin</span>
                    </div>
                    <div class="roi-input-field roi-input-field--currency">
                      <span class="roi-input-prefix">$</span>
                      <input class="roi-num-input" type="number" id="acquisition_cost" value="<?php echo esc_attr( Settings_Page::format_smart_decimal( $acquisition_cost ) ); ?>" min="0">
                    </div>
                  </div>
                  <div class="roi-input-row">
                    <div class="roi-input-label">Estimated # of Lease Renewals Saved Annually</div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="renewals_saved" value="<?php echo esc_attr( $renewals_saved ); ?>" min="0">
                    </div>
                  </div>
                </div><!-- /roiTurnover -->
              </div><!-- /section 2 -->

              <!-- SECTION 3: Time Spent on Dog Waste Issues (collapsed) -->
              <div class="roi-input-section">
                <div class="roi-input-header" style="background: var(--color-roi-time-bg);"
                     onclick="toggleSection('roiTime', this.querySelector('.section-arrow'))">
                  <span class="section-arrow">&#9658;</span>
                  Time Spent on Dog Waste Issues (Including Interruption Time)
                </div>
                <div id="roiTime" style="display:none; --section-border-color: var(--color-roi-time-bg);">
                  <div class="roi-input-row">
                    <div class="roi-input-label">
                      Average # of Dog Waste Complaints per Week
                      <span class="roi-input-hint">(Emails, Phone, Social Media, Office Visits)</span>
                    </div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="complaints_per_week" value="<?php echo esc_attr( $complaints_per_week ); ?>" min="0">
                    </div>
                  </div>
                  <div class="roi-input-row">
                    <div class="roi-input-label"># of Minutes per Email to Reply to Email Complaints</div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="minutes_email" value="<?php echo esc_attr( $minutes_email ); ?>" min="0">
                    </div>
                  </div>
                  <div class="roi-input-row">
                    <div class="roi-input-label"># of Minutes per Phone Call About Dog Waste</div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="minutes_phone" value="<?php echo esc_attr( $minutes_phone ); ?>" min="0">
                    </div>
                  </div>
                  <div class="roi-input-row">
                    <div class="roi-input-label"># of Minutes per Social Media Post to Reply</div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="minutes_social" value="<?php echo esc_attr( $minutes_social ); ?>" min="0">
                    </div>
                  </div>
                  <div class="roi-input-row">
                    <div class="roi-input-label"># of Hours per Week Spent Surveying Grounds &amp; Picking Up</div>
                    <div class="roi-input-field">
                      <input class="roi-num-input" type="number" id="pickup_hours_per_week" value="<?php echo esc_attr( Settings_Page::format_smart_decimal( $pickup_hours_pw ) ); ?>" min="0" step="0.5">
                    </div>
                  </div>
                </div><!-- /roiTime -->
              </div><!-- /section 3 -->

            </div><!-- /roi-right -->

          </div><!-- /roi-layout -->
        </div><!-- /roiCard -->
        <?php
        return ob_get_clean();
    }

    /**
     * [pooprints_sidenav]
     * Sticky section nav from a WordPress menu (top-level links only).
     * Menu: Settings → General → Side navigation menu, or [pooprints_sidenav menu="slug-or-id"].
     */
    public static function render_sidenav( $atts ) {
        self::$enqueue_sidenav = true;

        $atts = shortcode_atts(
            [ 'menu' => '' ],
            $atts,
            'pooprints_sidenav'
        );

        $menu_id = 0;
        if ( $atts['menu'] !== '' ) {
            $menu_obj = wp_get_nav_menu_object( $atts['menu'] );
            if ( $menu_obj ) {
                $menu_id = (int) $menu_obj->term_id;
            }
        } else {
            $menu_id = absint( Settings_Page::get( 'sidenav_menu_id' ) );
        }

        if ( ! $menu_id ) {
            return '';
        }

        $items = wp_get_nav_menu_items( $menu_id );
        if ( empty( $items ) ) {
            return '';
        }

        $top_level = [];
        foreach ( $items as $item ) {
            if ( (int) $item->menu_item_parent === 0 ) {
                $top_level[] = $item;
            }
        }

        if ( empty( $top_level ) ) {
            return '';
        }

        $nav_args = (object) [ 'menu' => $menu_id ];

        $links = '';
        foreach ( $top_level as $item ) {
            $title = apply_filters( 'nav_menu_item_title', $item->title, $item, $nav_args, 0 );

            $target_blank = ( ! empty( $item->target ) && '_blank' === $item->target )
                || ( isset( $item->ID ) && '_blank' === get_post_meta( $item->ID, '_menu_item_target', true ) );

            $attrs = '';
            if ( $target_blank ) {
                $attrs = ' target="_blank" rel="noopener noreferrer"';
            }

            $links .= sprintf(
                '<a class="pp-sidenav__item" href="%s"%s>%s</a>',
                esc_url( $item->url ),
                $attrs,
                esc_html( wp_strip_all_tags( $title ) )
            );
        }

        return sprintf(
            '<nav class="pp-sidenav" aria-label="%s">%s</nav>',
            esc_attr__( 'Section navigation', 'pooprints-calculator' ),
            $links
        );
    }

    /* ----------------------------------------------------------------
       [pooprints_gs_open]
       Opens the .pp-getstarted wrapper, enqueues assets + Inter font.
       Place at the top of the Elementor page above all section widgets.
    ---------------------------------------------------------------- */
    public static function render_gs_open() {
        self::$enqueue_assets      = true;
        self::$enqueue_get_started = true;

        wp_enqueue_style(
            'pooprints-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
            [],
            null
        );

        return '<div class="pp-getstarted"><!-- pp-getstarted open -->';
    }

    /* ----------------------------------------------------------------
       [pooprints_gs_close]
       Closes the .pp-getstarted wrapper and outputs the recalc JS.
       Place at the bottom of the Elementor page below all section widgets.
       Also resolves prices from settings for the JS fallback values.
    ---------------------------------------------------------------- */
    public static function render_gs_close() {
        $setup_fee       = (float) Settings_Page::get( 'price_setup_fee' );
        $sub_fee         = (float) Settings_Page::get( 'price_subscription_fee' );
        $single_discount = (float) Settings_Page::get( 'price_single_pay_discount' ) / 100;

        ob_start();
        ?>
        <script>
        (function(){
          var footer1 = document.querySelector('.pp-getstarted #pp-table-opt1 .pp-table-footer');
          var fixedFees1 = footer1
            ? (parseFloat(footer1.dataset.ppSetup) || 0) + (parseFloat(footer1.dataset.ppSub) || 0)
            : <?php echo esc_js( $setup_fee + $sub_fee ); ?>;
          var discount1 = footer1 ? (parseFloat(footer1.dataset.ppDiscount) || 0.9) : <?php echo esc_js( $single_discount ); ?>;

          var footer2 = document.querySelector('.pp-getstarted #pp-table-opt2 .pp-table-footer');
          var fixedFees2 = footer2
            ? (parseFloat(footer2.dataset.ppSetup) || 0) + (parseFloat(footer2.dataset.ppSub) || 0)
            : <?php echo esc_js( $setup_fee + $sub_fee ); ?>;

          function fmt(n){ return '$' + n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

          function recalcOpt1(){
            var inputs = document.querySelectorAll('.pp-getstarted #pp-table-opt1 [data-pp-line]');
            var subtotal = fixedFees1;
            inputs.forEach(function(inp){
              var qty = parseInt(inp.value,10)||0;
              var price = parseFloat(inp.dataset.ppPrice)||0;
              var line = inp.dataset.ppLine;
              var cost = qty * price;
              var el = document.querySelector('.pp-getstarted [data-pp-cost="'+line+'"]');
              if(el) el.textContent = fmt(cost);
              subtotal += cost;
            });
            var single  = subtotal * discount1;
            var monthly = subtotal / 12;
            var saving  = subtotal - single;
            var el;
            el = document.querySelector('.pp-getstarted [data-pp-total="single"]');   if(el) el.textContent = fmt(single);
            el = document.querySelector('.pp-getstarted [data-pp-total="monthly"]');  if(el) el.textContent = fmt(monthly);
            el = document.getElementById('pp-plan-full-price');    if(el) el.textContent = fmt(single);
            el = document.getElementById('pp-plan-full-savings');  if(el) el.textContent = 'Save '+fmt(saving)+' vs. monthly plan';
            el = document.getElementById('pp-plan-monthly-price'); if(el) el.textContent = fmt(monthly);
            el = document.getElementById('pp-plan-monthly-desc');  if(el) el.textContent = fmt(monthly)+' per month, invoiced for 12 months.';
            el = document.getElementById('pp-plan-monthly-total'); if(el) el.textContent = 'Total over 12 months: '+fmt(monthly*12);
            updateCTA(single, false);
          }

          function recalcOpt2(){
            var inputs = document.querySelectorAll('.pp-getstarted #pp-table-opt2 [data-pp-line]');
            var subtotal = fixedFees2;
            inputs.forEach(function(inp){
              var qty = parseInt(inp.value,10)||0;
              var price = parseFloat(inp.dataset.ppPrice)||0;
              var line = inp.dataset.ppLine;
              var cost = qty * price;
              var el = document.querySelector('.pp-getstarted [data-pp-cost="'+line+'"]');
              if(el) el.textContent = fmt(cost);
              subtotal += cost;
            });
            var single  = subtotal;
            var monthly = subtotal / 12;
            var el;
            el = document.querySelector('.pp-getstarted [data-pp-total="single2"]');  if(el) el.textContent = fmt(single);
            el = document.querySelector('.pp-getstarted [data-pp-total="monthly2"]'); if(el) el.textContent = fmt(monthly);
            updateCTA(single, true);
          }

          function updateCTA(amount, phased){
            var dnaQty, wasteQty, summary, ctaEl;
            ctaEl = document.getElementById('pp-cta-amount');
            if(ctaEl) ctaEl.textContent = fmt(amount);
            summary = document.getElementById('pp-confirm-summary');
            if(!summary) return;
            if(phased){
              var dnaInput2 = document.querySelector('#pp-table-opt2 [data-pp-line="dna2"]');
              dnaQty = dnaInput2 ? (parseInt(dnaInput2.value,10)||0) : 0;
              summary.innerHTML = "You're confirming: "+dnaQty+" DNA Swabs + setup + annual subscription &middot; Invoiced and shipped usually on same business day: <strong>"+fmt(amount)+"</strong>";
            } else {
              var dnaInput1  = document.querySelector('#pp-table-opt1 [data-pp-line="dna"]');
              var wasteInput = document.querySelector('#pp-table-opt1 [data-pp-line="waste"]');
              dnaQty   = dnaInput1  ? (parseInt(dnaInput1.value,10)||0)  : 0;
              wasteQty = wasteInput ? (parseInt(wasteInput.value,10)||0) : 0;
              summary.innerHTML = "You're confirming: "+dnaQty+" DNA Swabs + "+wasteQty+" Waste Kits + setup + annual subscription &middot; Invoiced and shipped usually on same business day: <strong>"+fmt(amount)+"</strong>";
            }
          }

          function applyRolloutMode(phased){
            var heroTitle      = document.getElementById('pp-hero-title');
            var step3dot       = document.getElementById('pp-step3-dot');
            var sectionStep3   = document.getElementById('pp-section-step3');
            var tableOpt1      = document.getElementById('pp-table-opt1');
            var tableOpt2      = document.getElementById('pp-table-opt2');
            var opt2note       = document.getElementById('pp-opt2-note');
            if(phased){
              if(heroTitle)    heroTitle.textContent = 'Two steps to launch PooPrints at your community';
              if(step3dot)     step3dot.style.display = 'none';
              if(sectionStep3) sectionStep3.style.display = 'none';
              if(tableOpt1)    tableOpt1.style.display = 'none';
              if(tableOpt2)    tableOpt2.style.display = '';
              if(opt2note)     opt2note.style.display = '';
              recalcOpt2();
            } else {
              if(heroTitle)    heroTitle.textContent = 'Three steps to launch PooPrints at your community';
              if(step3dot)     step3dot.style.display = '';
              if(sectionStep3) sectionStep3.style.display = '';
              if(tableOpt1)    tableOpt1.style.display = '';
              if(tableOpt2)    tableOpt2.style.display = 'none';
              if(opt2note)     opt2note.style.display = 'none';
              recalcOpt1();
            }
          }

          var rolloutGroup = document.querySelector('.pp-getstarted [data-pp-group="rollout"]');
          if(rolloutGroup){
            rolloutGroup.querySelectorAll('.pp-card').forEach(function(card){
              card.addEventListener('click', function(e){
                if(e.target.closest('input')) return;
                rolloutGroup.querySelectorAll('.pp-card').forEach(function(c){ c.classList.remove('pp-card--selected'); });
                card.classList.add('pp-card--selected');
                rolloutGroup.querySelectorAll('.pp-card-head').forEach(function(head){
                  var pill = head.querySelector('.pp-pill-selected');
                  var link = head.querySelector('.pp-link-switch');
                  var parentCard = head.closest('.pp-card');
                  if(parentCard.classList.contains('pp-card--selected')){
                    if(link){
                      var newPill = document.createElement('span');
                      newPill.className = 'pp-pill-selected';
                      newPill.textContent = '✓ Selected';
                      link.replaceWith(newPill);
                    }
                  } else {
                    if(pill){
                      var newLink = document.createElement('button');
                      newLink.type = 'button';
                      newLink.className = 'pp-link-switch';
                      newLink.textContent = 'Switch to this option →';
                      pill.replaceWith(newLink);
                    }
                  }
                });
                applyRolloutMode(card.dataset.ppOption === 'phased');
              });
            });
          }

          var paymentGroup = document.querySelector('.pp-getstarted [data-pp-group="payment"]');
          if(paymentGroup){
            paymentGroup.querySelectorAll('.pp-card').forEach(function(card){
              card.addEventListener('click', function(e){
                if(e.target.closest('input')) return;
                paymentGroup.querySelectorAll('.pp-card').forEach(function(c){ c.classList.remove('pp-card--selected'); });
                card.classList.add('pp-card--selected');
              });
            });
          }

          document.querySelectorAll('.pp-getstarted #pp-table-opt1 [data-pp-line]').forEach(function(inp){
            inp.addEventListener('input', recalcOpt1);
          });
          document.querySelectorAll('.pp-getstarted #pp-table-opt2 [data-pp-line]').forEach(function(inp){
            inp.addEventListener('input', recalcOpt2);
          });

        })();
        </script>
        </div><!-- /.pp-getstarted -->
        <?php
        return ob_get_clean();
    }

    /* ----------------------------------------------------------------
       [pooprints_get_started]
       Full "Get Started" page: option selector, kit table, payment plan, CTA.
       Pre-fill priority: $_GET param -> Memberium -> settings default.
    ---------------------------------------------------------------- */
    public static function render_get_started() {
        self::$enqueue_assets      = true;
        self::$enqueue_get_started = true;

        // Enqueue Inter font
        wp_enqueue_style(
            'pooprints-inter',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
            [],
            null
        );

        // --- Resolve quantities ---
        $o1_qty_swab  = self::resolve_option_swab_qty( 'o1_qty_swab', true );
        $o1_qty_waste = (int) self::resolve_field( 'o1_qty_waste' );
        $o2_qty_swab  = self::resolve_option_swab_qty( 'o2_qty_swab', false );

        // --- Resolve prices ---
        $swab_price      = (float) Settings_Page::get( 'price_swab_kit' );
        $waste_price     = (float) Settings_Page::get( 'price_waste_kit' );
        $setup_fee       = (float) Settings_Page::get( 'price_setup_fee' );
        $sub_fee         = (float) Settings_Page::get( 'price_subscription_fee' );
        $discount_pct    = (float) Settings_Page::get( 'price_single_pay_discount' ); // e.g. 90
        $single_discount = $discount_pct / 100; // e.g. 0.90

        // --- Pre-calculate Option 1 totals ---
        $o1_swab_cost  = $o1_qty_swab  * $swab_price;
        $o1_waste_cost = $o1_qty_waste * $waste_price;
        $o1_subtotal   = $o1_swab_cost + $o1_waste_cost + $setup_fee + $sub_fee;
        $o1_single     = $o1_subtotal * $single_discount;
        $o1_monthly    = $o1_subtotal / 12;
        $o1_saving     = $o1_subtotal - $o1_single;
        $o1_12total    = $o1_monthly * 12;

        // --- Pre-calculate Option 2 totals ---
        $o2_swab_cost = $o2_qty_swab * $swab_price;
        $o2_subtotal  = $o2_swab_cost + $setup_fee + $sub_fee;
        $o2_single    = $o2_subtotal;
        $o2_monthly   = $o2_subtotal / 12;

        $savings_pct = round( 100 - $discount_pct );

        ob_start();
        ?>
        <div class="pp-getstarted">

          <!-- ===== Hero ===== -->
          <div class="pp-hero">
            <div class="pp-wrap">
              <h1 id="pp-hero-title">Three steps to launch PooPrints at your community</h1>
              <div class="pp-trust">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1l9 4v6c0 5.5-3.8 10.7-9 12-5.2-1.3-9-6.5-9-12V5l9-4z"/></svg>
                Trusted by 9,000+ properties nationwide
              </div>
              <ul class="pp-steps">
                <li class="pp-step">
                  <div class="pp-step-num">1</div>
                  <div class="pp-step-label">Choose rollout</div>
                </li>
                <li class="pp-step">
                  <div class="pp-step-num">2</div>
                  <div class="pp-step-label">Confirm kits</div>
                </li>
                <li class="pp-step" id="pp-step3-dot">
                  <div class="pp-step-num">3</div>
                  <div class="pp-step-label">Pick payment plan</div>
                </li>
              </ul>
            </div>
          </div>

          <!-- ===== Step 1 ===== -->
          <section class="pp-section">
            <div class="pp-wrap">
              <div class="pp-section-head">
                <h2>Step 1 &#8212; Confirm your rollout option</h2>
                <p>Choose the rollout that fits your community.</p>
              </div>
              <div class="pp-options" data-pp-group="rollout">
                <!-- Option 1 -->
                <div class="pp-card pp-card--selected" data-pp-option="full">
                  <div class="pp-card-head">
                    <span class="pp-card-eyebrow">Option 1</span>
                    <span class="pp-pill-selected">&#10003; Selected</span>
                  </div>
                  <h3>Fastest Results &#8212; Full Rollout</h3>
                  <p class="pp-card-desc"><?php echo esc_html( $o1_qty_swab ); ?> DNA Swab Kits + <?php echo esc_html( $o1_qty_waste ); ?> Waste Sample Kits + training, collateral, resident materials, and ongoing support.</p>
                  <div class="pp-price-box">
                    <div class="pp-price-label">One-Time Payment</div>
                    <div class="pp-price">$<?php echo esc_html( number_format( $o1_single, 2 ) ); ?></div>
                    <div class="pp-price-sub">or 12 monthly payments of $<?php echo esc_html( number_format( $o1_monthly, 2 ) ); ?></div>
                  </div>
                </div>
                <!-- Option 2 -->
                <div class="pp-card" data-pp-option="phased">
                  <div class="pp-card-head">
                    <span class="pp-card-eyebrow">Option 2</span>
                    <button type="button" class="pp-link-switch">Switch to this option &#8594;</button>
                  </div>
                  <h3>Save Money &#8212; Phased Rollout</h3>
                  <p class="pp-card-desc"><?php echo esc_html( $o2_qty_swab ); ?> DNA Swab Kits to start + training, collateral, resident materials, and ongoing support. Order more as leases renew.</p>
                  <div class="pp-price-box">
                    <div class="pp-price-label">Investment</div>
                    <div class="pp-price">$<?php echo esc_html( number_format( $o2_single, 2 ) ); ?></div>
                    <div class="pp-price-sub">Start with fewer kits; add more at lease renewal.</div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- ===== Step 2 ===== -->
          <section class="pp-section pp-section--white">
            <div class="pp-wrap">
              <div class="pp-section-head">
                <h2>Step 2 &#8212; Confirm kit quantities</h2>
                <p>Your kit quantities are pre-loaded. Adjust the Qty column if needed.</p>
              </div>

              <!-- Option 1 table -->
              <div class="pp-table-wrap" id="pp-table-opt1">
                <div class="pp-table-title">Option 1 &#8212; Fastest Results &#8212; Full Rollout</div>
                <div class="pp-table-banner">Register every dog at the start for fastest results.</div>
                <table class="pp-table">
                  <thead>
                    <tr>
                      <th></th><th>Qty</th><th>$ Each</th><th>Cost</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><span class="pp-row-label">DNA Swab Kit</span></td>
                      <td class="pp-text-center">
                        <input type="number" class="pp-qty" value="<?php echo esc_attr( $o1_qty_swab ); ?>" min="0"
                          data-pp-price="<?php echo esc_attr( $swab_price ); ?>" data-pp-line="dna">
                      </td>
                      <td class="pp-text-center">$<?php echo esc_html( number_format( $swab_price, 2 ) ); ?></td>
                      <td class="pp-cost" data-pp-cost="dna">$<?php echo esc_html( number_format( $o1_swab_cost, 2 ) ); ?></td>
                    </tr>
                    <tr>
                      <td><span class="pp-row-label">Waste Sample Collection Kit</span></td>
                      <td class="pp-text-center">
                        <input type="number" class="pp-qty" value="<?php echo esc_attr( $o1_qty_waste ); ?>" min="0"
                          data-pp-price="<?php echo esc_attr( $waste_price ); ?>" data-pp-line="waste">
                      </td>
                      <td class="pp-text-center">$<?php echo esc_html( number_format( $waste_price, 2 ) ); ?></td>
                      <td class="pp-cost" data-pp-cost="waste">$<?php echo esc_html( number_format( $o1_waste_cost, 2 ) ); ?></td>
                    </tr>
                    <tr>
                      <td>
                        <span class="pp-row-label">One-Time Setup Fee <span class="pp-info" title="Includes setup in World Pet Registry, training, and resident materials.">i</span></span>
                        <span class="pp-row-sub">Includes setup in World Pet Registry, training, and resident materials.</span>
                      </td>
                      <td></td><td></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $setup_fee, 2 ) ); ?></td>
                    </tr>
                    <tr>
                      <td>
                        <span class="pp-row-label">Annual Subscription Fee <span class="pp-info" title="Ongoing support and enhancements.">i</span></span>
                        <span class="pp-row-sub">Ongoing support and enhancements.</span>
                      </td>
                      <td></td><td></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $sub_fee, 2 ) ); ?></td>
                    </tr>
                  </tbody>
                </table>
                <div class="pp-table-footer"
                  data-pp-setup="<?php echo esc_attr( $setup_fee ); ?>"
                  data-pp-sub="<?php echo esc_attr( $sub_fee ); ?>"
                  data-pp-discount="<?php echo esc_attr( $single_discount ); ?>">
                  <div class="pp-table-footer-row">
                    <strong>Single Payment &#8212; Total Cost (Save <?php echo esc_html( $savings_pct ); ?>%)</strong>
                    <span class="pp-amount" data-pp-total="single">$<?php echo esc_html( number_format( $o1_single, 2 ) ); ?></span>
                  </div>
                  <div class="pp-table-footer-row">
                    <strong>12 Monthly Payments (Per Month)</strong>
                    <span class="pp-amount" data-pp-total="monthly">$<?php echo esc_html( number_format( $o1_monthly, 2 ) ); ?></span>
                  </div>
                </div>
              </div>

              <!-- Option 2 table -->
              <div class="pp-table-wrap" id="pp-table-opt2" style="display:none">
                <div class="pp-table-title">Option 2 &#8212; Save Money &#8212; Phased Rollout</div>
                <div class="pp-table-banner">Start with <?php echo esc_html( $o2_qty_swab ); ?> dogs; add more as leases renew.</div>
                <table class="pp-table">
                  <thead>
                    <tr>
                      <th></th><th>Qty</th><th>$ Each</th><th>Cost</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><span class="pp-row-label">DNA Swab Kit</span></td>
                      <td class="pp-text-center">
                        <input type="number" class="pp-qty" value="<?php echo esc_attr( $o2_qty_swab ); ?>" min="0"
                          data-pp-price="<?php echo esc_attr( $swab_price ); ?>" data-pp-line="dna2">
                      </td>
                      <td class="pp-text-center">$<?php echo esc_html( number_format( $swab_price, 2 ) ); ?></td>
                      <td class="pp-cost" data-pp-cost="dna2">$<?php echo esc_html( number_format( $o2_swab_cost, 2 ) ); ?></td>
                    </tr>
                    <tr>
                      <td><span class="pp-row-label" style="color:var(--pp-text-muted)">Waste Sample Collection Kit</span></td>
                      <td class="pp-text-center"><span style="font-size:13px;font-weight:600;color:var(--pp-text-muted)">Add later</span></td>
                      <td class="pp-text-center" style="color:var(--pp-text-muted)">$<?php echo esc_html( number_format( $waste_price, 2 ) ); ?></td>
                      <td class="pp-cost" style="color:var(--pp-text-muted)">&#8212;</td>
                    </tr>
                    <tr>
                      <td>
                        <span class="pp-row-label">One-Time Setup Fee <span class="pp-info" title="Includes setup in World Pet Registry, training, and resident materials.">i</span></span>
                        <span class="pp-row-sub">Includes setup in World Pet Registry, training, and resident materials.</span>
                      </td>
                      <td></td><td></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $setup_fee, 2 ) ); ?></td>
                    </tr>
                    <tr>
                      <td>
                        <span class="pp-row-label">Annual Subscription Fee <span class="pp-info" title="Ongoing support and enhancements.">i</span></span>
                        <span class="pp-row-sub">Ongoing support and enhancements.</span>
                      </td>
                      <td></td><td></td>
                      <td class="pp-cost">$<?php echo esc_html( number_format( $sub_fee, 2 ) ); ?></td>
                    </tr>
                  </tbody>
                </table>
                <div class="pp-table-footer"
                  data-pp-setup="<?php echo esc_attr( $setup_fee ); ?>"
                  data-pp-sub="<?php echo esc_attr( $sub_fee ); ?>"
                  data-pp-discount="1">
                  <div class="pp-table-footer-row">
                    <strong>Single Payment &#8212; Total Cost</strong>
                    <span class="pp-amount" data-pp-total="single2">$<?php echo esc_html( number_format( $o2_single, 2 ) ); ?></span>
                  </div>
                  <div class="pp-table-footer-row">
                    <strong>12 Monthly Payments (Per Month)</strong>
                    <span class="pp-amount" data-pp-total="monthly2">$<?php echo esc_html( number_format( $o2_monthly, 2 ) ); ?></span>
                  </div>
                </div>
              </div>

              <p class="pp-opt2-note" id="pp-opt2-note" style="display:none">Note: Waste Sample Kits are not ordered yet &#8212; add them once all dogs are DNA-registered.</p>
            </div>
          </section>

          <!-- ===== Step 3 ===== -->
          <section class="pp-section" id="pp-section-step3">
            <div class="pp-wrap">
              <div class="pp-section-head">
                <h2>Step 3 &#8212; Choose your payment plan</h2>
                <p>Pick the payment plan that fits your budget.</p>
              </div>
              <div class="pp-options" data-pp-group="payment">
                <div class="pp-card pp-plan-card pp-card--selected" data-pp-plan="full">
                  <div class="pp-plan-head">
                    <span class="pp-radio"></span>
                    <h3>Pay in full</h3>
                  </div>
                  <p class="pp-card-desc">Single payment, invoiced.</p>
                  <div class="pp-price-box">
                    <div class="pp-price-label">Total Due</div>
                    <div class="pp-price" id="pp-plan-full-price">$<?php echo esc_html( number_format( $o1_single, 2 ) ); ?></div>
                    <div class="pp-savings" id="pp-plan-full-savings">Save $<?php echo esc_html( number_format( $o1_saving, 2 ) ); ?> vs. monthly plan (<?php echo esc_html( $savings_pct ); ?>% savings)</div>
                  </div>
                </div>
                <div class="pp-card pp-plan-card" data-pp-plan="monthly">
                  <div class="pp-plan-head">
                    <span class="pp-radio"></span>
                    <h3>12 monthly payments</h3>
                  </div>
                  <p class="pp-card-desc" id="pp-plan-monthly-desc">$<?php echo esc_html( number_format( $o1_monthly, 2 ) ); ?> per month, invoiced for 12 months.</p>
                  <div class="pp-price-box">
                    <div class="pp-price-label">Per Month</div>
                    <div class="pp-price" id="pp-plan-monthly-price">$<?php echo esc_html( number_format( $o1_monthly, 2 ) ); ?></div>
                    <div class="pp-price-sub" id="pp-plan-monthly-total">Total over 12 months: $<?php echo esc_html( number_format( $o1_12total, 2 ) ); ?></div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- ===== Confirm / CTA ===== -->
          <div class="pp-confirm">
            <div class="pp-wrap">
              <p class="pp-confirm-summary" id="pp-confirm-summary">You're confirming: <?php echo esc_html( $o1_qty_swab ); ?> DNA Swabs + <?php echo esc_html( $o1_qty_waste ); ?> Waste Kits + setup + annual subscription &middot; Invoiced and shipped usually on same business day: <strong>$<?php echo esc_html( number_format( $o1_single, 2 ) ); ?></strong></p>
              <a href="#" class="pp-cta" id="pp-place-order">
                Place Order &#8212; <span id="pp-cta-amount">$<?php echo esc_html( number_format( $o1_single, 2 ) ); ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
              <div class="pp-help">
                Questions? Call <a href="tel:6122590702">(612) 259-0702</a> or <a href="mailto:hello@pooprints.com">Email us</a>
              </div>
            </div>
          </div>

        </div><!-- /.pp-getstarted -->

        <script>
        (function(){
          var footer1 = document.querySelector('.pp-getstarted #pp-table-opt1 .pp-table-footer');
          var fixedFees1 = footer1
            ? (parseFloat(footer1.dataset.ppSetup) || 0) + (parseFloat(footer1.dataset.ppSub) || 0)
            : <?php echo esc_js( $setup_fee + $sub_fee ); ?>;
          var discount1 = footer1 ? (parseFloat(footer1.dataset.ppDiscount) || 0.9) : <?php echo esc_js( $single_discount ); ?>;

          var footer2 = document.querySelector('.pp-getstarted #pp-table-opt2 .pp-table-footer');
          var fixedFees2 = footer2
            ? (parseFloat(footer2.dataset.ppSetup) || 0) + (parseFloat(footer2.dataset.ppSub) || 0)
            : <?php echo esc_js( $setup_fee + $sub_fee ); ?>;

          function fmt(n){ return '$' + n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

          function recalcOpt1(){
            var inputs = document.querySelectorAll('.pp-getstarted #pp-table-opt1 [data-pp-line]');
            var subtotal = fixedFees1;
            inputs.forEach(function(inp){
              var qty = parseInt(inp.value,10)||0;
              var price = parseFloat(inp.dataset.ppPrice)||0;
              var line = inp.dataset.ppLine;
              var cost = qty * price;
              var el = document.querySelector('.pp-getstarted [data-pp-cost="'+line+'"]');
              if(el) el.textContent = fmt(cost);
              subtotal += cost;
            });
            var single  = subtotal * discount1;
            var monthly = subtotal / 12;
            var saving  = subtotal - single;
            var el;
            el = document.querySelector('.pp-getstarted [data-pp-total="single"]');   if(el) el.textContent = fmt(single);
            el = document.querySelector('.pp-getstarted [data-pp-total="monthly"]');  if(el) el.textContent = fmt(monthly);
            el = document.getElementById('pp-plan-full-price');    if(el) el.textContent = fmt(single);
            el = document.getElementById('pp-plan-full-savings');  if(el) el.textContent = 'Save '+fmt(saving)+' vs. monthly plan';
            el = document.getElementById('pp-plan-monthly-price'); if(el) el.textContent = fmt(monthly);
            el = document.getElementById('pp-plan-monthly-desc');  if(el) el.textContent = fmt(monthly)+' per month, invoiced for 12 months.';
            el = document.getElementById('pp-plan-monthly-total'); if(el) el.textContent = 'Total over 12 months: '+fmt(monthly*12);
            updateCTA(single, false);
          }

          function recalcOpt2(){
            var inputs = document.querySelectorAll('.pp-getstarted #pp-table-opt2 [data-pp-line]');
            var subtotal = fixedFees2;
            inputs.forEach(function(inp){
              var qty = parseInt(inp.value,10)||0;
              var price = parseFloat(inp.dataset.ppPrice)||0;
              var line = inp.dataset.ppLine;
              var cost = qty * price;
              var el = document.querySelector('.pp-getstarted [data-pp-cost="'+line+'"]');
              if(el) el.textContent = fmt(cost);
              subtotal += cost;
            });
            var single  = subtotal;
            var monthly = subtotal / 12;
            var el;
            el = document.querySelector('.pp-getstarted [data-pp-total="single2"]');  if(el) el.textContent = fmt(single);
            el = document.querySelector('.pp-getstarted [data-pp-total="monthly2"]'); if(el) el.textContent = fmt(monthly);
            updateCTA(single, true);
          }

          function updateCTA(amount, phased){
            var dnaQty, wasteQty, summary, ctaEl;
            ctaEl = document.getElementById('pp-cta-amount');
            if(ctaEl) ctaEl.textContent = fmt(amount);
            summary = document.getElementById('pp-confirm-summary');
            if(!summary) return;
            if(phased){
              var dnaInput2 = document.querySelector('#pp-table-opt2 [data-pp-line="dna2"]');
              dnaQty = dnaInput2 ? (parseInt(dnaInput2.value,10)||0) : 0;
              summary.innerHTML = "You're confirming: "+dnaQty+" DNA Swabs + setup + annual subscription &middot; Invoiced and shipped usually on same business day: <strong>"+fmt(amount)+"</strong>";
            } else {
              var dnaInput1  = document.querySelector('#pp-table-opt1 [data-pp-line="dna"]');
              var wasteInput = document.querySelector('#pp-table-opt1 [data-pp-line="waste"]');
              dnaQty   = dnaInput1  ? (parseInt(dnaInput1.value,10)||0)  : 0;
              wasteQty = wasteInput ? (parseInt(wasteInput.value,10)||0) : 0;
              summary.innerHTML = "You're confirming: "+dnaQty+" DNA Swabs + "+wasteQty+" Waste Kits + setup + annual subscription &middot; Invoiced and shipped usually on same business day: <strong>"+fmt(amount)+"</strong>";
            }
          }

          function applyRolloutMode(phased){
            var heroTitle      = document.getElementById('pp-hero-title');
            var step3dot       = document.getElementById('pp-step3-dot');
            var sectionStep3   = document.getElementById('pp-section-step3');
            var tableOpt1      = document.getElementById('pp-table-opt1');
            var tableOpt2      = document.getElementById('pp-table-opt2');
            var opt2note       = document.getElementById('pp-opt2-note');
            if(phased){
              if(heroTitle)    heroTitle.textContent = 'Two steps to launch PooPrints at your community';
              if(step3dot)     step3dot.style.display = 'none';
              if(sectionStep3) sectionStep3.style.display = 'none';
              if(tableOpt1)    tableOpt1.style.display = 'none';
              if(tableOpt2)    tableOpt2.style.display = '';
              if(opt2note)     opt2note.style.display = '';
              recalcOpt2();
            } else {
              if(heroTitle)    heroTitle.textContent = 'Three steps to launch PooPrints at your community';
              if(step3dot)     step3dot.style.display = '';
              if(sectionStep3) sectionStep3.style.display = '';
              if(tableOpt1)    tableOpt1.style.display = '';
              if(tableOpt2)    tableOpt2.style.display = 'none';
              if(opt2note)     opt2note.style.display = 'none';
              recalcOpt1();
            }
          }

          // Rollout card toggling
          var rolloutGroup = document.querySelector('.pp-getstarted [data-pp-group="rollout"]');
          if(rolloutGroup){
            rolloutGroup.querySelectorAll('.pp-card').forEach(function(card){
              card.addEventListener('click', function(e){
                if(e.target.closest('input')) return;
                rolloutGroup.querySelectorAll('.pp-card').forEach(function(c){ c.classList.remove('pp-card--selected'); });
                card.classList.add('pp-card--selected');
                rolloutGroup.querySelectorAll('.pp-card-head').forEach(function(head){
                  var pill = head.querySelector('.pp-pill-selected');
                  var link = head.querySelector('.pp-link-switch');
                  var parentCard = head.closest('.pp-card');
                  if(parentCard.classList.contains('pp-card--selected')){
                    if(link){
                      var newPill = document.createElement('span');
                      newPill.className = 'pp-pill-selected';
                      newPill.textContent = '✓ Selected';
                      link.replaceWith(newPill);
                    }
                  } else {
                    if(pill){
                      var newLink = document.createElement('button');
                      newLink.type = 'button';
                      newLink.className = 'pp-link-switch';
                      newLink.textContent = 'Switch to this option →';
                      pill.replaceWith(newLink);
                    }
                  }
                });
                applyRolloutMode(card.dataset.ppOption === 'phased');
              });
            });
          }

          // Payment plan card toggling
          var paymentGroup = document.querySelector('.pp-getstarted [data-pp-group="payment"]');
          if(paymentGroup){
            paymentGroup.querySelectorAll('.pp-card').forEach(function(card){
              card.addEventListener('click', function(e){
                if(e.target.closest('input')) return;
                paymentGroup.querySelectorAll('.pp-card').forEach(function(c){ c.classList.remove('pp-card--selected'); });
                card.classList.add('pp-card--selected');
              });
            });
          }

          // Live recalc on qty input
          document.querySelectorAll('.pp-getstarted #pp-table-opt1 [data-pp-line]').forEach(function(inp){
            inp.addEventListener('input', recalcOpt1);
          });
          document.querySelectorAll('.pp-getstarted #pp-table-opt2 [data-pp-line]').forEach(function(inp){
            inp.addEventListener('input', recalcOpt2);
          });

        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /* ----------------------------------------------------------------
       [pooprints_fine_tables show="both|breakeven|profit"]
       Static fine reference tables. No JS required.
    ---------------------------------------------------------------- */
    public static function render_fine_tables( $atts = [] ) {
        self::$enqueue_assets = true;

        $atts = shortcode_atts( [ 'show' => 'both' ], $atts, 'pooprints_fine_tables' );
        $show = sanitize_key( $atts['show'] );

        $total_cost       = (float) Settings_Page::get( 'price_waste_sample_test' );
        $avg_match_rate   = (float) Settings_Page::get( 'stat_avg_match_rate' );
        $recommended_fine = (int)   Settings_Page::get( 'stat_recommended_fine' );
        $breakeven_fine   = $avg_match_rate > 0 ? (int) round( $total_cost / $avg_match_rate ) : 0;

        $match_ratios = [ 0.25, 0.30, 0.35, 0.40, 0.45, 0.50, 0.55, 0.60, 0.65, 0.70, 0.75, 0.80, 0.85, 0.90 ];
        $profit_rows  = [ Settings_Page::get( 'price_waste_sample_test' ), 100, 150, 200, 275, 300, 350, 375, 400, 450, 500 ];

        ob_start();
        $side_by_side = ( 'both' === $show );
        ?>
        <div class="pp-ft-container <?php echo $side_by_side ? 'pp-ft-container--side-by-side' : 'pp-ft-container--single'; ?>">

        <?php if ( 'profit' !== $show ) : ?>
          <div class="pp-ft-wrap pp-ft-breakeven">
            <div class="pp-ft-title">Breakeven Fine Amount Table Based on Match Ratio</div>
            <table class="pp-ft-table">
              <thead>
                <tr>
                  <th>% Waste Testing Match Ratio</th>
                  <th>Breakeven Fine Amount</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ( $match_ratios as $ratio ) :
                  $row_fine         = $total_cost / $ratio;
                  $row_fine_rounded = (int) round( $row_fine );
                  $row_class        = '';
                  if ( abs( $row_fine_rounded - $recommended_fine ) <= 5 ) {
                      $row_class = ' pp-ft-row--recommended';
                  } elseif ( abs( $row_fine_rounded - $breakeven_fine ) <= 5 ) {
                      $row_class = ' pp-ft-row--breakeven';
                  }
              ?>
                <tr class="pp-ft-row<?php echo esc_attr( $row_class ); ?>">
                  <td><?php echo esc_html( (int) round( $ratio * 100 ) ); ?>%</td>
                  <td>$<?php echo esc_html( number_format( $row_fine, 0 ) ); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <div class="pp-ft-legend">
              <span class="pp-ft-legend__item pp-ft-legend__item--breakeven">Breakeven ($<?php echo esc_html( $breakeven_fine ); ?>)</span>
              <span class="pp-ft-legend__item pp-ft-legend__item--recommended">Recommended ($<?php echo esc_html( $recommended_fine ); ?>)</span>
            </div>
          </div>
        <?php endif; ?>

        <?php if ( 'breakeven' !== $show ) : ?>
          <div class="pp-ft-wrap pp-ft-profit">
            <div class="pp-ft-title">Waste Testing Profit (Loss) Table with No Row Numbers</div>
            <table class="pp-ft-table">
              <thead>
                <tr>
                  <th>Dog Owner Charge for Leaving Dog Waste Behind</th>
                  <th>Profit (Loss) Per Waste Sample Tested For An Average Property</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ( $profit_rows as $fine ) :
                  $profit   = ( $fine * $avg_match_rate ) - $total_cost;
                  $is_loss  = $profit < 0;
                  $row_class = $is_loss ? ' pp-ft-row--loss' : '';
                  $fine_int  = (int) round( $fine );
                  if ( abs( $fine_int - $recommended_fine ) <= 5 ) {
                      $row_class .= ' pp-ft-row--recommended';
                  } elseif ( abs( $fine_int - $breakeven_fine ) <= 5 ) {
                      $row_class .= ' pp-ft-row--breakeven';
                  }
                  if ( $is_loss ) {
                      $profit_display = '($' . number_format( abs( $profit ), 0 ) . ')';
                  } else {
                      $profit_display = '$' . number_format( $profit, 0 );
                  }
              ?>
                <tr class="pp-ft-row<?php echo esc_attr( $row_class ); ?>">
                  <td>$<?php echo esc_html( number_format( $fine, 2 ) ); ?></td>
                  <td class="<?php echo $is_loss ? 'pp-ft-cell--loss' : 'pp-ft-cell--gain'; ?>">
                    <?php echo esc_html( $profit_display ); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <div class="pp-ft-legend">
              <span class="pp-ft-legend__item pp-ft-legend__item--breakeven">Breakeven ($<?php echo esc_html( $breakeven_fine ); ?>)</span>
              <span class="pp-ft-legend__item pp-ft-legend__item--recommended">Recommended ($<?php echo esc_html( $recommended_fine ); ?>)</span>
            </div>
          </div>
        <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }
}
