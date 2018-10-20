<?php
/* @wordpress-plugin
 * Plugin Name:       WooCommerce Custom Payment Gateway Pro - kingstheme.com
 * Plugin URI:        https://wpruby.com/plugin/woocommerce-custom-payment-gateway-pro/
 * Description:       Make your own custom payment gateway.
 * Version:           1.3.8
 * Author:            WPRuby
 * Author URI:        https://wpruby.com
 * Text Domain:       woocommerce-custom-payment-gateway
 * Domain Path: /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */



if(wpruby_custom_gateway_is_woocommerce_active()){

	add_action( 'plugins_loaded', 'wpruby_custom_payment_activate_old_license' );
	function wpruby_custom_payment_activate_old_license(){
		if ( get_option( 'custom_payment_103_version' ) != 'upgraded' ) {
			// 1.0.3 activate the license for old customers
	    	if(get_option('wc_custompayment_license_key') != false){
	    		add_option('wc_custompayment_license_key_license_status', 'valid') or update_option('wc_custompayment_license_key_license_status', 'valid');
	    	}
			add_option( "custom_payment_103_version", 'upgraded' );
	    }
	}
	add_filter('woocommerce_payment_gateways', 'add_custom_payment_gateway');
	function add_custom_payment_gateway( $gateways ){
		$gateways['wpruby_wc_custom'] = 'WC_Custom_Payment_Gateway';
        $stored_gateways = json_decode(get_option('wpruby_generated_custom_gatwayes'));

		if($stored_gateways){
			foreach($stored_gateways as $gateway){
	        	$gateway->name =  'custom_' . md5($gateway->name);
				$gateways[ $gateway->name ] =  $gateway->name;
			}
		}
		return $gateways;
	}

	add_action('plugins_loaded', 'init_custom_payment_gateway');
	function init_custom_payment_gateway(){
		require_once 'class-woocommerce-custom-payment-gateway.php';
		require_once plugin_dir_path(__FILE__).'includes/gateway-classes-generator.php';
	}

	add_action( 'plugins_loaded', 'custom_payment_load_plugin_textdomain' );
	function custom_payment_load_plugin_textdomain() {
	  load_plugin_textdomain( 'woocommerce-custom-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	add_action( 'admin_init', 'custom_payment_admin_css' );
	function custom_payment_admin_css() {
       	wp_enqueue_style( 'custom_payment_admin_css', plugins_url('includes/assets/css/admin.css', __FILE__) );
   	}

   	add_action( 'admin_init', 'custom_payment_admin_js');
   	function custom_payment_admin_js(){
		wp_enqueue_script( 'custompayment', plugins_url( "includes/assets/js/custompayment.js", __FILE__ ) , array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-accordion' ), '1.3.5', true );
   	}
   	add_action( 'wp_enqueue_scripts', 'custom_payment_front_css' );
	function custom_payment_front_css() {
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('custom_payment_front_js',plugins_url('includes/assets/js/custom-payment-front.js', __FILE__), array('jquery-ui-datepicker') );
		wp_enqueue_style( 'jquery-ui-datepicker-style' , '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/flick/jquery-ui.css');
       	wp_enqueue_style( 'custom_payment_front_css', plugins_url('includes/assets/css/front.css', __FILE__) );
       	wp_enqueue_style( 'hint-css', plugins_url('includes/assets/css/hint.min.css', __FILE__) );
   	}

   	function wes_woocommerce_payment_information( $post ) {
	   	$order_id = $post->ID;
	    if(isset($_GET['delete_payment']) && $_GET['delete_payment'] == 'true'){
			delete_post_meta($order_id, 'woocommerce_customized_payment_data');
		}
		$data = get_post_meta($order_id, 'woocommerce_customized_payment_data', true);
		if($data){
			add_meta_box(
		        'woocommerce_customized_payment_gateway',
		        __( 'Payment Information' , 'woocommerce-custom-payment-gateway'),
		        'render_woocommerce_payment_information_metabox',
		        'shop_order',
		        'normal',
		        'high'
		    );
		}
	}
	add_action( 'add_meta_boxes_shop_order', 'wes_woocommerce_payment_information', 1, 2 );
	function render_woocommerce_payment_information_metabox( $post ){
		$order_id = $post->ID;
		$data = get_post_meta($order_id, 'woocommerce_customized_payment_data', true);
		if($data){ ?>
			<h2>Order #<?php echo $order_id; ?> <?php _e('Submitted Payment Information', 'woocommerce-custom-payment-gateway'); ?>.</h2>
			<table class="wp-list-table widefat fixed striped posts">
				<tbody>
			<?php foreach ($data as $key => $value) { ?>
					<tr>
						<th style="width:150px; !important;"><strong><?php echo $key; ?></strong></th>
						<td><?php if($key == "Card Number"){ echo str_replace(" ", "", $value); }else{ echo $value; } ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table><br>

			<a style="color:#a00;" onclick="if(!confirm('Are you sure that you want to delete payment information?')){return false;}" href="<?php echo admin_url('post.php?post='. $order_id .'&action=edit&delete_payment=true') ?>">Delete Information</a>

	<?php	}
	}
}
add_action('woocommerce_thankyou', 'wpruby_custompayment_add_customer_note_to_thank_you_page');
function wpruby_custompayment_add_customer_note_to_thank_you_page($order_id){
	$customer_note =	get_post_meta((int)$order_id, 'woocommerce_customized_customer_note', true);
	if($customer_note){
		echo '<p>'	. $customer_note . '</p>';
	}
}
// Add payment information to emails
function wpruby_custompayment_add_information_emails($order, $sent_to_admin){
	if(get_option('show_payment_data_in_email') === 'yes'){

	$order_id = $order->id;
	$data = get_post_meta($order_id, 'woocommerce_customized_payment_data', true);
		if($data){ ?>
			<h2><?php _e('Submitted Payment Information', 'woocommerce-custom-payment-gateway'); ?>:</h2>
			<table>
				<tbody>
			<?php foreach ($data as $key => $value) { ?>
					<tr>
						<th style="width:150px; !important;"><strong><?php echo $key; ?></strong></th>
						<td><?php echo $value; ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
	<?php	}
	}
}
add_action('woocommerce_email_order_details', 'wpruby_custompayment_add_information_emails', 10, 2);

function wpruby_custom_gateway_is_woocommerce_active(){
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() )
		$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

	return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
}

// Updates and Licence Handling
if(!defined('WPRUBY_SL_STORE_URL')) define( 'WPRUBY_SL_STORE_URL', 'https://wpruby.com' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system
define( 'WPRUBY_WOOCUSTOM_ITEM_NAME', 'WooCommerce Custom Payment Gateway Pro' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	include( dirname( __FILE__ ) . '/includes/EDD_SL_Plugin_Updater.php' );
}
if(!class_exists('WPRuby_Licence_Handler')){
	include( dirname( __FILE__ ) . '/includes/WPRuby_Licence_Handler.php' );
}
if(!class_exists('Generate_Custom_Payment_Gateways')){
	add_filter('woocommerce_get_settings_pages', 'wpruby_add_custom_payment_settings_tab');
	function wpruby_add_custom_payment_settings_tab($pages){
		$pages[] = include( dirname( __FILE__ ) . '/includes/classes/class-generate-custom-payment-gateways.php' );
		return $pages;
	}
}

// Licence Handler
$license_handler = new WPRuby_Licence_Handler('wc_custompayment_license_key');
$license_handler->setPage('wc-settings');
$license_handler->setSection('custom_payment');
$license_handler->setReturnUrl(admin_url('admin.php?page=wc-settings&tab=checkout&section=custom_payment'));
$license_handler->setPluginName(WPRUBY_WOOCUSTOM_ITEM_NAME);
// Update Handler
$license_key = trim( get_option( 'wc_custompayment_license_key' ) );
$edd_updater = new EDD_SL_Plugin_Updater( WPRUBY_SL_STORE_URL, __FILE__, array(
	'version' 	=> '1.3.8',		// current version number
	'license' 	=> $license_key,	// license key (used get_option above to retrieve from DB)
	'item_name' => WPRUBY_WOOCUSTOM_ITEM_NAME,	// name of this plugin
	'author' 	=> 'Waseem Senjer',	// author of this plugin
	'url'       => home_url()
));
