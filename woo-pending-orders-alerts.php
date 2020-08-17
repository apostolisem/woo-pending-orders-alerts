<?php
/*
Plugin Name: WPCARE: WooCommerce Pending Orders
Plugin URI: https://wpcare.gr
Description: Sends an e-mail alert when pending orders exist. The e-mail is sent to "admin_email" every morning after 5:00 am. You can change the e-mail from General Options. Just activate the plugin and it works.
Version: 1.0.1
Author: WordPress Care
Author URI: https://wpcare.gr
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: woo-pending-orders-alerts
*/

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

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

	// 1.6
	if ( ! wp_next_scheduled( 'wpcorders_woo_pending_orders' ) ) {
		wp_schedule_event( strtotime('05:00:00'), 'daily', 'wpcorders_woo_pending_orders' );
	}

	add_action( 'wpcorders_woo_pending_orders', 'wpcorders_woo_pending_orders' );

	// 1.7
	add_action('init', 'wpcorders_set_woo_order_status');

	// 1.8
	register_deactivation_hook(__FILE__, 'wpcorders_deactivation_hook');

	// 1.9
	add_action('admin_init', 'wpcorders_register_options');

	// 1.13
	register_activation_hook( __FILE__, 'wpcorders_activation_hook' );


// mark: 2. SHORTCODES



// mark: 3. FILTERS


// mark: 4. EXTERNAL SCRIPTS


// mark: 5. ACTIONS

	// 5.5
	function wpcorders_woo_pending_orders() {

		// check if function is enabled at plugin options
		if (get_option( 'wpcorders_woo_pending_orders' ) !== "on") return;

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
		$email_to = get_option('admin_email');
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

	// 5.6
	function wpcorders_deactivation_hook() {
		wp_clear_scheduled_hook('wpcorders_woo_pending_orders');
		wpcorders_write_log('The WordPress Care Plugin was succesfully Deactivated.');
	}

	// 5.9
	function wpcorders_activation_hook() {
		// check if version of wp is updated
		if ( version_compare( get_bloginfo( 'version' ), '4.9.7', '<' ) )  {
			wpcorders_write_log('The WordPress Care Plugin was NOT activated because the version of WordPress is old. An update to the latest version is required.');
			wp_die("You must update WordPress to use this plugin!");
		} elseif (!wpcorders_woo_enabled()) {
			wpcorders_write_log('The WordPress Care Plugin was NOT activated because WooCommerce is not Activated.');
			wp_die("You must activate WooCommerce to use this plugin!");
		} elseif (!get_option('wpcorders_first_time_installed')) {

			$email_template = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
			<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html;UTF-8">
			</head>
			<body style="background: #efefef; font: 13px \'Lucida Grande\', \'Lucida Sans Unicode\', Tahoma, Verdana, sans-serif; padding: 5px 0 10px" bgcolor="#efefef">
				<style type="text/css">
				body {
					font: 13px "Lucida Grande", "Lucida Sans Unicode", Tahoma, Verdana, sans-serif; background-color: #efefef; padding: 5px 0 10px 0;
				}
				</style>
				<div id="body" style="width: 600px; background-color:#ffffff; padding: 30px; margin: 30px; text-align: left;">

				<p><img src="'.plugins_url( "images/email-logo.png", __FILE__ ).'" /></p>

					%content%

				</div>
			</body>
			</html>';

			// create the default option values if it's the first time the plugin is installed
			update_option( 'wpcorders_first_time_installed', 'no', '', 'yes' );
			// insert default values
			update_option( 'wpcorders_woo_pending_orders', 'on', '', 'yes' );
			update_option( 'wpcorders_email_template', $email_template, '', 'yes' );
			update_option( 'wpcorders_newsletter_logo', '', '', 'yes' );

		}
		wpcorders_write_log('The WordPress Care Plugin was succesfully Activated.');
	}

	// 5.10
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

	// 6.2
	function wpcorders_support_url() {

		return 'http://wpcare.gr';

	}

	// 6.3
	function wpcorders_woo_enabled() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	// 6.4
	function wpcorders_set_woo_order_status(){

		if (!empty($_GET['complete_order']) OR !empty($_GET['cancel_order'])) {
			//things to do if order is set as complete
			$complete_order = $_GET['complete_order'];
			if (!empty($complete_order)) {
				global $woocommerce;
				if ( !$complete_order ) return;
				$order = new WC_Order($complete_order);
				$order->update_status( 'completed' );
				wpcorders_write_log('An order was set to status Complete by click on Pending WooCommerce Orders Email.', 'woocommerce');
			}

			//things to do if order is set as cancelled
			$cancel_order = $_GET['cancel_order'];
			if (!empty($cancel_order)) {
				global $woocommerce;
				if ( !$cancel_order ) return;
				$order = new WC_Order($cancel_order);
				$order->update_status( 'cancelled' );
				wpcorders_write_log('An order was set to status Cancelled by click on Pending WooCommerce Orders Email.', 'woocommerce');
			}
		}
	}

	// 6.5
	function wpcorders_get_option( $option_name ) {

		// setup return variable
		$option_value = '';

		try {

			// get the requested option
			switch( $option_name ) {

				case 'wpcorders_woo_pending_orders':
				// wpcorders_woo_pending_orders
				$option_value = get_option('wpcorders_woo_pending_orders');
				break;
				case 'wpcorders_email_template':
				// wpcorders_email_template
				$option_value = get_option('wpcorders_email_template');
				break;
				case 'wpcorders_newsletter_logo':
				// wpcorders_newsletter_logo
				$option_value = get_option('wpcorders_newsletter_logo');
				break;

			}

		} catch( Exception $e) {

			// php error

		}

		// return option value or it's default
		return $option_value;

	}

	// 6.6
	function wpcorders_get_current_options() {

		// setup our return variable
		$current_options = array();

		try {

			// build our current options associative array
			$current_options = array(
				'wpcorders_woo_pending_orders' => wpcorders_get_option('wpcorders_woo_pending_orders'),
				'wpcorders_email_template' => wpcorders_get_option('wpcorders_email_template'),
				'wpcorders_newsletter_logo' => wpcorders_get_option('wpcorders_newsletter_logo'),
			);

		} catch( Exception $e ) {

			// php error

		}

		// return current options
		return $current_options;

	}

	// 6.8
	function wpcorders_wp_mail( $to, $subject, $message, $tpl=true, $nl2br=true ) {

		$headers  = "From: \"".get_bloginfo('name')."\" <no-reply@".str_replace("www.", "", $_SERVER['SERVER_NAME']).">\r\n";
		$headers .= "Reply-To: ".get_option('admin_email')."\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
		$logo_url = plugins_url( 'images/email-logo.png', __FILE__ );

		if ($nl2br == true ) $message = nl2br($message);

		// compose the message
		$message_html = $message;

		if ($tpl == true ) {
			$message_html = get_option( 'wpcorders_email_template');
			$message_html = str_replace("%content%",$message, $message_html);
		}

		wp_mail($to, $subject, $message_html, $headers);

	}

// mark: 7. CUSTOM POST TYPES


// mark: 8. ADMIN PAGES


// mark: 9. SETTINGS

	// 9.1
	function wpcorders_register_options() {
		// plugin functions
		register_setting('wpcorders_plugin_functions', 'wpcorders_woo_pending_orders');
		// plugin options
		register_setting('wpcorders_plugin_options', 'wpcorders_email_template');
		register_setting('wpcorders_plugin_options', 'wpcorders_newsletter_logo');
	}



// mark: 10. MISCELLANEOUS
