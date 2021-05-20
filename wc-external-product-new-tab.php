<?php
/**
 * Plugin Name:       WooCommerce External Product New Tab
 * Plugin URI:        https://wordpress.org/plugins/wc-external-product-new-tab
 * Description:       This plugin sets all external / affiliate product buy now links on a WooCommerce site to open in a new web browser tab.
 * Version:           1.0.4
 * Author:            Stuart Duff
 * Author URI:        http://stuartduff.com
 * Requires at least: 5.3
 * Tested up to:      5.8
 * Text Domain: wc-external-product-new-tab
 * Domain Path: /languages/
 * WC requires at least: 5.0
 * WC tested up to: 5.4
 *
 * @package WC_External_Product_New_Tab
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of WC_External_Product_New_Tab to prevent the need to use globals.
 *
 * @since   1.0.0
 * @return  object WC_External_Product_New_Tab
 */
function WC_External_Product_New_Tab() {
  return WC_External_Product_New_Tab::instance();
} // End WC_External_Product_New_Tab()
WC_External_Product_New_Tab();

/**
 * Main WC_External_Product_New_Tab Class
 *
 * @class WC_External_Product_New_Tab
 * @version   1.0.0
 * @since     1.0.0
 * @package   WC_External_Product_New_Tab
 */
final class WC_External_Product_New_Tab {

  /**
   * WC_External_Product_New_Tab The single instance of WC_External_Product_New_Tab.
   * @var     object
   * @access  private
   * @since   1.0.0
   */
  private static $_instance = null;

  /**
   * The token.
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $token;

  /**
   * The version number.
   * @var     string
   * @access  public
   * @since   1.0.0
   */
  public $version;

  /**
   * Constructor function.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public function __construct() {
    $this->token          = 'wc-external-product-new-tab';
    $this->plugin_url     = plugin_dir_url( __FILE__ );
    $this->plugin_path    = plugin_dir_path( __FILE__ );
    $this->version        = '1.0.0';

    register_activation_hook( __FILE__, array( $this, 'install' ) );

    add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

    add_action( 'init', array( $this, 'plugin_setup' ) );

  }

  /**
   * Main WC_External_Product_New_Tab Instance
   *
   * Ensures only one instance of WC_External_Product_New_Tab is loaded or can be loaded.
   *
   * @since   1.0.0
   * @static
   * @see     WC_External_Product_New_Tab()
   * @return  Main WC_External_Product_New_Tab instance
   */
  public static function instance() {
    if ( is_null( self::$_instance ) )
      self::$_instance = new self();
    return self::$_instance;
  } // End instance()

  /**
   * Load the localisation file.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public function load_plugin_textdomain() {
    load_plugin_textdomain( 'wc-external-product-new-tab', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
  }

  /**
   * Installation.
   * Runs on activation. Logs the version number.
   * @access  public
   * @since   1.0.0
   * @return  void
   */
  public function install() {
    $this->log_plugin_version_number();
  }

  /**
   * Log the plugin version number.
   * @access  private
   * @since   1.0.0
   * @return  void
   */
  private function log_plugin_version_number() {
    // Log the version number.
    update_option( $this->token . '-version', $this->version );
  }

  /**
   * Setup all the things.
   * Only executes if WooCommerce External Product New Tab is active and WooCommerce is not.
   * If WooCommerce is inactive an admin notice is displayed.
   * @return void
   */
  public function plugin_setup() {
    if ( class_exists( 'woocommerce' ) ) {
      // Filter only external product archive buttons to open in a new browser tab.
      add_filter( 'woocommerce_loop_add_to_cart_link',  array( $this, 'external_add_product_link' ), 10, 2 );

      // Remove the default WooCommerce single external product add to cart button.
      remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );

      // Add the open in a new browser tab WooCommerce single external product add to cart button.
      add_action( 'woocommerce_external_add_to_cart', array( $this,'wc_external_add_to_cart'), 30 );

    } else {
      add_action( 'admin_notices', array( $this, 'install_woocommerce_core_notice' ) );
    }
  }

  /**
   * WooCommerce core plugin install notice.
   * If the user activates this plugin while not having the WooCommerce core plugin installed or activated, prompt them to install the WooCommerce core plugin.
   * @since   1.0.0
   * @return  void
   */
  public function install_woocommerce_core_notice() {
    echo '<div class="notice is-dismissible updated">
      <p>' . __( 'The WooCommerce External Product New Tab extension requires that you have the main WooCommerce plugin installed and activated.', 'wc-external-product-new-tab' ) . ' <a href="https://www.woocommerce.com/">' . __( 'Get WooCommerce now!', 'wc-external-product-new-tab' ) . '</a></p>
    </div>';
  }

  /**
   * Output external product add to cart button on product archives, shortcodes etc.
   *
   * @since   1.0.0
   * @return  $link
   */
  public function external_add_product_link( $link ) {
    global $product;

    if ( $product->is_type( 'external' ) ) {
      /**
       *  The original code is located in the WooCommerce file /templates/loop/add-to-cart.php
       */
      $link =	sprintf( apply_filters( 'external_add_product_link_html', '<a rel="nofollow noopener noreferrer" href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s" target="_blank">%s</a>' ),
        esc_url( $product->add_to_cart_url() ),
        esc_attr( isset( $quantity ) ? $quantity : 1 ),
        esc_attr( $product->get_id() ),
        esc_attr( $product->get_sku() ),
        esc_attr( isset( $class ) ? $class : apply_filters( 'external_add_product_link_html_classes', 'button product_type_external' ) ),
        esc_html( $product->add_to_cart_text() )
      );
    }

    return $link;
  }

  /**
   * Output the external product add to cart button on single products.
   *
   * @since   1.0.0
   */
  public function wc_external_add_to_cart() {
    global $product;

    if ( ! $product->add_to_cart_url() ) {
      return;
    }

    $product_url = $product->add_to_cart_url();
    $button_text = $product->single_add_to_cart_text();

    /**
     *  The code below this comment outputs the edited add to cart button with target="_blank" added to the html markup.
     *  The original code is located in the WooCommerce file /templates/single-product/add-to-cart/external.php
     */
    do_action( 'woocommerce_before_add_to_cart_button' ); ?>

    <p class="cart">
    	<a href="<?php echo esc_url( $product_url ); ?>" rel="nofollow noopener noreferrer" class="single_add_to_cart_button button alt" target="_blank"><?php echo esc_html( $button_text ); ?></a>
    </p>

    <?php do_action( 'woocommerce_after_add_to_cart_button' );

  }

} // End Class
