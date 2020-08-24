<?php
/*
Plugin Name: WooCommerce Pending Orders Alerts
Plugin URI: https://wpcare.gr
Description: Sends morning e-mail alerts to the shop manager when there are pending orders. Useful feature to remember pending orders.
Version: 1.2.0
Author: WPCARE
Author URI: https://wpcare.gr
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: woo-pending-orders-alerts
*/

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly
include_once('settings.php'); // include settings

/*
	// mark: TABLE OF CONTENTS

	1. HOOKS

	2. SHORTCODES

	3. FILTERS

	4. EXTERNAL SCRIPTS

	5. ACTIONS

	6. HELPERS

	7. CUSTOM POST TYPES

	8. ADMIN PAGES

	9. SETTINGS

	10. MISCELLANEOUS
*/

// mark: 1. HOOKS

	// 1.1
	if ( ! wp_next_scheduled( 'wpcorders_woo_pending_orders' ) ) {
		$time_to_schedule = get_wpoa_setting( 'wpcorders_scheduled_time' );
		wp_schedule_event( strtotime($time_to_schedule), 'daily', 'wpcorders_woo_pending_orders' );
	}

	// 1.2
	add_action( 'wpcorders_woo_pending_orders', 'wpcorders_woo_pending_orders' );

	// 1.3
	add_action('init', 'wpcorders_set_woo_order_status');

	// 1.4
	register_deactivation_hook(__FILE__, 'wpcorders_deactivation_hook');

	// 1.5
	register_activation_hook( __FILE__, 'wpcorders_activation_hook' );


// mark: 2. SHORTCODES


// mark: 3. FILTERS


// mark: 4. EXTERNAL SCRIPTS


// mark: 5. ACTIONS

	// 5.1
	function wpcorders_woo_pending_orders() {

		// check if woocommerce exists and it's enabled
		if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

		$complete_order_button = plugins_url( 'images/email-complete-order.png', __FILE__ );
		$cancel_order_button = plugins_url( 'images/email-cancel-order.png', __FILE__ );
		$order_info_button = plugins_url( 'images/email-order-info.png', __FILE__ );
		$button_style = "style='width: 24px; position: relative; top: 2px;'";

		$args = array(
			'post_type' => 'shop_order',
			//'post_status' => 'publish'
			// not used any more like this -> https://woocommerce.wordpress.com/2014/08/07/wc-2-2-order-statuses-plugin-compatibility/
			'post_status' => array_keys( wc_get_order_statuses() ),
			'meta_key' => '_customer_user',
			'posts_per_page' => '-1'
		);
		$my_query = new WP_Query($args);

		$customer_orders = $my_query->posts;
		$wpcorders_woo_pending_orders = 0;
		$subject = "Υπάρχουν παραγγελίες σε εκκρεμότητα";
		//$email_to = get_option('admin_email');
		//https://stackoverflow.com/questions/57612532/how-to-get-email-recipients-from-new-order-email-in-woocommerce
		$email_to = WC()->mailer()->get_emails()['WC_Email_New_Order']->recipient;
		//$name_of_user_nice = get_option( 'wpcorders_manager_nicename' );
		$orders_included = "";

		//$email_message  = "Γεια σου ".$name_of_user_nice.",
		$email_message  = "Γεια σου,

		Αυτή είναι μια υπενθύμιση για τις ανοικτές παραγγελίες του καταστήματος σου:

		<table width='100%' style='border-collapse: collapse; background-color: #F7F7F7;'><tr><th width='10%' style='padding:7px;'>#</th><th width='60%'>Πελάτης</th><th width='15%'>Ημερομηνία</th><th width='15%'>Επιλογές</th></tr>";

		foreach ($customer_orders as $customer_order) {
			$order_id = $customer_order->ID;

			$order = wc_get_order( $order_id );
			$client_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

			if ($order->get_status() == "failed" OR
			$order->get_status() == "refunded" OR
			$order->get_status() == "cancelled" OR
			$order->get_status() == "completed") {
				//we need only the 'else'
			} else {
				$wpcorders_woo_pending_orders++;
				$orders_included .= $order_id.", ";
				$email_message .= "<tr>";
				$email_message .= "<td style='padding:7px;'>".$order_id."</td>";
				$email_message .= "<td><a href='".get_admin_url()."post.php?post=".$order_id."&action=edit' style='text-decoration:none; color: blue;' target='_blank'>".$client_name ."</a></td>";
				$email_message .= "<td>".date("d-m-Y",strtotime($order->get_date_created()))."</td>";
				$email_message .= "<td><a href='".get_admin_url()."post.php?post=".$order_id."&action=edit&complete_order=".$order_id."&message=1' target='_blank'><img src='".$complete_order_button."' ".$button_style." /></a> <a href='".get_admin_url()."post.php?post=".$order_id."&action=edit&cancel_order=".$order_id."&message=1' target='_blank'><img src='".$cancel_order_button."' ".$button_style." /></a> <a href='".get_admin_url()."post.php?post=".$order_id."&action=edit' target='_blank'><img src='".$order_info_button."' ".$button_style." /></a></td>";
				$email_message .= "</tr>";
			}
		}
		$email_message .= "</table><br />";
		$email_message .= "<b><i>Επεξήγηση Επιλογών</i></b>:<ul style='list-style: none;'><li><span style='color:green;'><img src='".$complete_order_button."' ".$button_style." /> ολοκλήρωση</span> αν η παραγγελία έχει αποσταλλεί</li><li><span style='color:red;'><img src='".$cancel_order_button."' ".$button_style." /> ακύρωση</span> αν η παραγγελία ακυρώθηκε</li><li><span style='color:blue;'><img src='".$order_info_button."' ".$button_style." /> άνοιγμα</span> για προβολή ή επεξεργασία της παραγγελίας</li></ul>
		Θα χρειαστεί να συνδεθείς με το username και τον κωδικό σου για να πραγματοποιήσεις αλλαγές.\r\n\r\nΜε εκτίμηση,\r\nΗ ιστοσελίδα ".get_bloginfo()."!";

		if ($wpcorders_woo_pending_orders > 0) {
			wpcorders_wp_mail($email_to,$subject,$email_message);
			wpcorders_write_log("Pending Orders Email sent to Shop Manager - Orders included: ".rtrim($orders_included,", "), "woocommerce");
		}

	}

	// 5.2
	function wpcorders_deactivation_hook() {
		wp_clear_scheduled_hook('wpcorders_woo_pending_orders');
		wpcorders_write_log('The WordPress Care Plugin was succesfully Deactivated.');
	}

	// 5.3
	function wpcorders_activation_hook() {
		// check if version of wp is updated
		if ( version_compare( get_bloginfo( 'version' ), '4.9.7', '<' ) )  {
			wpcorders_write_log('The WordPress Care Plugin was NOT activated because the version of WordPress is old. An update to the latest version is required.');
			wp_die("You must update WordPress to use this plugin!");
		// check if woocommerce is enabled
		} elseif (!wpcorders_woo_enabled()) {
			wpcorders_write_log('The WordPress Care Plugin was NOT activated because WooCommerce is not Activated.');
			wp_die("You must activate WooCommerce to use this plugin!");
		} 
		wpcorders_write_log('The WordPress Care Plugin was succesfully Activated.');
	}

	// 5.4
	function wpcorders_write_log( $log, $function='' ) {

		$upload_dir = wp_upload_dir();

		if ($function !== '') {
			$File = $upload_dir['basedir']."/wpcorders-plugin-logs/function-".$function.".log";
		} else {
			$File = $upload_dir['basedir']."/wpcorders-plugin-logs/function-core.log";
		}

		if (!file_exists($upload_dir['basedir']."/wpcorders-plugin-logs")) {
		    mkdir($upload_dir['basedir']."/wpcorders-plugin-logs", 0755, true);
		}

	 	$Handle = fopen($File, 'a');
	 	$Data = date("Y-m-d H:i:s")." - ".$log."\r\n";
	 	fwrite($Handle, $Data);
	 	fclose($Handle);

	}


// mark: 6. HELPERS

	// 6.1
	function wpcorders_support_url() {

		return 'http://wpcare.gr';

	}

	// 6.2
	function wpcorders_woo_enabled() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	// 6.3
	function wpcorders_set_woo_order_status(){

		if (!empty($_GET['complete_order'])) {
			//things to do if order is set as complete
			$complete_order = $_GET['complete_order'];
			if (!empty($complete_order)) {
				global $woocommerce;
				if ( !$complete_order ) return;
				$order = new WC_Order($complete_order);
				$order->update_status( 'completed' );
				$order->save();
				wpcorders_write_log('An order was set to status Complete by click on Pending WooCommerce Orders Email.', 'woocommerce');
			}
		} else if (!empty($_GET['cancel_order'])) {
			//things to do if order is set as cancelled
			$cancel_order = $_GET['cancel_order'];
			if (!empty($cancel_order)) {
				global $woocommerce;
				if ( !$cancel_order ) return;
				$order = new WC_Order($cancel_order);
				$order->update_status( 'cancelled' );
				$order->save();
				wpcorders_write_log('An order was set to status Cancelled by click on Pending WooCommerce Orders Email.', 'woocommerce');
			}
		}
	}

	// 6.4
	function wpcorders_wp_mail( $to, $subject, $message, $tpl=true, $nl2br=true ) {

		$headers  = "From: \"".get_bloginfo('name')."\" <no-reply@".str_replace("www.", "", $_SERVER['SERVER_NAME']).">\r\n";
		$headers .= "Reply-To: ".get_option('admin_email')."\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

		if ($nl2br == true ) $message = nl2br($message);

		// compose the message
		$message_html = $message;

		if ($tpl == true ) {
			// $message_html = get_option( 'wpcorders_email_template');
			$message_html = get_wpoa_setting( 'wpcorders_email_template' );
			$logo_url = get_wpoa_setting( 'wpcorders_newsletter_logo' );
			$message_html = str_replace("%alert_logo%", $logo_url, $message_html);
			$message_html = str_replace("%content%", $message, $message_html);
		}

		wp_mail($to, $subject, $message_html, $headers);

	}

// mark: 7. CUSTOM POST TYPES


// mark: 8. ADMIN PAGES


// mark: 9. SETTINGS

	// 9.1
	function get_wpoa_setting( $setting_key ) {
		$result = wpsf_get_setting( 'wpoa_settings_general', 'general', $setting_key );
		if ( strlen($result) <= 0) { // if settings are not saved yet in database, get default values
			global $default_settings;
			$result = $default_settings[$setting_key];
		}
		return $result;
	}

// mark: 10. MISCELLANEOUS
