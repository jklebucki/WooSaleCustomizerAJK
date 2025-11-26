<?php
/**
 * Plugin Name: WooSaleCustomizerAJK
 * Plugin URI: https://github.com/jklebucki/WooSaleCustomizerAJK
 * Description: Customizacja etykiety "Sale" w WooCommerce (AJK).
 * Version: 1.0.1
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

        // Uniwersalne ukrywanie etykiet sale z różnych motywów
        add_action( 'wp_head', array( $this, 'hide_theme_sale_badges' ), 999 );
        
        // Wyłączenie etykiet sale w popularnych motywach przez ich filtry
        add_filter( 'astra_woo_shop_sale_badge_enabled', '__return_false', 999 );
        add_filter( 'kadence_woocommerce_product_sale_flash', '__return_false', 999 );
        add_filter( 'flatsome_sale_flash', '__return_false', 999 );
        add_filter( 'oceanwp_display_sale_badge', '__return_false', 999 );

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
     * Ukryj etykiety sale z różnych motywów za pomocą CSS (uniwersalne rozwiązanie)
     */
    public function hide_theme_sale_badges() {
        ?>
        <style type="text/css">
            /* Uniwersalne ukrywanie etykiet sale z motywów WordPress */
            
            /* Motywy: Astra */
            .ast-onsale-card,
            .ast-on-card-button.ast-onsale-card {
                display: none !important;
            }
            
            /* Motywy: Kadence */
            .kadence-sale-badge,
            .product-label.sale-badge {
                display: none !important;
            }
            
            /* Motywy: OceanWP */
            .owp-sale-badge,
            .outofstock-badge {
                display: none !important;
            }
            
            /* Motywy: Flatsome */
            .sale-label,
            .badge-container .sale-bubble {
                display: none !important;
            }
            
            /* Motywy: Storefront */
            .storefront-sale-flash {
                display: none !important;
            }
            
            /* Motywy: GeneratePress */
            .generate-sale-flash {
                display: none !important;
            }
            
            /* Motywy: Neve */
            .nv-sale-badge {
                display: none !important;
            }
            
            /* Motywy: Blocksy */
            .ct-sale-badge {
                display: none !important;
            }
            
            /* Dodatkowe selektory dla innych motywów */
            .theme-sale-badge,
            .product-sale-flash,
            .woo-sale-badge,
            span.onsale:not(.woosale-style-1):not(.woosale-style-2):not(.woosale-style-3):not(.woosale-style-4):not(.woosale-style-5):not(.woosale-style-6):not(.woosale-style-7):not(.woosale-style-8):not(.woosale-style-9):not(.woosale-style-10):not(.woosale-preview) {
                display: none !important;
            }
        </style>
        <?php
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
            background: rgba(255, 255, 255, 0.85) !important;
            color: #e74c3c !important;
            border: 2px solid #e74c3c !important;
            padding: 5px 12px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            border-radius: 3px !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
        }

        /* Style 2: Gradientowy */
        .woosale-style-2.onsale {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.85) 0%, rgba(255, 142, 83, 0.85) 100%) !important;
            color: #ffffff !important;
            padding: 6px 14px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            border-radius: 4px !important;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3) !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: none !important;
        }

        /* Style 3: Pulsujący */
        .woosale-style-3.onsale {
            background: rgba(231, 76, 60, 0.85) !important;
            color: #ffffff !important;
            padding: 6px 14px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            border-radius: 4px !important;
            animation: woosale-pulse 2s ease-in-out infinite !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: none !important;
        }
        @keyframes woosale-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        /* Style 4: Z cieniem */
        .woosale-style-4.onsale {
            background: rgba(44, 62, 80, 0.85) !important;
            color: #ffffff !important;
            padding: 7px 15px !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            border-radius: 4px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4), 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: none !important;
        }

        /* Style 5: Zaokrąglony */
        .woosale-style-5.onsale {
            background: rgba(255, 182, 193, 0.85) !important;
            color: #8b4c6b !important;
            padding: 8px 16px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            border-radius: 20px !important;
            border: 2px solid #ff91a4 !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
        }

        /* Style 6: Diagonalny */
        .woosale-style-6.onsale {
            background: rgba(231, 76, 60, 0.85) !important;
            color: #ffffff !important;
            padding: 8px 20px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1.5px !important;
            transform: rotate(-5deg) !important;
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.4) !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: none !important;
        }

        /* Style 7: Z ikoną */
        .woosale-style-7.onsale {
            background: rgba(255, 107, 107, 0.85) !important;
            color: #ffffff !important;
            padding: 6px 14px 6px 10px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            border-radius: 4px !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: none !important;
        }
        .woosale-style-7.onsale::before {
            content: "🔥 " !important;
            margin-right: 4px !important;
        }

        /* Style 8: Przezroczysty */
        .woosale-style-8.onsale {
            background: rgba(231, 76, 60, 0.85) !important;
            color: #ffffff !important;
            padding: 6px 14px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            border-radius: 4px !important;
            backdrop-filter: blur(5px) !important;
            -webkit-backdrop-filter: blur(5px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
        }

        /* Style 9: Neonowy */
        .woosale-style-9.onsale {
            background: rgba(10, 10, 10, 0.85) !important;
            color: #00ffff !important;
            padding: 6px 14px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            border-radius: 4px !important;
            border: 2px solid #00ffff !important;
            box-shadow: 0 0 10px #00ffff, 0 0 20px #00ffff, inset 0 0 10px rgba(0, 255, 255, 0.2) !important;
            text-shadow: 0 0 5px #00ffff, 0 0 10px #00ffff !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
        }

        /* Style 10: Klasyczny premium */
        .woosale-style-10.onsale {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.85) 0%, rgba(184, 148, 31, 0.85) 100%) !important;
            color: #ffffff !important;
            padding: 7px 16px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            border-radius: 4px !important;
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            line-height: normal !important;
            display: inline-block !important;
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
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
