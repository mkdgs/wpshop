<?php
/**
 * Checkout
 * 
 * The WPShop checkout class handles the checkout process, collecting user data and processing the payment.
 *
 * @class 		wpwhop_checkout
 * @package		WPShop
 * @category	Class
 * @author		Eoxia
 */

/* Instantiate the class from the shortcode */
function wpshop_checkout_init() {
	global $wpshop_checkout;
	$wpshop_checkout = &new wpshop_checkout();
	$wpshop_checkout->display_form();
}

class wpshop_checkout {
	
	var $div_register, $div_infos_register, $div_login, $div_infos_login = 'display:block;';
	var $creating_account = true;
	
	/** Constructor of the class
	* @return void
	*/
	function __construct () {
	}
	
	/** Affiche le formulaire de commande
	* @return void
	*/
	function display_form() {
	
		global $wpshop, $wpshop_account, $wpshop_cart, $civility;
	
		if(!empty($_GET['action']) && $_GET['action']=='cancel') {
			// On vide le panier
			$wpshop_cart->empty_cart();
			echo __('Your order has been succesfully cancelled.', 'wpshop');
			return false;
		}
		
		// Si le panier n'est pas vide
		if($wpshop_cart->is_empty()) :
			echo '<p>'.__('Your cart is empty. Select product(s) before checkout.','wpshop').'</p>';
		else :
		
			$this->managePost();
			
			$user_id = get_current_user_id();
			
			// On r�cup�re les m�thodes de paiements disponibles
			$paymentMethod = get_option('wpshop_paymentMethod', array());
			
			// PAYPAL
			if(!empty($paymentMethod['paypal']) && isset($_POST['modeDePaiement']) && $_POST['modeDePaiement']=='paypal') 
			{
				wpshop_paypal::display_form($_SESSION['order_id']);
				// On vide le panier
				$wpshop_cart->empty_cart();
			}
			// CHECK
			elseif(!empty($paymentMethod['checks']) && isset($_POST['modeDePaiement']) && $_POST['modeDePaiement']=='check') 
			{
				// On r�cup�re les informations de paiements par ch�que
				$paymentInfo = get_option('wpshop_paymentAddress', true);
				echo '<p>'.__('Thank you ! Your order has been placed and you will receive a confirmation email shortly.', 'wpshop').'</p>';
				echo '<p>'.__('You have to send the check with the good amount to the adress :', 'wpshop').'</p>';
				echo $paymentInfo['company_name'].'<br />';
				echo $paymentInfo['company_street'].'<br />';
				echo $paymentInfo['company_postcode'].', '.$paymentInfo['company_city'].'<br />';
				echo $paymentInfo['company_country'].'<br /><br />';
				echo '<p>'.__('Your order will be shipped upon receipt of the check.', 'wpshop').'</p>';
				
				// On vide le panier
				$wpshop_cart->empty_cart();
			}
			// CIC
			elseif(/*!empty($paymentMethod['cic']) && */isset($_POST['modeDePaiement']) && $_POST['modeDePaiement']=='cic') 
			{
				wpshop_CIC::display_form($_SESSION['order_id']);
				// On vide le panier
				$wpshop_cart->empty_cart();
			}
			else {
			
				if($user_id) {
					global $current_user;
					get_currentuserinfo();
					$shipping_info = get_user_meta($current_user->ID, 'shipping_info', true);
					$billing_info = get_user_meta($current_user->ID, 'billing_info', true);
					
					// Si il n'y pas d'info de livraison et de facturation on redirectionne l'utilisateur
					if(empty($shipping_info) || empty($billing_info)) {
						wpshop_tools::wpshop_safe_redirect(get_permalink(get_option('wpshop_myaccount_page_id')).(strpos(get_permalink(get_option('wpshop_myaccount_page_id')), '?')===false ? '?' : '&').'action=editinfo&return=checkout');
					}
					
					// Display the page
					echo '<p>'.sprintf(__('Hi <strong>%s</strong>, you would like to take an order :','wpshop'), $billing_info['first_name'].' '.$billing_info['last_name']).'</p>';
					echo '<div class="half">';
					echo '<h2>'.__('Shipping address', 'wpshop').'</h2>';
					echo $shipping_info['first_name'].' '.$shipping_info['last_name'];
					echo empty($shipping_info['company'])?'<br />':', <i>'.$shipping_info['company'].'</i><br />';
					echo $shipping_info['address'].'<br />';
					echo $shipping_info['postcode'].', '.$shipping_info['city'].'<br />';
					echo $shipping_info['country'];
					echo '</div>';

					echo '<div class="half">';
					echo '<h2>'.__('Billing address', 'wpshop').'</h2>';
					echo $civility[$billing_info['civility']].' '.$billing_info['first_name'].' '.$billing_info['last_name'];
					echo empty($billing_info['company'])?'<br />':', <i>'.$billing_info['company'].'</i><br />';
					echo $billing_info['address'].'<br />';
					echo $billing_info['postcode'].', '.$billing_info['city'].'<br />';
					echo $billing_info['country'];
					echo '</div>';
					
					echo '<p><a href="'.get_permalink(get_option('wpshop_myaccount_page_id')).(strpos(get_permalink(get_option('wpshop_myaccount_page_id')), '?')===false ? '?' : '&').'action=editinfo&amp;return=checkout" title="'.__('Edit shipping & billing info...', 'wpshop').'">'.__('Edit shipping & billing info...', 'wpshop').'</a></p>';
					
					echo '<h2>'.__('Summary of the order','wpshop').'</h2>';
					$wpshop_cart->display_cart($hide_button=true);
					
					// Display the several payment methods
					wpshop_payment::display_payment_methods_choice_form();
				}
				else {
				
					echo '<div class="infos_bloc" id="infos_register" style="'.$this->div_infos_register.'">'.__('Already registered? <a href="#" class="checkoutForm_login">Please login</a>.','wpshop').'</div>';
					echo '<div class="infos_bloc" id="infos_login" style="'.$this->div_infos_login.'">'.__('Not already registered? <a href="#" class="checkoutForm_login">Please register</a>.','wpshop').'</div>';
					
					echo '<div id="reponseBox"></div>';
					
					echo '<form  method="post" id="register_form" action="'.WPSHOP_AJAX_FILE_URL.'">';
						echo '<input type="hidden" name="post" value="true" />';
						echo '<input type="hidden" name="elementCode" value="ajax_register" />';
						// Bloc REGISTER
						echo '<div class="col1" id="register" style="'.$this->div_register.'">';
							$wpshop_account->display_billing_and_shipping_form_field();
							echo '<input type="submit" name="submitOrderInfos" value="'.__('Take order','wpshop').'"" />';
						echo '</div>';
					echo '</form>';
					
					echo '<form method="post" id="login_form" action="'.WPSHOP_AJAX_FILE_URL.'">';
						echo '<input type="hidden" name="post" value="true" />';
						echo '<input type="hidden" name="elementCode" value="ajax_login" />';
						// Bloc LOGIN
						echo '<div class="col1" id="login" style="'.$this->div_login.'">';
							echo '<div class="create-account">'; 
								$wpshop_account->display_login_form();
							echo '</div>';
							echo '<input type="submit" name="submitLoginInfos" value="'.__('Login and order','wpshop').'" />';
						echo '</div>';
					echo '</form>';	
				}
			}
		endif;
	}
	
	/** Traite les donn�es re�us en POST
	 * @return void
	*/
	function managePost() {
	
		global $wpshop, $wpshop_account;
		
		// Confirmation (derni�re �tape)
		if(isset($_POST['takeOrder'])) {
			// Paypal
			if(isset($_POST['modeDePaiement']) && $_POST['modeDePaiement']=='paypal') {
				$this->process_checkout($paymentMethod='paypal');
			}
			// Ch�que
			elseif(isset($_POST['modeDePaiement']) && $_POST['modeDePaiement']=='check') {
				$this->process_checkout($paymentMethod='check');
			}
			// Ch�que
			elseif(isset($_POST['modeDePaiement']) && $_POST['modeDePaiement']=='cic') {
				$this->process_checkout($paymentMethod='cic');
			}
			else $wpshop->add_error(__('You have to choose a payment method to continue.', 'wpshop'));
			
		}
		else {
			$this->div_login = $this->div_infos_login = 'display:none';
		}
		
		// Si il y a des erreurs
		if($wpshop->error_count()>0) {
			echo $wpshop->show_messages();
			return false;
		}
		else return true;
	}
	
	/** Register a new customer, need $_POST data, don't use out of context
	* @return boolean
	*/
	function new_customer_account(){
		global $wpdb, $wpshop, $wpshop_account;

		// Checkout fields (non-shipping/billing)
		$this->posted['terms'] 				= 	isset($_POST['terms']) ? 1 : 0;
		$this->posted['createaccount'] 		= 	true;
		$this->posted['payment_method'] 	= 	isset($_POST['payment_method']) ? wpshop_tools::wpshop_clean($_POST['payment_method']) : '';
		$this->posted['shipping_method']	= 	isset($_POST['shipping_method']) ? wpshop_tools::wpshop_clean($_POST['shipping_method']) : '';
		$this->posted['account_username']	= 	isset($_POST['account_username']) ? wpshop_tools::wpshop_clean($_POST['account_username']) : '';
		$this->posted['account_password'] 	= 	isset($_POST['account_password_1']) ? wpshop_tools::wpshop_clean($_POST['account_password_1']) : '';
		$this->posted['account_password_2'] = 	isset($_POST['account_password_2']) ? wpshop_tools::wpshop_clean($_POST['account_password_2']) : '';
		$this->posted['account_email'] 		= 	isset($_POST['account_email']) ? wpshop_tools::wpshop_clean($_POST['account_email']) : null;
		$this->posted['account_civility'] 		= 	isset($_POST['account_civility']) ? wpshop_tools::wpshop_clean($_POST['account_civility']) : null;

		// On verifie certains champs du formulaire
		if (empty($this->posted['account_civility']) OR !in_array($this->posted['account_civility'], array(1,2,3))) $wpshop->add_error(__('Please enter an user civility', 'wpshop'));
		if (empty($this->posted['account_password'])) $wpshop->add_error(__('Please enter an account password.', 'wpshop'));
		if ($this->posted['account_password_2'] !== $this->posted['account_password']) $wpshop->add_error(__('Passwords do not match.', 'wpshop'));

		// On s'assure que le nom d'utilisateur est libre
		if (!validate_username($this->posted['account_username'])) :
			$wpshop->add_error( __('Invalid email/username.', 'wpshop') );
		elseif (username_exists($this->posted['account_username'])) :
			$wpshop->add_error( __('An account is already registered with that username. Please choose another.', 'wpshop') );
		endif;

		// Check the e-mail address
		if (email_exists($this->posted['account_email'])) :
			$wpshop->add_error(__('An account is already registered with your email address. Please login.', 'wpshop'));
		endif;

		// Si il n'y a pas d'erreur
		if ($wpshop->error_count()==0) :

			/** Cr�ation compte client */
			$reg_errors = new WP_Error();
			do_action('register_post', $this->posted['account_email'], $this->posted['account_email'], $reg_errors);
			$errors = apply_filters('registration_errors', $reg_errors, $this->posted['account_email'], $this->posted['account_email']);

			// if there are no errors, let's create the user account
			if (!$reg_errors->get_error_code()) :

				$user_pass = $this->posted['account_password'];
				$user_id = wp_create_user($this->posted['account_username'], $user_pass, $this->posted['account_email']);
				if (!$user_id) {
					$wpshop->add_error(sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'wpshop'), get_option('admin_email')));
					return false;
				}
				// Change role
				wp_update_user(array('ID' => $user_id, 'role' => 'customer'));

				// Set the WP login cookie
				$secure_cookie = is_ssl() ? true : false;
				wp_set_auth_cookie($user_id, true, $secure_cookie);

				// Envoi du mail d'inscription
				wpshop_tools::wpshop_prepared_email($this->posted['account_email'], 'WPSHOP_SIGNUP_MESSAGE', array(
					'customer_first_name' => $_POST['account_first_name'], 
					'customer_last_name' => $_POST['account_last_name']
				));

				// R�cupere les donn�es en POST et enregistre les infos de livraison et facturation
				$wpshop_account->save_billing_and_shipping_info($user_id);

				return true;
			else :
				$wpshop->add_error($reg_errors->get_error_message());
				return false;
			endif;

		endif;

		return false;
	}

	/** Enregistre la commande dans la bdd apr�s que les champs aient �t� valid�, ou que l'utilisateur soit connect�
	 * @param int $user_id=0 : id du client passant commande. Par d�faut 0 pour un nouveau client
	 * @return void
	*/
	function process_checkout($paymentMethod='paypal') {
	
		global $wpdb, $wpshop, $wpshop_cart;
		
		if (is_user_logged_in()) :
		
			$user_id = get_current_user_id();
		
			$order_data = array(
				'post_type' => WPSHOP_NEWTYPE_IDENTIFIER_ORDER,
				'post_title' => sprintf(__('Order - %s','wpshop'), mysql2date('d M Y\, H:i:s', current_time('mysql', 0), true)),
				'post_status' => 'publish',
				/*'post_excerpt' => $this->posted['order_comments'],*/
				'post_author' => $user_id,
				'comment_status' => 'closed'
			);

			// Cart items
			$order_items = array();
			$order_tva = array();

			//$cart = (array)$wpshop_cart->cart;
			$cart = (array)$_SESSION['cart'];

			// Nouvelle commande
			$order_id = wp_insert_post($order_data);
			$_SESSION['order_id'] = $order_id;

			// Informations de commande � stocker
			$currency = wpshop_tools::wpshop_get_currency(true);
			$order = array_merge(array(
				'order_key' => wpshop_orders::get_new_order_reference(),
				'customer_id' => $user_id,
				'order_status' => 'awaiting_payment',
				'order_date' => current_time('mysql', 0),
				'order_payment_date' => null,
				'order_shipping_date' => null,
				'payment_method' => $paymentMethod,
				'order_invoice_ref' => '',
				'order_currency' => $currency
			), $cart);
			
			// On enregistre la commande
			update_post_meta($order_id, '_order_postmeta', $order);

			/*	Set custmer information for the order	*/
			wpshop_orders::set_order_customer_addresses($user_id, $order_id);

			/*	Notify the customer as the case	*/
			$user_info = get_userdata($user_id);
			$email = $user_info->user_email;
			$first_name = $user_info->user_firstname ;
			$last_name = $user_info->user_lastname;
			// Envoie du message de confirmation de commande au client
			wpshop_tools::wpshop_prepared_email($email, 'WPSHOP_ORDER_CONFIRMATION_MESSAGE', array('customer_first_name' => $first_name, 'customer_last_name' => $last_name));

		endif;
	}

}