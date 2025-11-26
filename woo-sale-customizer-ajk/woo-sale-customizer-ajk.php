<?php
/**
 * Plugin Name: WooSaleCustomizerAJK
 * Plugin URI: https://github.com/jklebucki/WooSaleCustomizerAJK
 * Description: Customizacja etykiety "Sale" w WooCommerce (AJK).
 * Version: 1.0.2
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
    const OPTION_FONT_SIZE_KEY = 'woosale_customizer_ajk_font_size';
    const OPTION_FONT_WEIGHT_KEY = 'woosale_customizer_ajk_font_weight';
    const OPTION_FONT_ITALIC_KEY = 'woosale_customizer_ajk_font_italic';

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
        if ( get_option( self::OPTION_FONT_SIZE_KEY ) === false ) {
            add_option( self::OPTION_FONT_SIZE_KEY, '11' );
        }
        if ( get_option( self::OPTION_FONT_WEIGHT_KEY ) === false ) {
            add_option( self::OPTION_FONT_WEIGHT_KEY, '700' );
        }
        if ( get_option( self::OPTION_FONT_ITALIC_KEY ) === false ) {
            add_option( self::OPTION_FONT_ITALIC_KEY, '0' );
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

            $font_size = isset( $_POST['woosale_customizer_ajk_font_size'] )
                ? absint( $_POST['woosale_customizer_ajk_font_size'] )
                : 11;

            if ( $font_size < 8 || $font_size > 24 ) {
                $font_size = 11;
            }

            $font_weight = isset( $_POST['woosale_customizer_ajk_font_weight'] )
                ? sanitize_text_field( wp_unslash( $_POST['woosale_customizer_ajk_font_weight'] ) )
                : '700';

            $font_italic = isset( $_POST['woosale_customizer_ajk_font_italic'] ) ? '1' : '0';

            update_option( self::OPTION_KEY, $label );
            update_option( self::OPTION_STYLE_KEY, $style );
            update_option( self::OPTION_FONT_SIZE_KEY, $font_size );
            update_option( self::OPTION_FONT_WEIGHT_KEY, $font_weight );
            update_option( self::OPTION_FONT_ITALIC_KEY, $font_italic );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'woo-sale-customizer-ajk' ) . '</p></div>';
        }

        $current_label = get_option( self::OPTION_KEY, __( 'PROMOCJA', 'woo-sale-customizer-ajk' ) );
        $current_style = get_option( self::OPTION_STYLE_KEY, '1' );
        $current_style = absint( $current_style );
        if ( $current_style < 1 || $current_style > 10 ) {
            $current_style = 1;
        }
        $current_font_size = get_option( self::OPTION_FONT_SIZE_KEY, '11' );
        $current_font_weight = get_option( self::OPTION_FONT_WEIGHT_KEY, '700' );
        $current_font_italic = get_option( self::OPTION_FONT_ITALIC_KEY, '0' );
        $available_styles = $this->get_available_styles();
        ?>

        <div class="wrap">
            <h1><?php esc_html_e( 'WooSaleCustomizerAJK – Ustawienia', 'woo-sale-customizer-ajk' ); ?></h1>

            <?php 
            // Wyjście CSS bezpośrednio (bez echo z escapowaniem)
            $this->output_admin_styles(); 
            ?>

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
                            <label for="woosale_customizer_ajk_font_size">
                                <?php esc_html_e( 'Wielkość czcionki', 'woo-sale-customizer-ajk' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                name="woosale_customizer_ajk_font_size"
                                id="woosale_customizer_ajk_font_size"
                                type="number"
                                min="8"
                                max="24"
                                class="small-text"
                                value="<?php echo esc_attr( $current_font_size ); ?>"
                            /> px
                            <p class="description">
                                <?php esc_html_e( 'Rozmiar czcionki w pikselach (8-24).', 'woo-sale-customizer-ajk' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="woosale_customizer_ajk_font_weight">
                                <?php esc_html_e( 'Pogrubienie czcionki', 'woo-sale-customizer-ajk' ); ?>
                            </label>
                        </th>
                        <td>
                            <select
                                name="woosale_customizer_ajk_font_weight"
                                id="woosale_customizer_ajk_font_weight"
                                class="regular-text"
                            >
                                <option value="400" <?php selected( $current_font_weight, '400' ); ?>><?php esc_html_e( 'Normalna', 'woo-sale-customizer-ajk' ); ?></option>
                                <option value="500" <?php selected( $current_font_weight, '500' ); ?>><?php esc_html_e( 'Średnia', 'woo-sale-customizer-ajk' ); ?></option>
                                <option value="600" <?php selected( $current_font_weight, '600' ); ?>><?php esc_html_e( 'Półgruba', 'woo-sale-customizer-ajk' ); ?></option>
                                <option value="700" <?php selected( $current_font_weight, '700' ); ?>><?php esc_html_e( 'Gruba', 'woo-sale-customizer-ajk' ); ?></option>
                                <option value="800" <?php selected( $current_font_weight, '800' ); ?>><?php esc_html_e( 'Bardzo gruba', 'woo-sale-customizer-ajk' ); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Stopień pogrubienia czcionki.', 'woo-sale-customizer-ajk' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="woosale_customizer_ajk_font_italic">
                                <?php esc_html_e( 'Kursywa', 'woo-sale-customizer-ajk' ); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input
                                    name="woosale_customizer_ajk_font_italic"
                                    id="woosale_customizer_ajk_font_italic"
                                    type="checkbox"
                                    value="1"
                                    <?php checked( $current_font_italic, '1' ); ?>
                                />
                                <?php esc_html_e( 'Włącz kursywę', 'woo-sale-customizer-ajk' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Zaznacz aby tekst był pochylony.', 'woo-sale-customizer-ajk' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Podgląd', 'woo-sale-customizer-ajk' ); ?>
                        </th>
                        <td>
                            <div style="position: relative; width: 300px; height: 200px; background: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"300\" height=\"200\"><rect fill=\"%23f5f5f5\" width=\"300\" height=\"200\"/><text x=\"50%\" y=\"50%\" font-size=\"14\" fill=\"%23999\" text-anchor=\"middle\" dy=\".3em\">Podgląd produktu</text></svg>'); border: 1px solid #ddd; margin: 20px 0; border-radius: 8px; overflow: hidden;">
                                <span class="onsale woosale-preview" id="woosale-preview">
                                    <?php echo esc_html( $current_label ); ?>
                                </span>
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Podgląd etykiety z wybranym stylem, tekstem i czcionką.', 'woo-sale-customizer-ajk' ); ?>
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
            var fontSizeInput = document.getElementById('woosale_customizer_ajk_font_size');
            var fontWeightSelect = document.getElementById('woosale_customizer_ajk_font_weight');
            var fontItalicCheckbox = document.getElementById('woosale_customizer_ajk_font_italic');
            var preview = document.getElementById('woosale-preview');

            function updatePreview() {
                var selectedStyle = styleSelect.value;
                var labelText = labelInput.value || '<?php echo esc_js( __( 'PROMOCJA', 'woo-sale-customizer-ajk' ) ); ?>';
                var fontSize = fontSizeInput.value || '11';
                var fontWeight = fontWeightSelect.value || '700';
                var fontItalic = fontItalicCheckbox.checked;
                
                // Usuń wszystkie klasy stylu
                preview.className = 'onsale woosale-preview';
                // Dodaj wybrany styl
                preview.classList.add('woosale-style-' + selectedStyle);
                // Zaktualizuj tekst
                preview.textContent = labelText;
                // Zaktualizuj style czcionki
                preview.style.fontSize = fontSize + 'px';
                preview.style.fontWeight = fontWeight;
                preview.style.fontStyle = fontItalic ? 'italic' : 'normal';
            }

            if (styleSelect && labelInput && preview) {
                styleSelect.addEventListener('change', updatePreview);
                labelInput.addEventListener('input', updatePreview);
                fontSizeInput.addEventListener('input', updatePreview);
                fontWeightSelect.addEventListener('change', updatePreview);
                fontItalicCheckbox.addEventListener('change', updatePreview);
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

        $font_size = get_option( self::OPTION_FONT_SIZE_KEY, '11' );
        $font_weight = get_option( self::OPTION_FONT_WEIGHT_KEY, '700' );
        $font_italic = get_option( self::OPTION_FONT_ITALIC_KEY, '0' );

        $inline_style = 'font-size: ' . esc_attr( $font_size ) . 'px !important; ';
        $inline_style .= 'font-weight: ' . esc_attr( $font_weight ) . ' !important; ';
        $inline_style .= 'font-style: ' . ( $font_italic === '1' ? 'italic' : 'normal' ) . ' !important;';

        return '<span class="onsale woosale-style-' . esc_attr( $style ) . '" style="' . $inline_style . '">' . esc_html( $label ) . '</span>';
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
     * Wyjście CSS stylów bezpośrednio dla strony admina
     */
    public function output_admin_styles() {
        // Bezpośrednie wypisanie CSS bez bufora
        $this->generate_styles_css_direct();
    }

    /**
     * Generuj CSS bezpośrednio (bez bufora)
     */
    public function generate_styles_css_direct() {
        ?>
        <style id="woosale-customizer-ajk-styles">
        /* Style 1: Minimalistyczny - UX 2025 */
        .woosale-style-1.onsale {
            background: rgba(255, 255, 255, 0.3) !important;
            color: #DC2626 !important;
            border: 2px solid rgba(220, 38, 38, 0.6) !important;
            padding: 4px 10px !important;
            border-radius: 6px !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
            transition: all 0.2s ease !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
        }
        .woosale-style-1.onsale:hover {
            background: rgba(255, 255, 255, 0.5) !important;
            transform: scale(1.05) !important;
        }

        /* Style 2: Gradientowy - UX 2025 */
        .woosale-style-2.onsale {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.35) 0%, rgba(249, 115, 22, 0.35) 100%) !important;
            color: #ffffff !important;
            padding: 4px 12px !important;
            border-radius: 8px !important;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3) !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
        }
        .woosale-style-2.onsale:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.5) 0%, rgba(249, 115, 22, 0.5) 100%) !important;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4) !important;
            transform: translateY(-2px) !important;
        }

        /* Style 3: Pulsujący - UX 2025 (subtelniejszy) */
        .woosale-style-3.onsale {
            background: rgba(220, 38, 38, 0.35) !important;
            color: #ffffff !important;
            padding: 4px 12px !important;
            border-radius: 8px !important;
            animation: woosale-pulse-2025 3s ease-in-out infinite !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 2px 10px rgba(220, 38, 38, 0.3) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
        }
        @keyframes woosale-pulse-2025 {
            0%, 100% { box-shadow: 0 2px 10px rgba(220, 38, 38, 0.3); }
            50% { box-shadow: 0 4px 16px rgba(220, 38, 38, 0.5); }
        }

        /* Style 4: Premium shadow - UX 2025 */
        .woosale-style-4.onsale {
            background: rgba(31, 41, 55, 0.4) !important;
            color: #ffffff !important;
            padding: 5px 14px !important;
            border-radius: 10px !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4) !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
        }
        .woosale-style-4.onsale:hover {
            background: rgba(31, 41, 55, 0.55) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
            transform: translateY(-2px) !important;
        }

        /* Style 5: Pill - UX 2025 */
        .woosale-style-5.onsale {
            background: rgba(254, 242, 242, 0.35) !important;
            color: #DC2626 !important;
            padding: 5px 14px !important;
            border-radius: 20px !important;
            border: 2px solid rgba(252, 165, 165, 0.5) !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.15) !important;
            transition: all 0.2s ease !important;
            backdrop-filter: blur(6px) !important;
            -webkit-backdrop-filter: blur(6px) !important;
        }
        .woosale-style-5.onsale:hover {
            background: rgba(254, 226, 226, 0.5) !important;
            border-color: rgba(248, 113, 113, 0.7) !important;
            transform: scale(1.03) !important;
        }

        /* Style 6: Ribbon - UX 2025 (mniej nachylony dla czytelności) */
        .woosale-style-6.onsale {
            background: rgba(220, 38, 38, 0.35) !important;
            color: #ffffff !important;
            padding: 5px 16px !important;
            letter-spacing: 0.5px !important;
            transform: rotate(-2deg) !important;
            box-shadow: 0 2px 10px rgba(220, 38, 38, 0.3) !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 4px !important;
            transition: transform 0.3s ease !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
        }
        .woosale-style-6.onsale:hover {
            transform: rotate(-2deg) scale(1.05) !important;
        }

        /* Style 7: Z ikoną - UX 2025 */
        .woosale-style-7.onsale {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.35) 0%, rgba(185, 28, 28, 0.35) 100%) !important;
            color: #ffffff !important;
            padding: 5px 12px 5px 10px !important;
            border-radius: 8px !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 4px !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 2px 10px rgba(220, 38, 38, 0.3) !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
        }
        .woosale-style-7.onsale::before {
            content: "🔥" !important;
            font-size: 14px !important;
            margin: 0 !important;
        }
        .woosale-style-7.onsale:hover {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.5) 0%, rgba(185, 28, 28, 0.5) 100%) !important;
            transform: scale(1.05) !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4) !important;
        }

        /* Style 8: Glass morphism - UX 2025 */
        .woosale-style-8.onsale {
            background: rgba(220, 38, 38, 0.15) !important;
            color: #1F2937 !important;
            padding: 5px 14px !important;
            border-radius: 12px !important;
            backdrop-filter: blur(16px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
            border: 2px solid rgba(220, 38, 38, 0.25) !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.2) !important;
            transition: all 0.3s ease !important;
        }
        .woosale-style-8.onsale:hover {
            background: rgba(220, 38, 38, 0.25) !important;
            border-color: rgba(220, 38, 38, 0.4) !important;
        }

        /* Style 9: Neon accent - UX 2025 (bardziej subtelny) */
        .woosale-style-9.onsale {
            background: rgba(220, 38, 38, 0.35) !important;
            color: #ffffff !important;
            padding: 5px 14px !important;
            border-radius: 8px !important;
            border: 2px solid rgba(252, 165, 165, 0.5) !important;
            box-shadow: 0 0 16px rgba(220, 38, 38, 0.4), 0 2px 10px rgba(220, 38, 38, 0.3) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            animation: woosale-neon-glow 2s ease-in-out infinite !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        @keyframes woosale-neon-glow {
            0%, 100% { box-shadow: 0 0 16px rgba(220, 38, 38, 0.4), 0 2px 10px rgba(220, 38, 38, 0.3); }
            50% { box-shadow: 0 0 24px rgba(220, 38, 38, 0.6), 0 2px 10px rgba(220, 38, 38, 0.4); }
        }

        /* Style 10: Premium gold - UX 2025 */
        .woosale-style-10.onsale {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.35) 0%, rgba(217, 119, 6, 0.35) 100%) !important;
            color: #ffffff !important;
            padding: 5px 16px !important;
            letter-spacing: 0.5px !important;
            border-radius: 10px !important;
            box-shadow: 0 3px 14px rgba(245, 158, 11, 0.3) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
            border: 2px solid rgba(255, 255, 255, 0.25) !important;
            line-height: 1.2 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            position: absolute !important;
            top: 8px !important;
            right: 8px !important;
            left: auto !important;
            z-index: 999 !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        .woosale-style-10.onsale:hover {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.5) 0%, rgba(217, 119, 6, 0.5) 100%) !important;
            box-shadow: 0 5px 20px rgba(245, 158, 11, 0.4) !important;
            transform: translateY(-2px) scale(1.02) !important;
        }
        </style>
        <?php
    }

    /**
     * Generuj CSS dla wszystkich stylów (z buforem dla wp_head)
     *
     * @return string
     */
    public function generate_styles_css() {
        ob_start();
        $this->generate_styles_css_direct();
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
