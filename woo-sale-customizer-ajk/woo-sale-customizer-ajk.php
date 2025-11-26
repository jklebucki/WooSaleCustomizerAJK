<?php
/**
 * Plugin Name: WooSaleCustomizerAJK
 * Plugin URI: https://github.com/jklebucki/WooSaleCustomizerAJK
 * Description: Customizacja etykiety "Sale" w WooCommerce (AJK).
 * Version: 1.0.0
 * Author: Jarosław Kłębucki
 * Author URI: https://github.com/jklebucki
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-sale-customizer-ajk
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * WC requires at least: 3.0.0
 * WC tested up to: 9.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Bezpośredni dostęp blokowany
}

/**
 * Deklaracja kompatybilności z WooCommerce HFCS
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * Sprawdź kompatybilność z WooCommerce
 */
function woosale_customizer_ajk_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'woosale_customizer_ajk_woocommerce_missing_notice' );
        return false;
    }

    // WooCommerce sprawdza kompatybilność wersji na podstawie nagłówków wtyczki
    // Tutaj sprawdzamy tylko czy klasa istnieje
    return true;
}

/**
 * Powiadomienie o braku WooCommerce
 */
function woosale_customizer_ajk_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p>
            <strong><?php esc_html_e( 'WooSale Customizer AJK', 'woo-sale-customizer-ajk' ); ?></strong> 
            <?php esc_html_e( 'requires WooCommerce to be installed and active.', 'woo-sale-customizer-ajk' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Powiadomienie o niekompatybilnej wersji WooCommerce
 */
function woosale_customizer_ajk_woocommerce_version_notice() {
    ?>
    <div class="error">
        <p>
            <strong><?php esc_html_e( 'WooSale Customizer AJK', 'woo-sale-customizer-ajk' ); ?></strong> 
            <?php esc_html_e( 'requires WooCommerce version 3.0 or higher.', 'woo-sale-customizer-ajk' ); ?>
        </p>
    </div>
    <?php
}

class WooSaleCustomizerAJK {

    const OPTION_KEY = 'woosale_customizer_ajk_label';
    const OPTION_STYLE_KEY = 'woosale_customizer_ajk_style';

    public function __construct() {
        // Sprawdź kompatybilność z WooCommerce przed inicjalizacją
        if ( ! woosale_customizer_ajk_check_woocommerce() ) {
            return;
        }

        // Ładowanie tłumaczeń
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Ustaw domyślną wartość przy aktywacji
        register_activation_hook( __FILE__, array( $this, 'on_activate' ) );

        // Dodanie strony ustawień w WooCommerce
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 99 );

        // Usuń wszystkie poprzednie hooki dla sale_flash i dodaj nasz
        add_action( 'wp_loaded', array( $this, 'remove_default_sale_flash' ), 999 );

        // Dodanie CSS stylów do head
        add_action( 'wp_head', array( $this, 'output_styles' ) );
    }

    /**
     * Ładowanie tłumaczeń
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'woo-sale-customizer-ajk',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * Usuń domyślne hooki sale_flash aby zapobiec duplikacji
     */
    public function remove_default_sale_flash() {
        // Usuń wszystkie hooki przypisane do woocommerce_sale_flash
        remove_all_filters( 'woocommerce_sale_flash', 10 );
        
        // Dodaj ponownie tylko nasz hook
        add_filter( 'woocommerce_sale_flash', array( $this, 'filter_sale_flash' ), 10, 3 );
    }

    /**
     * Ustaw domyślne wartości przy aktywacji
     */
    public function on_activate() {
        if ( get_option( self::OPTION_KEY ) === false ) {
            add_option( self::OPTION_KEY, __( 'PROMOCJA', 'woo-sale-customizer-ajk' ) );
        }
        if ( get_option( self::OPTION_STYLE_KEY ) === false ) {
            add_option( self::OPTION_STYLE_KEY, '1' );
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

            $style = isset( $_POST['woosale_customizer_ajk_style'] )
                ? absint( $_POST['woosale_customizer_ajk_style'] )
                : 1;

            if ( $style < 1 || $style > 10 ) {
                $style = 1;
            }

            update_option( self::OPTION_KEY, $label );
            update_option( self::OPTION_STYLE_KEY, $style );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'woo-sale-customizer-ajk' ) . '</p></div>';
        }

        $current_label = get_option( self::OPTION_KEY, __( 'PROMOCJA', 'woo-sale-customizer-ajk' ) );
        $current_style = get_option( self::OPTION_STYLE_KEY, '1' );
        $current_style = absint( $current_style );
        if ( $current_style < 1 || $current_style > 10 ) {
            $current_style = 1;
        }
        $available_styles = $this->get_available_styles();
        ?>

        <div class="wrap">
            <h1><?php esc_html_e( 'WooSaleCustomizerAJK – Ustawienia', 'woo-sale-customizer-ajk' ); ?></h1>

            <?php echo $this->generate_styles_css(); ?>

            <form method="post" id="woosale-customizer-form">
                <?php wp_nonce_field( 'woosale_customizer_ajk_save', 'woosale_customizer_ajk_nonce' ); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="woosale_customizer_ajk_label">
                                <?php esc_html_e( 'Tekst etykiety "Sale"', 'woo-sale-customizer-ajk' ); ?>
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
                                <?php esc_html_e( 'Ten tekst pojawi się zamiast domyślnego "Sale" na produktach w promocji.', 'woo-sale-customizer-ajk' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="woosale_customizer_ajk_style">
                                <?php esc_html_e( 'Styl etykiety', 'woo-sale-customizer-ajk' ); ?>
                            </label>
                        </th>
                        <td>
                            <select
                                name="woosale_customizer_ajk_style"
                                id="woosale_customizer_ajk_style"
                                class="regular-text"
                            >
                                <?php foreach ( $available_styles as $style_id => $style_name ) : ?>
                                    <option value="<?php echo esc_attr( $style_id ); ?>" <?php selected( $current_style, $style_id ); ?>>
                                        <?php echo esc_html( $style_name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Wybierz styl wizualny etykiety sale.', 'woo-sale-customizer-ajk' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Podgląd', 'woo-sale-customizer-ajk' ); ?>
                        </th>
                        <td>
                            <div style="position: relative; width: 200px; height: 200px; background: #f5f5f5; border: 1px solid #ddd; padding: 20px; margin: 20px 0;">
                                <span class="onsale woosale-preview" id="woosale-preview" style="position: absolute; top: 10px; left: 10px;">
                                    <?php echo esc_html( $current_label ); ?>
                                </span>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Podgląd etykiety z wybranym stylem i tekstem.', 'woo-sale-customizer-ajk' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Zapisz', 'woo-sale-customizer-ajk' ), 'primary', 'woosale_customizer_ajk_submit' ); ?>
            </form>
        </div>

        <script>
        (function() {
            var styleSelect = document.getElementById('woosale_customizer_ajk_style');
            var labelInput = document.getElementById('woosale_customizer_ajk_label');
            var preview = document.getElementById('woosale-preview');

            function updatePreview() {
                var selectedStyle = styleSelect.value;
                var labelText = labelInput.value || '<?php echo esc_js( __( 'PROMOCJA', 'woo-sale-customizer-ajk' ) ); ?>';
                
                // Usuń wszystkie klasy stylu
                preview.className = 'onsale woosale-preview';
                // Dodaj wybrany styl
                preview.classList.add('woosale-style-' + selectedStyle);
                // Zaktualizuj tekst
                preview.textContent = labelText;
            }

            if (styleSelect && labelInput && preview) {
                styleSelect.addEventListener('change', updatePreview);
                labelInput.addEventListener('input', updatePreview);
                updatePreview();
            }
        })();
        </script>
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
        $label = get_option( self::OPTION_KEY, __( 'PROMOCJA', 'woo-sale-customizer-ajk' ) );
        $label = $label !== '' ? $label : __( 'PROMOCJA', 'woo-sale-customizer-ajk' );
        $style = get_option( self::OPTION_STYLE_KEY, '1' );
        $style = absint( $style );
        if ( $style < 1 || $style > 10 ) {
            $style = 1;
        }

        return '<span class="onsale woosale-style-' . esc_attr( $style ) . '">' . esc_html( $label ) . '</span>';
    }

    /**
     * Pobierz dostępne style
     *
     * @return array
     */
    public function get_available_styles() {
        return array(
            1 => __( 'Minimalistyczny', 'woo-sale-customizer-ajk' ),
            2 => __( 'Gradientowy', 'woo-sale-customizer-ajk' ),
            3 => __( 'Pulsujący', 'woo-sale-customizer-ajk' ),
            4 => __( 'Z cieniem', 'woo-sale-customizer-ajk' ),
            5 => __( 'Zaokrąglony', 'woo-sale-customizer-ajk' ),
            6 => __( 'Diagonalny', 'woo-sale-customizer-ajk' ),
            7 => __( 'Z ikoną', 'woo-sale-customizer-ajk' ),
            8 => __( 'Przezroczysty', 'woo-sale-customizer-ajk' ),
            9 => __( 'Neonowy', 'woo-sale-customizer-ajk' ),
            10 => __( 'Klasyczny premium', 'woo-sale-customizer-ajk' ),
        );
    }

    /**
     * Generuj CSS dla wszystkich stylów
     *
     * @return string
     */
    public function generate_styles_css() {
        ob_start();
        ?>
        <style id="woosale-customizer-ajk-styles">
        /* Style 1: Minimalistyczny */
        .woosale-style-1.onsale {
            background: rgba(255, 255, 255, 0.85);
            color: #e74c3c;
            border: 2px solid #e74c3c;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 3px;
        }

        /* Style 2: Gradientowy */
        .woosale-style-2.onsale {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.85) 0%, rgba(255, 142, 83, 0.85) 100%);
            color: #ffffff;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }

        /* Style 3: Pulsujący */
        .woosale-style-3.onsale {
            background: rgba(231, 76, 60, 0.85);
            color: #ffffff;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
            animation: woosale-pulse 2s ease-in-out infinite;
        }
        @keyframes woosale-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        /* Style 4: Z cieniem */
        .woosale-style-4.onsale {
            background: rgba(44, 62, 80, 0.85);
            color: #ffffff;
            padding: 7px 15px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4), 0 2px 4px rgba(0, 0, 0, 0.3);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Style 5: Zaokrąglony */
        .woosale-style-5.onsale {
            background: rgba(255, 182, 193, 0.85);
            color: #8b4c6b;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 20px;
            border: 2px solid #ff91a4;
        }

        /* Style 6: Diagonalny */
        .woosale-style-6.onsale {
            background: rgba(231, 76, 60, 0.85);
            color: #ffffff;
            padding: 8px 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transform: rotate(-5deg);
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.4);
        }

        /* Style 7: Z ikoną */
        .woosale-style-7.onsale {
            background: rgba(255, 107, 107, 0.85);
            color: #ffffff;
            padding: 6px 14px 6px 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .woosale-style-7.onsale::before {
            content: "🔥 ";
            margin-right: 4px;
        }

        /* Style 8: Przezroczysty */
        .woosale-style-8.onsale {
            background: rgba(231, 76, 60, 0.85);
            color: #ffffff;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Style 9: Neonowy */
        .woosale-style-9.onsale {
            background: rgba(10, 10, 10, 0.85);
            color: #00ffff;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
            border: 2px solid #00ffff;
            box-shadow: 0 0 10px #00ffff, 0 0 20px #00ffff, inset 0 0 10px rgba(0, 255, 255, 0.2);
            text-shadow: 0 0 5px #00ffff, 0 0 10px #00ffff;
        }

        /* Style 10: Klasyczny premium */
        .woosale-style-10.onsale {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.85) 0%, rgba(184, 148, 31, 0.85) 100%);
            color: #ffffff;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 4px;
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.3);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Wyjście CSS stylów do head
     */
    public function output_styles() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        echo $this->generate_styles_css();
    }
}

// Inicjalizuj wtyczkę tylko jeśli WooCommerce jest dostępny
add_action( 'plugins_loaded', 'woosale_customizer_ajk_init', 10 );

function woosale_customizer_ajk_init() {
    // Sprawdź kompatybilność przed inicjalizacją
    if ( woosale_customizer_ajk_check_woocommerce() ) {
        new WooSaleCustomizerAJK();
    }
}
