<?php

add_filter( 'wpsf_register_settings_wpoa_settings_general', 'wpoa_general_settings' );

/**
 * Populate General Settings
 *
 * @param  array $wpsf_settings
 * @return array
 */
function wpoa_general_settings( $wpsf_settings ) {

	// set the default values here
	global $default_settings;
	$default_settings['wpcorders_scheduled_time'] = '05:00';
	$default_settings['wpcorders_newsletter_logo'] = plugins_url( "images/logo.png", dirname(__FILE__) );
	$default_settings['wpcorders_email_template'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
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
				'id'         => 'wpcorders_scheduled_time',
				'title'      => 'Scheduled Time (UTC)',
				'desc'       => 'Current UTC time: ' . gmdate('H:m'),
				'type'       => 'time',
				'default'    => $default_settings['wpcorders_scheduled_time'],
				'timepicker' => array(), // Array of timepicker options (http://fgelinas.com/code/timepicker)
			),
			array(
				'id'          => 'wpcorders_email_template',
				'title'       => 'E-mail Template',
				'desc'        => 'Change the e-mail template according to your needs.',
				'placeholder' => 'This is a placeholder.',
				'type'        => 'textarea',
				'default'     => $default_settings['wpcorders_email_template'],
			),		
			array(
				'id'      => 'wpcorders_newsletter_logo',
				'title'   => 'E-mail Alert Logo',
				'desc'    => 'Choose a Logo image for the header of the alert message.',
				'type'    => 'file',
				'default' => $default_settings['wpcorders_newsletter_logo'],
			)
		)
	);

	return $wpsf_settings;
}

/**
 * Retrieve settings from DB or if not there get the default values.
 *
 * @param  string $setting_key
 * @return mixed
 */
function get_wpoa_setting( $setting_key ) {
	$result = wpsf_get_setting( 'wpoa_settings_general', 'general', $setting_key );
	if ( strlen($result) <= 0) { // if settings are not saved yet in database, get default values
		global $default_settings;
		$result = $default_settings[$setting_key];
	}
	return $result;
}
