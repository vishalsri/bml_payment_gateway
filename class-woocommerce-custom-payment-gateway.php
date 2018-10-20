<?php


class WC_Custom_Payment_Gateway extends WC_Payment_Gateway{


	public $sizes = array(
						'small',
						'medium',
						'large'
					);
	public $required_options = array(
						'yes',
						'no'
					);
	public $date_formats = array(
						'mm/dd/yy',
						'yy-mm-dd',
						'd M, y',
						'd MM, y',
						'DD, d MM, yy',
					);
	public $api_data = array();
	public $data = array();

	public function __construct($child = false){
		$this->id = 'custom_payment';
		$this->method_title = __('Custom Payment Pro','woocommerce-custom-payment-gateway');
		$this->title = __('Custom Payment','woocommerce-custom-payment-gateway');
		$this->has_fields = true;

		$this->init_form_fields();
		$this->init_settings();


		$this->enabled = $this->get_option('enabled');
		$this->title = $this->get_option('title');
		$this->gateway_icon = $this->get_option('gateway_icon');
		$this->debug_mode = $this->get_option('debug_mode');


		$this->description = $this->get_option('description');
		$this->order_status = $this->get_option('order_status');
		$this->customer_note = $this->get_option('customer_note');
		$this->customized_form = $this->get_option('customized_form');

		$this->enable_api = $this->get_option('enable_api');
		$this->redirect_to_api_url = $this->get_option('redirect_to_api_url');

		$this->api_url_to_ping = $this->get_option('api_url_to_ping');
		$this->api_method = $this->get_option('api_method');
		$this->api_post_data_type = $this->get_option('api_post_data_type');


		$this->extra_api_atts = $this->get_option('extra_api_atts');
		$this->wc_api_atts = $this->get_option('wc_api_atts');

		// Debug mode, only administrators can use the gateway.
		if($this->debug_mode == 'yes'){
			if( !current_user_can('administrator') ){
				$this->enabled = 'no';
			}
		}

		add_action( 'woocommerce_receipt_custom_payment', array($this, 'receipt_page') );

		if($child === false){
			add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
		}

		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'process_returned_response' ) );
	}

	public function receipt_page( $order ){
		$request_body = $this->get_request_body( $this->api_data, $order );

		$customgateway_args_array = array();
		foreach ($request_body as $key => $value) {
			$customgateway_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}
		echo '<form action="'.$this->api_url_to_ping.'" method="post" id="customgateway_payment_form">
				' . implode('', $customgateway_args_array) . '
			</form>';
		echo '<script>jQuery(document).ready(function(){
			jQuery("#customgateway_payment_form").submit();

		});</script>';
	}

	public function init_form_fields(){


				$this->form_fields = array(
					'enabled' => array(
						'title' 		=> __( 'Enable/Disable', 'woocommerce-custom-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Enable Custom Payment', 'woocommerce-custom-payment-gateway' ),
						'default' 		=> 'no'
					),
					'title' => array(
						'title' 		=> __( 'Method Title', 'woocommerce-custom-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'The title of the gateway which will show to the user on the checkout page.', 'woocommerce-custom-payment-gateway' ),
						'default'		=> __( 'Custom Payment', 'woocommerce-custom-payment-gateway' ),
					),

					'gateway_icon' => array(
						'title' 		=> __( 'Gateway Icon', 'woocommerce-custom-payment-gateway' ),
						'type' 			=> 'text',
						'description' 	=> __( 'Icon URL for the gateway that will show to the user on the checkout page.', 'woocommerce-custom-payment-gateway' ),
						'default'		=> __( 'http://', 'woocommerce-custom-payment-gateway' ),
					),
					'description' => array(
						'title' => __( 'Customer Message', 'woocommerce-custom-payment-gateway' ),
						'css'	=> 'width:50%;',
						'type' => 'textarea',
						'default' => 'None of the custom payment options are suitable for you? please drop us a note about your favourable payment option and we will contact you as soon as possible.',
						'description' 	=> __( 'The message which you want it to appear to the customer on the checkout page.', 'woocommerce-custom-payment-gateway' ),

					),
					'customer_note' => array(
						'title' => __( 'Customer Note', 'woocommerce-custom-payment-gateway' ),
						'type' => 'textarea',
						'css'	=> 'width:50%;',
						'default' => '',
						'description' 	=> __( 'A note for the customer after the Checkout process.', 'woocommerce-custom-payment-gateway' ),

					),
					'order_status' => array(
						'title' => __( 'Order Status After The Checkout', 'woocommerce-custom-payment-gateway' ),
						'type' => 'select',
						'options' => wc_get_order_statuses(),
						'default' => 'wc-pending',
						'description' 	=> __( 'The default order status if this gateway used in payment.', 'woocommerce-custom-payment-gateway' ),
					),
					'customized_form' => array(
						'type' => 'customized_form',
					),
					'advanced' => array(
						'title'       => __( 'Advanced options<hr>', 'woocommerce-custom-payment-gateway' ),
						'type'        => 'title',
						'description' => '',
					),
					'enable_api' => array(
						'title' 		=> __( 'API requests', 'woocommerce-custom-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Enable the gateway to request an API URL after the checkout process.', 'woocommerce-custom-payment-gateway' ),
						'default' 		=> 'no'
					),
					'api_url_to_ping' => array(
						'title'       => __( 'API URL', 'woocommerce-custom-payment-gateway' ),
						'type'        => 'text',
						'description' => __( 'The gateway will send the payment data to this URL after placing the order.', 'woocommerce-custom-payment-gateway' ),
						'default'     => '',
						'placeholder' => 'http://'
					),
					'redirect_to_api_url' => array(
						'title' 		=> __( 'Redirect the Customer to the API URL', 'woocommerce-custom-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( '', 'woocommerce-custom-payment-gateway' ),
						'default' 		=> 'no'
					),
					'api_method' => array(
						'title' => __( 'Request method', 'woocommerce-custom-payment-gateway' ),
						'type' => 'select',
						'options' => array(
								'post'	=> 'POST',
								'get'	=> 'GET',
							),
						'default' => 'post',
						'description' 	=> __( 'The request method to request the API URL.', 'woocommerce-custom-payment-gateway' ),
					),
					'api_post_data_type' => array(
						'title' => __( 'POST requests data type', 'woocommerce-custom-payment-gateway' ),
						'type' => 'select',
						'options' => array(
							'form'	=> 'FORM DATA',
							'json'	=> 'JSON',
						),
						'default' => 'form',
						'description' 	=> __( 'Change this only if you want to send the API data as a JSON object. This option will only work if POST method is selected and if the user wont be redirected to the API URL.st', 'woocommerce-custom-payment-gateway' ),
					),
					'wc_api_atts' => array(
						'type' => 'wc_api_atts',
					),
					'extra_api_atts' => array(
						'type' => 'extra_api_atts',
					),


					'debug_mode' => array(
						'title' 		=> __( 'Enable Debug Mode', 'woocommerce-custom-payment-gateway' ),
						'type' 			=> 'checkbox',
						'label' 		=> __( 'Enable ', 'woocommerce-custom-payment-gateway' ),
						'default' 		=> 'no',
						'description'	=> __('If debug mode is enabled, the payment gateway will be activated just for the administrator. You can use the debug mode to make sure that the gateway work as you expected.'),
					),
			 );



	}



	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_options() {

		?>
		<h3><?php _e( 'Custom Payment Settings', 'woocommerce-custom-payment-gateway' ); ?></h3>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<table class="form-table">
							<?php $this->generate_settings_html();?>
						</table><!--/.form-table-->
					</div>
					<div id="postbox-container-1" class="postbox-container">
	                        <div id="side-sortables" class="meta-box-sortables ui-sortable">
	                            <div class="postbox " id="wpruby_support">
	                                <div class="handlediv" title="Click to toggle"><br></div>
	                                <h3 class="hndle"><span><i class="fa fa-question-circle"></i>&nbsp;&nbsp;Plugin Support</span></h3>
	                                <div class="inside">
	                                    <div class="support-widget">
	                                        <p>
	                                        <img style="width:100%;" src="https://wpruby.com/wp-content/uploads/2016/03/wpruby_logo_with_ruby_color-300x88.png">
	                                        <br/>
	                                        Got a Question, Idea, Problem or Praise?</p>
	                                        <ul>
	                                            <li>» <a href="https://wpruby.com/submit-ticket/" target="_blank">Support Request</a></li>
	                                            <li>» <a href="https://wpruby.com/knowledgebase-category/woocommerce-custom-payment-gateway/" target="_blank">Documentation and Common issues.</a></li>
	                                            <li>» <a href="https://wpruby.com/plugins/" target="_blank">Our Plugins Shop</a></li>
	                                        </ul>

	                                    </div>
	                                </div>
	                            </div>

	                            <div class="postbox rss-postbox" id="wpruby_rss">
	    							<div class="handlediv" title="Click to toggle"><br></div>
	    								<h3 class="hndle"><span><i class="fa fa-wordpress"></i>&nbsp;&nbsp;WPRuby Blog</span></h3>
	    								<div class="inside">
											<div class="rss-widget">
												<?php
	    											wp_widget_rss_output(array(
	    													'url' => 'https://wpruby.com/feed/',
	    													'title' => 'WPRuby Blog',
	    													'items' => 3,
	    													'show_summary' => 0,
	    													'show_author' => 0,
	    													'show_date' => 1,
	    											));
	    										?>
	    									</div>
	    								</div>
	    						</div>

	                        </div>
	                    </div>
                    </div>
				</div>
				<div class="clear"></div>

		<?php
	}


	public function validate_fields(){

		foreach ($this->customized_form as $key => $field) {
			// if instruction continue
			if($field['field_type'] == 'instructions') continue;
			// credit card needs different saving process
			if($field['field_type'] == 'ccform'){
				$this->data[ 'Card Number' ] = (isset($_POST[$this->id.'-card-number']))?$_POST[$this->id.'-card-number']:'-';
				$this->data[ 'Card Expiry' ] = (isset($_POST[$this->id.'-card-expiry']))?$_POST[$this->id.'-card-expiry']:'-';
				$this->data[ 'Card CVC' ] = (isset($_POST[$this->id.'-card-cvc']))?$_POST[$this->id.'-card-cvc']:'-';
				if($this->data[ 'Card Number' ] == '-' || trim($this->data[ 'Card Number' ]) == '' || !$this->ValidCreditcard(  str_replace(" ", "", $this->data[ 'Card Number' ])  )){
					wc_add_notice('"Card Number"' . __(' must be valid.','woocommerce-custom-payment-gateway'), 'error');
					return false;
				}
				if($this->data[ 'Card Expiry' ] == '-' || trim($this->data[ 'Card Expiry' ]) == ''){
					wc_add_notice('"Card Expiry"' . __(' must be not empty.','woocommerce-custom-payment-gateway'), 'error');
					return false;
				}
				if($this->data[ 'Card CVC' ] == '-' || trim($this->data[ 'Card CVC' ]) == ''){
					wc_add_notice('"Card CVC"' . __(' must be not empty.','woocommerce-custom-payment-gateway'), 'error');
					return false;
				}
				if(isset($field['elements']['ccard-number-api-parameter']['value']) && trim($field['elements']['ccard-number-api-parameter']['value']) != ''){
					$this->api_data[ $field['elements']['ccard-number-api-parameter']['value'] ] = $this->data[ 'Card Number' ];
				}
				if(isset($field['elements']['ccard-expiry-date-api-parameter']['value']) && trim($field['elements']['ccard-expiry-date-api-parameter']['value']) != ''){
					$this->api_data[ $field['elements']['ccard-expiry-date-api-parameter']['value'] ] = $this->data[ 'Card Expiry' ];
				}
				if(isset($field['elements']['ccard-cvc-code-api-parameter']['value']) && trim($field['elements']['ccard-cvc-code-api-parameter']['value']) != ''){
					$this->api_data[ $field['elements']['ccard-cvc-code-api-parameter']['value'] ] = $this->data[ 'Card Number' ];
				}
			}else{
				$value = isset($_POST[$this->id .'_' . $key])?$_POST[$this->id .'_' . $key]:'-';
				$field_name = (isset($field['elements']['name']['value']) && trim($field['elements']['name']['value'])!='')?$field['elements']['name']['value']:ucfirst($field['field_type']);

				if($field['elements']['required']['value'] === 'yes'){
					if(is_array($value)){
						if(empty($value)){
							wc_add_notice('"' . $field_name . '"' . __(' must be not empty.','woocommerce-custom-payment-gateway'), 'error');
							return false;
						}
					}else{
						if('' === trim($value)){
							wc_add_notice('"' . $field_name . '"' . __(' must be not empty.','woocommerce-custom-payment-gateway'), 'error');
							return false;
						}
					}
					if(!$this->validate_field($field['field_type'], $value, $field_name)){
						return false;
					}
				}


				$this->data[ $field_name ] = (is_array($value))?implode(', ', $value):$value;
				if(isset($field['elements']['api-parameter']['value']) && trim($field['elements']['api-parameter']['value']) != ''){
					$this->api_data[ $field['elements']['api-parameter']['value'] ] = (is_array($value))?implode(', ', $value):$value;
				}
			}

		}
	}
	public function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		update_post_meta((int)$order_id, 'woocommerce_customized_payment_data', $this->data);
		update_post_meta((int)$order_id, 'woocommerce_customized_customer_note', $this->customer_note);


		// Update order status
		$order->update_status($this->order_status);

		// Reduce stock levels
		//$order->reduce_order_stock(); deprecated since 3.0
		wc_reduce_stock_levels( $order_id );

		if(trim($this->customer_note) != ''){
			$order->add_order_note($this->customer_note, 1);
		}
		// Remove cart
		$woocommerce->cart->empty_cart();

		// ping to URL.

		if( $this->api_url_to_ping && $this->enable_api == 'yes' ){
			$is_return = $this->ping_api( $this->api_data, $order_id, $order );
			if(isset($is_return['redirect'])){
				return array(
					'result' => 'success',
					'redirect' => $is_return['redirect']
				);
			}
		}

		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}


	public function validate_field($field_type, $value, $field_name){
		switch ($field_type) {
			case 'email':
				if(!is_email( $value )){  wc_add_notice(__('Please enter a valid email at "','woocommerce-custom-payment-gateway') . $field_name . '"', 'error'); return false;  }
				break;
			case 'number':
				if(!is_numeric( $value )){ wc_add_notice(__('Please enter a valid number at "','woocommerce-custom-payment-gateway') . $field_name . '"', 'error');  return false; }
				break;
			case 'url':
				if(filter_var($value, FILTER_VALIDATE_URL) === FALSE){ wc_add_notice(__('Please enter a valid URL at "','woocommerce-custom-payment-gateway') . $field_name . '"', 'error');  return false; }
				break;
			case 'currency':
				if(!is_numeric( $value )){ wc_add_notice(__('Please enter a valid number at "','woocommerce-custom-payment-gateway') . $field_name . '"', 'error');  return false; }
				break;
			case 'phone':
				if(!is_numeric( $value )){ wc_add_notice(__('Please enter a valid number at "','woocommerce-custom-payment-gateway') . $field_name . '"', 'error');  return false; }
				break;
			default:
				# code...
				break;
		}
		return true;
	}
	public function payment_fields(){

		?>
		<?php if(trim($this->description) != ''): ?>
				<fieldset><?php echo $this->description; ?></fieldset>
		<?php endif; ?>
		<fieldset>
		<?php
		$current_field = 1;
		if(is_array($this->customized_form)){
			foreach($this->customized_form as $key => $field): ?>
					<p class="form-row form-row-wide">
					<?php
						$this->render_checkout_field($field, $key, $current_field);
						$current_field++;
					?>
			 		</p>
					<div class="clear"></div>
			<?php endforeach; ?>
			</fieldset>
			<?php

		}

	}

public function render_checkout_field($field, $key, $current_field){
	$field_name = (isset($field['elements']['name']['value']) && trim($field['elements']['name']['value'])!='')?$field['elements']['name']['value']:ucfirst($field['field_type']);
	$field_default  = (isset($field['elements']['default-value']['value']))?$field['elements']['default-value']['value']:'';
	$field_id = $this->id . '_' . $key;
	$css_class = (isset($field['elements']['css-classes']['value']))?$field['elements']['css-classes']['value']:'';
	$field_size = (isset($field['elements']['size']['value']))?$field['elements']['size']['value'].'-field':'';
	$field_description = (isset($field['elements']['description']['value']) and trim($field['elements']['description']['value'])!=='')?'<span class="hint--top hint--info" data-hint="'. esc_attr($field['elements']['description']['value']) .'">&#8505;</span>':'';
	$required = ((isset($field['elements']['required']['value'])) && $field['elements']['required']['value'] === 'yes')?'<span class="required">*</span>':'';
	$label	=	'<label for="'.$field_id.'">'.$field_name . ' ' . $required .' '.	$field_description .' </label> ';
	switch ($field['field_type']) {
		case 'text':
			echo $label;
			echo '<input id="'.$field_id.'" class="input-text '.$css_class.' '.$field_size.'" type="text" name="'.$field_id.'" value="'.esc_attr($field_default).'">';
			break;
		case 'time':
			echo $label;
			echo '<input id="'.$field_id.'" class="input-text '.$css_class.' '.$field_size.'" type="time" name="'.$field_id.'" value="'.esc_attr(($field_default!='')?$field_default:date('H:i')).'">';
			break;
		case 'textarea':
			echo $label;
			echo '<textarea id="'.$field_id.'" class="input-text '.$css_class.' '.$field_size.'" name="'.$field_id.'">'.stripslashes($field_default).'</textarea>';
			break;
		case 'checkbox':
			echo $label;
			foreach($field['elements']['options']['value'] as $option){
				echo '<input id="'.$field_id.'" '. checked($field_default, $option, false) .' class="input-checkbox '.$css_class.'" type="checkbox" name="'.$field_id.'[]" value="'.$option.'">' . $option . '<br/>';
			}
			break;
			case 'radio':
			echo $label;
			foreach($field['elements']['options']['value'] as $option){
				echo '<input id="'.$field_id.'" '. checked($field_default, $option, false) .' class="input-checkbox '.$css_class.'" type="radio" name="'.$field_id.'" value="'.$option.'">' . $option.'<br/>';
			}
			break;
		case 'select':
			echo $label;
			echo '<select id="'.$field_id.'" name="'.$field_id.'">';
			foreach($field['elements']['options']['value'] as $option){
				echo '<option '. selected($field_default, $option, false) .' value="'.$option.'">'.$option.'</option>';
			}
			echo '</select>';
			break;
		case 'email':
			echo $label;
			echo '<input id="'.$field_id.'" class="input-text '.$css_class.' '.$field_size.'" type="email" name="'.$field_id.'" value="'.esc_attr($field_default).'">';
			break;
		case 'date':
			echo $label;
			echo '<input data-defaultdate="0" id="'.$field_id .'" data-dateformat="'. $field['elements']['date-format']['value']. '"  class="input-date '.$css_class.' '.$field_size.'" type="text" name="'.$field_id.'" >';
			break;
		case 'currency':
			echo $label;
			echo '<input placeholder="'.get_woocommerce_currency_symbol().'" id="'.$field_id.'" class="input-text small-field '.$css_class.'" type="number" min="0" name="'.$field_id.'" value="'.esc_attr($field_default).'" >';
			break;
		case 'url':
			echo $label;
			echo '<input placeholder="http://" id="'.$field_id.'" class="input-text '.$css_class.' '.$field_size.'" type="text" name="'.$field_id.'" value="'.esc_attr($field_default).'">';
			break;
		case 'phone':
			echo $label;
			echo '<input placeholder="" id="'.$field_id.'" class="input-text '.$css_class.' '.$field_size.'" type="text" name="'.$field_id.'" value="'.esc_attr($field_default).'">';
			break;
		case 'number':
			echo $label;
			echo '<input id="'.$field_id.'" class="input-text '.$css_class.' '.$field_size.'" type="number" min="0" name="'.$field_id.'" value="'.esc_attr($field_default).'">';
			break;
		case 'instructions':
			echo $label;
			echo '<div class="woocommerce-info">'. $field['elements']['instructions-(html-tags-allowed)']['value']  .'</div>';
			break;
		case 'ccform':
			echo $label;
			echo '<fieldset>';
			if(version_compare(WC()->version, '2.6', '>=')){ $cc_form = new WC_Payment_Gateway_CC; $cc_form->id = $this->id; $cc_form->form(); }else{ $this->credit_card_form(); }
			echo '</fieldset>';
			break;
		default:
			# code...
			break;
	}
}
public function validate_customized_form_field( $k ){
	$fields = array();
	$elements_counter = 0;
	foreach($_POST as $key => $value){
		if(strpos($key, 'field_') === 0){
			$key_elements = explode('_', $key);
			$field_id = $key_elements[4];
			$fields[$field_id]['elements'][$key_elements[2]]['type'] = $key_elements[3];
			$fields[$field_id]['elements'][$key_elements[2]]['function'] = $key_elements[2];
			$fields[$field_id]['elements'][$key_elements[2]]['value'] = $_POST[$key];
			$fields[$field_id]['field_type'] = $key_elements[1];
			$elements_counter++;
		}
	}
	return $fields;
}

public function validate_extra_api_atts_field( $k ){
	$attributes = array();
	if(!isset($_POST['extra_keys'])) return '';
	if(!isset($_POST['extra_values'])) return '';
	foreach($_POST['extra_keys'] as $key => $value){
		$attributes[ $value ] = $_POST[ 'extra_values' ][$key];
	}
	return $attributes;
}
public function validate_wc_api_atts_field( $k ){
	$attributes = array();
	if(!isset($_POST['wc_keys'])) return '';
	if(!isset($_POST['wc_values'])) return '';
	foreach($_POST['wc_keys'] as $key => $value){
		$attributes[ $value ] = $_POST[ 'wc_values' ][$key];
	}
	return $attributes;
}

public function render_field(	$field, $field_id, $current_field){
	$field_title = '';
	$html_form = '';
	foreach($field['elements'] as $key => $item){
		$field_type = $field['field_type'];
		$item_name = ucfirst(str_replace('-', ' ', $item['function']));
	    $field_name = 'field_' . $field_type .'_'. strtolower($item['function']) . '_' . $item['type'] .'_'. $field_id;
		switch(  $item['type'] ){
		    		case 'text':
		    			if($item['function'] == 'name'){
		    				if(trim($item['value'])==''){
		    					if(in_array($field_type, array('ccform','url'))){
		    						$field_title = strtoupper($field_type);
		    					}else{
		    						$field_title = ucfirst($field_type);
		    					}
		    				}else{
		    					$field_title = $item['value'];
		    				}
		    			}
		    			$html_form .= '<p class="description description-wide"><label>'. $item_name .'<br/><input class="widefat code" type="text" name="'.	$field_name	.'" value="'. esc_attr($item['value']) .'" /></label></p>';
		    		break;
                    case 'time':
                        $html_form .= '<p class="description description-wide"><label>'. $item_name .'<br/><input class="widefat code" type="time" name="'.	$field_name	.'" value="'. esc_attr($item['value']) .'" /></label></p>';
                    break;
		    		case 'textarea':
		    			$html_form .= '<p class="description description-wide"><label>'. $item_name .'<br/><textarea class="widefat code" name="'. $field_name	.'">'. stripslashes($item['value']) .'</textarea></label></p>';
		    		break;
		    		case 'select':
		    			$options = '';
		    			if($item['function'] == 'date-format'){
							foreach($this->date_formats as $format){
			    				$options .= '<option value="'. $format .'"'. selected($item['value'], $format, false) .'>'. $format .'</option>';
			    			}
		    			}elseif($item['function'] == 'required'){
		    				foreach($this->required_options as $option){
			    				$options .= '<option value="'. $option .'"'. selected($item['value'], $option, false) .'>'. ucfirst($option) .'</option>';
			    			}
		    			}
		    			else{
		    				foreach($this->sizes as $size){
			    				$options .= '<option value="'. $size .'"'. selected($item['value'], $size, false) .'>'. ucfirst($size) .'</option>';
			    			}
		    			}

		    			$html_form .= '<p class="description description-thin"><label>'. $item_name .'<br/><select class="widefat code" name="'.	$field_name	.'">' . $options . '</label></select></p>';
		    		break;

		    		case 'options':
		    			$html_form .= '<p class="description description-wide"<label>'. $item_name .'<br/>';
		    			$html_form .= '<ul class="field_options" id="'. $field_name . '">';
		    			foreach($item['value'] as $option){
		    					$html_form .= '<li><input name="'. $field_name .'[]" class="code" value="'. $option .'" type="text" /><span class="delete_option dashicons dashicons-trash"></span><span class="dashicons  dashicons-menu"></span></li>';
		    				}
		    			$html_form .= '</ul>';
		    			$html_form .= '<a class="add-option-btn button-secondary" data-field="'.$field_name.'" href="javascript:void(0)"><span class="dashicons dashicons-plus-alt"></span>Add Option</a>';
		    			$html_form .= '</label></p>';
		    		break;
		}
	}

	$new_element =	'<li id="field_'. $current_field .'" class="group '. $field_type.'">'.
							    '<h3>'. $field_title .' <div class="controls"><label>'. ucfirst($field_type) .'</label><a href="javascript:void(0)" class="delete_field_from_header"><span class="dashicons dashicons-trash"></span></a> </div></h3>'.
							    '<div class="form_details">'. $html_form .
							    '<a href="javascript:void(0)" class="delete_field"><span class="dashicons dashicons-trash"></span>'.__('Delete','woocommerce-custom-payment-gateway').'</a> </div>'.
					'</li>';
	return $new_element;
}

public function get_request_body( $api_data, $order_id ){
	$request_body = array();
	if(is_array($this->extra_api_atts) && !empty($this->extra_api_atts)){
		$request_body = $this->extra_api_atts;
	}
	if(!empty($api_data)){
		$request_body = array_merge($api_data, $request_body);
	}
	if(isset($this->wc_api_atts)){
		$wc_data = array();
		$order = new WC_Order($order_id);
		foreach($this->wc_api_atts as $key => $value){
			switch ($value) {
				case 'order_id':
					$wc_data[$key] = $order->get_id();
					break;
				case 'order_total':
					$wc_data[$key] = $order->get_total();
					break;
				case 'billing_first_name':
					$wc_data[$key] = $order->get_billing_first_name();
					break;
				case 'billing_last_name':
					$wc_data[$key] = $order->get_billing_last_name();
					break;
				case 'billing_postcode':
					$wc_data[$key] = $order->get_billing_postcode();
					break;
				case 'billing_city':
					$wc_data[$key] = $order->get_billing_city();
					break;
				case 'billing_state':
					$wc_data[$key] = $order->get_billing_state();
					break;
				case 'billing_country':
					$wc_data[$key] = $order->get_billing_country();
					break;
				case 'billing_email':
					$wc_data[$key] = $order->get_billing_email();
					break;
				case 'billing_phone':
					$wc_data[$key] = $order->get_billing_phone();
					break;
				case 'billing_ip_address':
					$wc_data[$key] = $order->get_customer_ip_address();
					break;
				case 'return_url':
					$wc_data[$key] = $this->get_return_url( $order );
					break;
			}
		}
		$wc_data = apply_filters('custom_payment_gateways_api_data', $wc_data, $order_id);
		return array_merge($wc_data, $request_body);

	}
}
public function ping_api( $api_data, $order_id, $order = false ){
	$request_body = $this->get_request_body( $api_data, $order_id );

	if($this->redirect_to_api_url == 'yes'){
		if($this->api_method == "get"){
			return array('redirect' => $this->api_url_to_ping . '?' . http_build_query( $request_body ));
		}else{
			return array('redirect' =>  $order->get_checkout_payment_url( true ) );
		}
	}else{
	    if($this->api_post_data_type === 'json' && $this->api_method === 'post'){

		    $response = wp_remote_post( $this->api_url_to_ping, array(
				    'headers'   => array('Content-Type' => 'application/json; charset=utf-8'),
				    'body'      => json_encode($request_body),
				    'method'    => 'POST',
			    )
		    );
        }else{
		    $response = wp_remote_post( $this->api_url_to_ping, array(
				    'method' => strtoupper($this->api_method),
				    'body' => $request_body,
			    )
		    );
        }

	}


}



public function generate_customized_form_html(){
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_auspost_debug_mode">Custom Form</label>
							</th>
			<td class="forminp">
				<fieldset>
					<div id="custom_payment_form_components">
						<ul class="form_components_col1">
							<li><a href="#" class="draggable-form-item" data-type="text" id="form-element-text"><b></b>Text</a></li>
							<li><a href="#" class="draggable-form-item" data-type="checkbox" id="form-element-checkbox"><b></b>Checkbox</a></li>
							<li><a href="#" class="draggable-form-item" data-type="select" id="form-element-select"><b></b>Select</a></li>
							<li><a href="#" class="draggable-form-item" data-type="date" id="form-element-datepicker"><b></b>Date</a></li>
							<li><a href="#" class="draggable-form-item" data-type="url" id="form-element-url"><b></b>URL</a></li>
							<li><a href="#" class="draggable-form-item" data-type="number" id="form-element-digits"><b></b>Number</a></li>
							<li><a href="#" class="draggable-form-item" data-type="ccform" id="form-element-file"><b></b>CCard Form</a></li>
						</ul>
						<ul class="form_components_col2">
							<li><a href="#" class="draggable-form-item" data-type="textarea" id="form-element-textarea"><b></b>Textarea</a></li>
							<li><a href="#" class="draggable-form-item" data-type="radio" id="form-element-radio"><b></b>Radio</a></li>
							<li><a href="#" class="draggable-form-item" data-type="email" id="form-element-email"><b></b>Email</a></li>
                            <li><a href="#" class="draggable-form-item" data-type="time" id="form-element-time"><b></b>Time</a></li>
                            <li><a href="#" class="draggable-form-item" data-type="currency" id="form-element-currency"><b></b>Currency</a></li>
							<li><a href="#" class="draggable-form-item" data-type="phone" id="form-element-phone"><b></b>Phone</a></li>
							<li><a href="#" class="draggable-form-item" data-type="instructions" id="form-element-instructions"><b></b>Instructions</a></li>
						</ul>
					</div>
					<div id="custom_payment_form_fields">
						<ul id="fields_wrap">
							<?php
							$current_field = 1;
							if(is_array($this->customized_form)){
								foreach($this->customized_form as $key => $field): ?>
										<?php
											echo $this->render_field($field, $key, $current_field);
											$current_field++;
										?>
								<?php
								endforeach;
							}
							?>
						</ul>
					</div>
				</fieldset>
			</td>
		</tr>
		<script type="text/javascript">
			var fields_counter = <?php echo (!$this->customized_form)?0:max((array_keys($this->customized_form))); ?>;
		</script>
		<?php
		return ob_get_clean();

	}

public function generate_extra_api_atts_html(){
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_auspost_debug_mode"><?php _e('Extra API Parameters', 'woocommerce-custom-payment-gateway'); ?></label>
							</th>
			<td class="forminp">
				<fieldset>
					<table id="customized_payment_extra_attrs" class="wp-list-table widefat fixed striped posts">
						<thead>
							<tr>
								<th class="column-key"><?php _e('Key', 'woocommerce-custom-payment-gateway'); ?></th>
								<th><?php _e('Value', 'woocommerce-custom-payment-gateway'); ?></th>
								<th class="column-featured"></th>
							</tr>
						</thead>
						<tbody>

							<?php if(isset($this->extra_api_atts) and is_array($this->extra_api_atts)): ?>
								<?php foreach($this->extra_api_atts as $key => $value): ?>
									<tr>
										<td><input name="extra_keys[]" class="widefat" value="<?php echo esc_attr($key); ?>" type="text"></td>
										<td><input name="extra_values[]" class="widefat" value="<?php echo esc_attr($value); ?>" type="text"></td>
										<td><a class="delete_api_key" href="javascript:void(0);"><span class="dashicons  dashicons-trash"></span></a></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="3"><a id="add_row_extra_atts_button" href="javascript:void(0);" class="button"><?php _e('Add Row', 'woocommerce-custom-payment-gateway'); ?></a></th>
							</tr>
						</tfoot>
					</table>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
	public function generate_wc_api_atts_html(){
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_auspost_debug_mode"><?php _e('WooCommerce API Parameters', 'woocommerce-custom-payment-gateway'); ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<table id="customized_payment_wc_attrs" class="wp-list-table widefat fixed striped posts">
						<thead>
							<tr>
								<th class="column-key"><?php _e('Key', 'woocommerce-custom-payment-gateway'); ?></th>
								<th><?php _e('Value', 'woocommerce-custom-payment-gateway'); ?></th>
								<th class="column-featured"></th>
							</tr>
						</thead>
						<tbody>

							<?php if(isset($this->wc_api_atts) and is_array($this->wc_api_atts)): ?>
								<?php foreach($this->wc_api_atts as $key => $value): ?>
									<tr>
										<td><input name="wc_keys[]" class="widefat" value="<?php echo esc_attr($key); ?>" type="text">

										</td>
										<td><select name="wc_values[]">
												<option <?php selected($value, 'order_id'); ?> value="order_id">Order ID</option>
												<option <?php selected($value, 'order_total'); ?> value="order_total">Order Total</option>
												<option <?php selected($value, 'billing_first_name'); ?> value="billing_first_name">Customer First Name</option>
												<option <?php selected($value, 'billing_last_name'); ?> value="billing_last_name">Customer Last Name</option>
												<option <?php selected($value, 'billing_postcode'); ?> value="billing_postcode">Customer Postcode</option>
												<option <?php selected($value, 'billing_city'); ?> value="billing_city">Customer City</option>
												<option <?php selected($value, 'billing_state'); ?> value="billing_state">Customer State</option>
												<option <?php selected($value, 'billing_country'); ?> value="billing_country">Customer Country</option>
												<option <?php selected($value, 'billing_email'); ?> value="billing_email">Customer Email</option>
												<option <?php selected($value, 'billing_phone'); ?> value="billing_phone">Customer Phone</option>
												<option <?php selected($value, 'billing_ip_address'); ?> value="billing_ip_address">Customer IP Address</option>
												<option <?php selected($value, 'return_url'); ?> value="return_url">Order Return URL</option>
											</select></td>
										<td><a class="delete_api_key" href="javascript:void(0);"><span class="dashicons  dashicons-trash"></span></a></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="3"><a id="add_row_wc_atts_button" href="javascript:void(0);" class="button"><?php _e('Add Row', 'woocommerce-custom-payment-gateway'); ?></a></th>
							</tr>
						</tfoot>
					</table>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
	public function get_icon(){
		return (trim($this->gateway_icon) != '')?'<img class="customized_payment_icon" src="'.  esc_attr($this->gateway_icon) .'" />':'';
	}

	public function luhn($number){
	    // Force the value to be a string as this method uses string functions.
	    // Converting to an integer may pass PHP_INT_MAX and result in an error!
	    $number = (string)$number;

	    if (!ctype_digit($number)) {
	        // Luhn can only be used on numbers!
	        return FALSE;
	    }

	    // Check number length
	    $length = strlen($number);

	    // Checksum of the card number
	    $checksum = 0;

	    for ($i = $length - 1; $i >= 0; $i -= 2) {
	        // Add up every 2nd digit, starting from the right
	        $checksum += substr($number, $i, 1);
	    }

	    for ($i = $length - 2; $i >= 0; $i -= 2) {
	        // Add up every 2nd digit doubled, starting from the right
	        $double = substr($number, $i, 1) * 2;

	        // Subtract 9 from the double where value is greater than 10
	        $checksum += ($double >= 10) ? ($double - 9) : $double;
	    }

	    // If the checksum is a multiple of 10, the number is valid
	    return ($checksum % 10 === 0);
	}

	public function ValidCreditcard($number){
	    $card_array = array(
	        'default' => array(
	            'length' => '13,14,15,16,17,18,19',
	            'prefix' => '',
	            'luhn' => TRUE,
	        ),
	        'american express' => array(
	            'length' => '15',
	            'prefix' => '3[47]',
	            'luhn' => TRUE,
	        ),
	        'diners club' => array(
	            'length' => '14,16',
	            'prefix' => '36|55|30[0-5]',
	            'luhn' => TRUE,
	        ),
	        'discover' => array(
	            'length' => '16',
	            'prefix' => '6(?:5|011)',
	            'luhn' => TRUE,
	        ),
	        'jcb' => array(
	            'length' => '15,16',
	            'prefix' => '3|1800|2131',
	            'luhn' => TRUE,
	        ),
	        'maestro' => array(
	            'length' => '16,18',
	            'prefix' => '50(?:20|38)|6(?:304|759)',
	            'luhn' => TRUE,
	        ),
	        'mastercard' => array(
	            'length' => '16',
	            'prefix' => '5[1-5]',
	            'luhn' => TRUE,
	        ),
	        'visa' => array(
	            'length' => '13,16',
	            'prefix' => '4',
	            'luhn' => TRUE,
	        ),
	    );

	    // Remove all non-digit characters from the number
	    if (($number = preg_replace('/\D+/', '', $number)) === '')
	        return FALSE;

	    // Use the default type
	    $type = 'default';

	    $cards = $card_array;

	    // Check card type
	    $type = strtolower($type);

	    if (!isset($cards[$type]))
	        return FALSE;

	    // Check card number length
	    $length = strlen($number);

	    // Validate the card length by the card type
	    if (!in_array($length, preg_split('/\D+/', $cards[$type]['length'])))
	        return FALSE;

	    // Check card number prefix
	    if (!preg_match('/^' . $cards[$type]['prefix'] . '/', $number))
	        return FALSE;

	    // No Luhn check required
	    if ($cards[$type]['luhn'] == FALSE)
	        return TRUE;

	    return $this->luhn($number);
	}

    public function convertToPHPDateFormat($format){
	    switch ($format){
            case 'mm/dd/yy':
                return 'm/d/y';
		    case 'yy-mm-dd':
			    return 'y-m-d';
		    case 'd MM, y':
			    return 'd M, y';
		    case 'DD, d MM, yy':
			    return 'D, d M, y';
        }

        return $format;
    }

	/**
	 * For developers to process returned URLs from 3rd-party gateways
     * @since 1.3.8
	 */
	public function process_returned_response(){
        do_action('custom_payment_process_returned_result');
        exit;
    }

}
