<?php


add_filter( 'wpsf_register_settings_wpoa_settings_general', 'wpoa_general_settings' );

/**
 * Tabless example
 */
function wpoa_general_settings( $wpsf_settings ) {

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

			<p><img src="%alert_logo%" /></p>

				%content%

			</div>
		</body>
		</html>';

	// General Settings section
	$wpsf_settings[] = array(
		'section_id'          => 'general',
		'section_title'       => 'General Settings',
		'section_description' => __('Some general settings for the order alerts.', 'woo-pending-orders-alerts'),
		'section_order'       => 5,
		'fields'              => array(
			array(
				'id'         => 'time',
				'title'      => 'Scheduled Time',
				'desc'       => 'Current server time: N/A',
				'type'       => 'time',
				'default'    => '05:00',
				'timepicker' => array(), // Array of timepicker options (http://fgelinas.com/code/timepicker)
			),
			array(
				'id'          => 'wpcorders_email_template',
				'title'       => 'E-mail Template',
				'desc'        => 'Change the e-mail template according to your needs.',
				'placeholder' => 'This is a placeholder.',
				'type'        => 'textarea',
				'default'     => $email_template,
			),		
			array(
				'id'      => 'wpcorders_newsletter_logo',
				'title'   => 'E-mail Alert Logo',
				'desc'    => 'Choose a Logo image for the header of the alert message.',
				'type'    => 'file',
				'default' => plugins_url( "images/logo.png", __FILE__ ),
			)
		)
	);

	return $wpsf_settings;
}
