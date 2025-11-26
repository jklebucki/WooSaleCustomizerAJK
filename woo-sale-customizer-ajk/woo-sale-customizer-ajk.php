<?php
/**
 * Plugin Name: WooSaleCustomizerAJK
 * Description: Customizacja etykiety "Sale" w WooCommerce (AJK).
 * Author: Jarosław Kłębucki
 * Version: 1.0.0
 * Text Domain: woo-sale-customizer-ajk
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Bezpośredni dostęp blokowany
}

class WooSaleCustomizerAJK {

    const OPTION_KEY = 'woosale_customizer_ajk_label';

    public function __construct() {
        // Ustaw domyślną wartość przy aktywacji
        register_activation_hook( __FILE__, array( $this, 'on_activate' ) );

        // Dodanie strony ustawień w WooCommerce
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 99 );

        // Filtr WooCommerce – podmiana etykiety "Sale"
        add_filter( 'woocommerce_sale_flash', array( $this, 'filter_sale_flash' ), 10, 3 );
    }

    /**
     * Ustaw domyślną etykietę przy aktywacji
     */
    public function on_activate() {
        if ( get_option( self::OPTION_KEY ) === false ) {
            add_option( self::OPTION_KEY, 'PROMOCJA' );
        }
    }

    /**
     * Dodanie pozycji menu pod WooCommerce
     */
    public function add_admin_menu() {
        // Sprawdź, czy WooCommerce jest aktywny
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        add_submenu_page(
            'woocommerce',
            __( 'Sale Customizer AJK', 'woo-sale-customizer-ajk' ),
            __( 'Sale Customizer AJK', 'woo-sale-customizer-ajk' ),
            'manage_woocommerce',
            'woosale-customizer-ajk',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Widok strony ustawień
     */
    public function render_settings_page() {

        // Obsługa zapisu formularza
        if ( isset( $_POST['woosale_customizer_ajk_submit'] ) ) {
            check_admin_referer( 'woosale_customizer_ajk_save', 'woosale_customizer_ajk_nonce' );

            $label = isset( $_POST['woosale_customizer_ajk_label'] )
                ? sanitize_text_field( wp_unslash( $_POST['woosale_customizer_ajk_label'] ) )
                : '';

            update_option( self::OPTION_KEY, $label );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'woo-sale-customizer-ajk' ) . '</p></div>';
        }

        $current_label = get_option( self::OPTION_KEY, 'PROMOCJA' );
        ?>

        <div class="wrap">
            <h1><?php esc_html_e( 'WooSaleCustomizerAJK – Ustawienia', 'woo-sale-customizer-ajk' ); ?></h1>

            <form method="post">
                <?php wp_nonce_field( 'woosale_customizer_ajk_save', 'woosale_customizer_ajk_nonce' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="woosale_customizer_ajk_label">
                                <?php esc_html_e( 'Tekst etykiety „Sale”', 'woo-sale-customizer-ajk' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                name="woosale_customizer_ajk_label"
                                id="woosale_customizer_ajk_label"
                                type="text"
                                class="regular-text"
                                value="<?php echo esc_attr( $current_label ); ?>"
                            />
                            <p class="description">
                                <?php esc_html_e( 'Ten tekst pojawi się zamiast domyślnego „Sale” na produktach w promocji.', 'woo-sale-customizer-ajk' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Zapisz', 'woo-sale-customizer-ajk' ), 'primary', 'woosale_customizer_ajk_submit' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Podmiana etykiety "Sale" w WooCommerce
     *
     * @param string        $html
     * @param WP_Post       $post
     * @param WC_Product    $product
     *
     * @return string
     */
    public function filter_sale_flash( $html, $post, $product ) {
        $label = get_option( self::OPTION_KEY, 'PROMOCJA' );
        $label = $label !== '' ? $label : 'PROMOCJA';

        return '<span class="onsale">' . esc_html( $label ) . '</span>';
    }
}

new WooSaleCustomizerAJK();
