<?php
namespace PooPrints;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Per-user calculator values.
 *
 * main.js posts the visitor's calculator values 2 seconds after their last change
 * (Ajax::save_calculator); they are stored in user meta and pre-fill the calculator on
 * the next visit (Shortcodes::resolve_field). Admins read them on the
 * "Saved Calculator Data" tab.
 *
 * Stored shape:
 *   [ 'values' => [ field_map() key => int|float, … every field ],
 *     'updated' => 'Y-m-d H:i:s' (site time),
 *     'page'    => '/path/the/change/was/made/on/' ]
 */
class Saved_Data {

    const META_KEY = 'user_calculator_data';

    /** Largest value accepted for any field; anything above is clamped. */
    const MAX_VALUE = 1000000;

    const PER_PAGE = 20;

    /** @return array The stored array, or [] when the user has none. */
    public static function get( $user_id ) {
        $data = get_user_meta( (int) $user_id, self::META_KEY, true );
        return is_array( $data ) ? $data : [];
    }

    /** get_values() per user for this request; save() keeps it current. */
    private static $values_cache = [];

    /** @return array Only the calculator values, keyed like field_map(). */
    public static function get_values( $user_id ) {
        $user_id = (int) $user_id;
        if ( ! $user_id ) {
            return [];
        }
        if ( ! isset( self::$values_cache[ $user_id ] ) ) {
            $data                             = self::get( $user_id );
            self::$values_cache[ $user_id ] = isset( $data['values'] ) && is_array( $data['values'] ) ? $data['values'] : [];
        }
        return self::$values_cache[ $user_id ];
    }

    /**
     * Save a user's values. Every field is stored: a field missing from $posted keeps
     * its previously saved value, or its default when there is none. Unknown keys are dropped.
     *
     * @param int    $user_id Always the logged-in user; never taken from the request.
     * @param array  $posted  Raw values keyed like field_map().
     * @param string $source  Where the change was made (a URL or path).
     * @return array|false The stored array, or false on failure.
     */
    public static function save( $user_id, array $posted, $source = '' ) {
        $user_id = (int) $user_id;
        if ( ! $user_id ) {
            return false;
        }

        $existing = self::get_values( $user_id );
        $values   = [];
        foreach ( Shortcodes::field_map() as $key => $field ) {
            list( , $type, $default ) = $field;
            if ( isset( $posted[ $key ] ) && is_scalar( $posted[ $key ] ) && is_numeric( trim( (string) $posted[ $key ] ) ) ) {
                $raw = $posted[ $key ];
            } elseif ( isset( $existing[ $key ] ) ) {
                $raw = $existing[ $key ];
            } else {
                $raw = $default;
            }
            $values[ $key ] = self::clean( $raw, $type );
        }
        // Option 1 registers every dog, and the page keeps its swab count equal to the dog
        // count. A page without the Option 1 table cannot send it, so follow the dogs here.
        if ( ! isset( $posted['o1_qty_swab'] ) && isset( $values['dogs'] ) ) {
            $values['o1_qty_swab'] = (int) $values['dogs'];
        }

        $data = [
            'values'  => $values,
            'updated' => current_time( 'mysql' ),
            'page'    => self::clean_path( $source ),
        ];
        update_user_meta( $user_id, self::META_KEY, $data );
        self::$values_cache[ $user_id ] = $values;
        return $data;
    }

    private static function clean( $raw, $type ) {
        $n = min( (float) $raw, self::MAX_VALUE );
        $n = max( $n, 0 );
        return 'float' === $type ? round( $n, 2 ) : (int) round( $n );
    }

    private static function clean_path( $source ) {
        $path = wp_parse_url( (string) $source, PHP_URL_PATH );
        return is_string( $path ) ? substr( sanitize_text_field( $path ), 0, 200 ) : '';
    }

    /* ----------------------------------------------------------------
       Admin: Settings → PooPrints Calculator → Saved Calculator Data
    ---------------------------------------------------------------- */

    public static function tab_url( $args = [] ) {
        return add_query_arg(
            array_merge( [ 'page' => 'pooprints-calculator', 'tab' => 'saved-data' ], $args ),
            admin_url( 'options-general.php' )
        );
    }

    /** The user being viewed on the tab, or 0. */
    public static function selected_user_id() {
        return isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
    }

    /**
     * Users who have saved data, newest change first, optionally filtered by a search term.
     *
     * @return array[] Each: [ 'user' => WP_User, 'updated' => string ].
     */
    private static function users_with_data( $search ) {
        $args = [
            'meta_query' => [ [ 'key' => self::META_KEY, 'compare' => 'EXISTS' ] ],
            'number'     => -1,
        ];
        if ( '' !== $search ) {
            $args['search']         = '*' . $search . '*';
            $args['search_columns'] = [ 'user_login', 'user_email', 'display_name', 'user_nicename' ];
        }
        $users = get_users( $args );

        $rows = [];
        foreach ( $users as $user ) {
            $data   = self::get( $user->ID );
            $rows[] = [ 'user' => $user, 'updated' => isset( $data['updated'] ) ? (string) $data['updated'] : '' ];
        }
        // The timestamp lives inside the serialized array, so sort here rather than in SQL.
        usort( $rows, function ( $a, $b ) {
            return strcmp( $b['updated'], $a['updated'] );
        } );
        return $rows;
    }

    private static function ago( $mysql_time ) {
        $ts = $mysql_time ? strtotime( $mysql_time ) : false;
        if ( ! $ts ) {
            return '';
        }
        /* translators: %s: human time difference, e.g. "2 hours". */
        return sprintf( __( '%s ago', 'pooprints-calculator' ), human_time_diff( $ts, strtotime( current_time( 'mysql' ) ) ) );
    }

    private static function format_value( $value, $type ) {
        return 'float' === $type ? Settings_Page::format_smart_decimal( $value ) : (string) (int) $value;
    }

    public static function render_tab() {
        $search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $selected = self::selected_user_id();

        $rows   = self::users_with_data( $search );
        $total  = count( $rows );
        $pages  = max( 1, (int) ceil( $total / self::PER_PAGE ) );
        $paged  = min( $paged, $pages );
        $shown  = array_slice( $rows, ( $paged - 1 ) * self::PER_PAGE, self::PER_PAGE );
        $keep   = array_filter( [ 's' => $search, 'paged' => $paged > 1 ? $paged : null ] );
        ?>
        <style>
          .pp-sd { display: flex; gap: 24px; align-items: flex-start; }
          .pp-sd__list { flex: 0 0 300px; background: #fff; border: 1px solid #dcdcde; }
          .pp-sd__list form { display: flex; gap: 6px; padding: 12px; border-bottom: 1px solid #dcdcde; }
          .pp-sd__list form input[type=search] { flex: 1; min-width: 0; }
          .pp-sd__list ul { margin: 0; }
          .pp-sd__list li { margin: 0; border-bottom: 1px solid #f0f0f1; }
          .pp-sd__list li a { display: block; padding: 10px 12px; text-decoration: none; color: #1d2327; }
          .pp-sd__list li a:hover { background: #f6f7f7; }
          .pp-sd__list li.is-active a { background: #2271b1; color: #fff; }
          .pp-sd__list li small { display: block; color: #646970; margin-top: 2px; }
          .pp-sd__list li.is-active small { color: #e0ecf7; }
          .pp-sd__count, .pp-sd__empty { padding: 10px 12px; margin: 0; color: #646970; }
          .pp-sd__pager { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; }
          .pp-sd__view { flex: 1; min-width: 0; }
          .pp-sd__head { background: #fff; border: 1px solid #dcdcde; padding: 14px 16px; margin-bottom: 16px; }
          .pp-sd__head h2 { margin: 0 0 4px; }
          .pp-sd__head p { margin: 0; color: #646970; }
          .pp-sd__values { margin-bottom: 24px; }
          .pp-sd__values td.is-changed { font-weight: 600; color: #b32d2e; }
          .pp-sd__readonly { background: #fff; border: 1px solid #dcdcde; padding: 16px; }
          .pp-sd__readonly .roi-masthead__reset { display: none; }
          .pp-sd__readonly input { cursor: default; }
          .pp-sd__readonly > * + * { margin-top: 24px; }
          @media (max-width: 960px) { .pp-sd { flex-direction: column; } .pp-sd__list { flex-basis: auto; width: 100%; } }
        </style>

        <div class="pp-sd">
          <div class="pp-sd__list">
            <form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
              <input type="hidden" name="page" value="pooprints-calculator">
              <input type="hidden" name="tab" value="saved-data">
              <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name or email', 'pooprints-calculator' ); ?>">
              <button type="submit" class="button"><?php esc_html_e( 'Search', 'pooprints-calculator' ); ?></button>
            </form>
            <?php if ( ! $total ) : ?>
              <p class="pp-sd__empty"><?php echo $search ? esc_html__( 'No users match that search.', 'pooprints-calculator' ) : esc_html__( 'No user has saved calculator data yet.', 'pooprints-calculator' ); ?></p>
            <?php else : ?>
              <p class="pp-sd__count">
                <?php
                /* translators: %d: number of users. */
                echo esc_html( sprintf( _n( '%d user', '%d users', $total, 'pooprints-calculator' ), $total ) );
                ?>
              </p>
              <ul>
                <?php foreach ( $shown as $row ) :
                    $user = $row['user'];
                    $url  = self::tab_url( array_merge( $keep, [ 'user' => $user->ID ] ) );
                    ?>
                  <li class="<?php echo $selected === (int) $user->ID ? 'is-active' : ''; ?>">
                    <a href="<?php echo esc_url( $url ); ?>">
                      <strong><?php echo esc_html( $user->display_name ); ?></strong>
                      <small><?php echo esc_html( $user->user_email ); ?></small>
                      <small><?php echo esc_html( self::ago( $row['updated'] ) ); ?></small>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php if ( $pages > 1 ) : ?>
                <div class="pp-sd__pager">
                  <?php if ( $paged > 1 ) : ?>
                    <a class="button" href="<?php echo esc_url( self::tab_url( array_filter( [ 's' => $search, 'paged' => $paged - 1, 'user' => $selected ?: null ] ) ) ); ?>">&laquo; <?php esc_html_e( 'Previous', 'pooprints-calculator' ); ?></a>
                  <?php else : ?><span></span><?php endif; ?>
                  <span><?php echo esc_html( $paged . ' / ' . $pages ); ?></span>
                  <?php if ( $paged < $pages ) : ?>
                    <a class="button" href="<?php echo esc_url( self::tab_url( array_filter( [ 's' => $search, 'paged' => $paged + 1, 'user' => $selected ?: null ] ) ) ); ?>"><?php esc_html_e( 'Next', 'pooprints-calculator' ); ?> &raquo;</a>
                  <?php else : ?><span></span><?php endif; ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="pp-sd__view">
            <?php self::render_user_view( $selected ); ?>
          </div>
        </div>
        <?php
    }

    private static function render_user_view( $user_id ) {
        if ( ! $user_id ) {
            echo '<p>' . esc_html__( 'Select a user on the left to see their calculator.', 'pooprints-calculator' ) . '</p>';
            return;
        }
        $user = get_userdata( $user_id );
        $data = self::get( $user_id );
        if ( ! $user || empty( $data['values'] ) || ! is_array( $data['values'] ) ) {
            echo '<p>' . esc_html__( 'This user has no saved calculator data.', 'pooprints-calculator' ) . '</p>';
            return;
        }

        $values = $data['values'];
        $labels = Settings_Page::field_labels();
        ?>
        <div class="pp-sd__head">
          <h2><?php echo esc_html( $user->display_name ); ?></h2>
          <p>
            <?php echo esc_html( $user->user_email ); ?>
            &middot;
            <?php
            /* translators: 1: date and time, 2: "x ago". */
            echo esc_html( sprintf( __( 'Last changed %1$s (%2$s)', 'pooprints-calculator' ),
                mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $data['updated'] ?? '' ),
                self::ago( $data['updated'] ?? '' ) ) );
            ?>
            <?php if ( ! empty( $data['page'] ) ) : ?>
              &middot; <?php esc_html_e( 'on', 'pooprints-calculator' ); ?> <code><?php echo esc_html( $data['page'] ); ?></code>
            <?php endif; ?>
          </p>
        </div>

        <table class="widefat striped pp-sd__values">
          <thead>
            <tr>
              <th><?php esc_html_e( 'Field', 'pooprints-calculator' ); ?></th>
              <th><?php esc_html_e( 'Saved value', 'pooprints-calculator' ); ?></th>
              <th><?php esc_html_e( 'Default', 'pooprints-calculator' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( Shortcodes::field_map() as $key => $field ) :
                list( , $type, $default ) = $field;
                $saved   = isset( $values[ $key ] ) ? $values[ $key ] : $default;
                $changed = (float) $saved !== (float) $default;
                ?>
              <tr>
                <td><?php echo esc_html( isset( $labels[ $key ] ) ? $labels[ $key ] : $key ); ?> <code><?php echo esc_html( $key ); ?></code></td>
                <td class="<?php echo $changed ? 'is-changed' : ''; ?>"><?php echo esc_html( self::format_value( $saved, $type ) ); ?></td>
                <td><?php echo esc_html( self::format_value( $default, $type ) ); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="pp-sd__readonly" data-pp-readonly>
          <?php
          // Read-only: main.js still calculates the figures, but saving is switched off on
          // this screen (ppPrices.save.enabled = false) and the inputs are locked below.
          echo Shortcodes::render_with_values( $values, function () { // phpcs:ignore WordPress.Security.EscapeOutput
              return Shortcodes::render_option1() . Shortcodes::render_option2() . Shortcodes::render_roi();
          } );
          ?>
        </div>
        <script>
        document.querySelectorAll('[data-pp-readonly] input, [data-pp-readonly] select, [data-pp-readonly] textarea').forEach(function (el) {
          el.readOnly = true;
          el.setAttribute('aria-readonly', 'true');
          el.tabIndex = -1;
        });
        </script>
        <?php
    }
}
