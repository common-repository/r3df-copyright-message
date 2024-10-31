<?php
/*
Plugin Name: 	R3DF - Copyright Message
Description:    Inserts a customizable copyright message in the theme footer.
Plugin URI:		http://r3df.com/
Version: 		1.1.0
Text Domain:	r3df-copyright-message
Domain Path: 	/lang/
Author:         R3DF
Author URI:     http://r3df.com
Author email:   plugin-support@r3df.com
Copyright: 		R-Cubed Design Forge
*/

/*  Copyright 2015 R-Cubed Design Forge

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// TODO
// option for any hook points
// Shortcode like tag for adding current year to custom messages
// Uninstall

// Avoid direct calls to this file where wp core files not present
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Construct
$r3df_copyright_message = new R3DF_Copyright_Message();

/**
 * Class R3DF_Dashboard_Language
 *
 */
class R3DF_Copyright_Message {

	private $twenty_astric_themes_list = array(
		'twentyten',
		'twentyeleven',
		'twentytwelve',
		'twentythirteen',
		'twentyfourteen',
		'twentyfifteen',
		'twentysixteen',
	);

	private $active_theme = '';
	private $current_theme = '';
	private $twenty_astric_theme = false;


	/**
	 * Class constructor
	 *
	 */
	function __construct() {
		// Do common admin and front-end functions

		// Add plugin text domain hook
		add_action( 'load_plugin_textdomain', array( $this, '_text_domain' ) );

		// Add hook for customizer (required in both admin and front-end)
		add_action( 'customize_register', array( $this, 'customizer_options' ) );

		if ( is_admin() ) {
			// Do admin functions

			// Load admin css and javascript
			//add_action( 'admin_enqueue_scripts', array( $this, '_load_admin_scripts_and_styles' ) );
		} else {
			// Do front-end functions

			// Run setup - after theme is setup - earliest hook for is_customize_preview() to work
			// - parse request seems to be the soonest the customizer intercepts options calls
			add_action( 'parse_request', array( $this, '_setup' ) );

			// Load transport js for customizer preview
			add_action( 'customize_preview_init', array( $this, 'load_customizer_preview_js' ) );

		}
	}

	/**
	 * Setup
	 *
	 */
	function _setup() {
		// get theme info
		$this->load_theme_info();

		// insert copyright notice
		add_action( $this->get_location(), array( $this, 'copyright_html' ) );

		// load css and javascript
		add_action( 'wp_enqueue_scripts', array( $this, '_load_scripts_and_styles' ) );

		// Add markers to hook points in html so we can use postMessage
		if ( $this->twenty_astric_theme ) {
			add_action( $this->twenty_astric_theme . '_credits', array( $this, 'action_marker' ) );
		}
		add_action( 'wp_footer', array( $this, 'action_marker' ) );

		// add body classes to assist css
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Get theme information
	 *
	 */
	function load_theme_info() {
		// get active theme
		$this->active_theme = wp_get_theme()->template;

		// if in preview, we need to get theme template being previewed from $wp_customize to get current theme, which may not be active theme
		if ( is_customize_preview() ) {
			global $wp_customize;
			$this->current_theme = $wp_customize->get_template();
		} else {
			$this->twenty_astric_theme = $this->active_theme;
		}

		// check if it is a twenty_astric theme
		if ( in_array( $this->current_theme, $this->twenty_astric_themes_list ) ) {
			$this->twenty_astric_theme = $this->current_theme;
		}
	}

	/**
	 * Get location to display copyright message
	 *
	 * @return string
	 */
	function get_location() {
		$options = get_option( 'r3df_copyright_message', null );

		if ( ! isset( $options['location'] ) ) {
			$options['location'] = $this->get_default( 'location' );
		}

		switch ( $options['location'] ) {
			case 'other':
				if ( ! isset( $options['other_hook'] ) ) {
					$hook = 'wp_footer';
				} else {
					$hook = $options['other_hook'];
				}
				break;
			default:
				$hook = $options['location'];
				break;
		}
		return $hook;
	}


	/**
	 * Add theme name to body classes
	 *
	 * @param $body_classes - array, setting to be returned
	 *
	 * @return array
	 */
	function add_body_class( $body_classes ) {
		// add theme identifier class to body
		$body_classes[] = 'r3df-cm-t-' . $this->current_theme;

		// add a location identifier class to body
		$body_classes[] = 'r3df-cm-l-' . $this->get_location();

		//$options = get_option( 'r3df_copyright_message', null );
		//if ( ! empty( $options['hide-pbw'] ) ) {
		//	$body_classes[] = 'r3df-cm-hide-pbw';
		//}
		return ( $body_classes );
	}


	/**
	 * Add options to customizer
	 *
	 * @param $wp_customize
	 */
	function customizer_options( $wp_customize ) {
		// Load theme related information (needed to check if it's a twenty_astric theme)
		// _setup does not run in first admin load of the customizer options
		$this->load_theme_info();

		$wp_customize->add_panel( 'r3df_copyright_message_settings', array(
			'title'          => __( 'Copyright Message', 'r3df-copyright-message' ),
			'description'    => __( 'Change options to choose the message to display and where to display the message',  'r3df-copyright-message' ),
		) );

		// ***************
		// Message section

		$wp_customize->add_section( 'r3df_copyright_message_message', array(
			'title'          => __( 'Message', 'r3df-copyright-message' ),
			'panel' => 'r3df_copyright_message_settings',
		) );

		$wp_customize->add_setting( 'r3df_copyright_message[use_custom]', array(
			'default' => 'default',
			'type'    => 'option',
			'transport' => 'postMessage',
		) );

		$wp_customize->add_control( 'r3df_copyright_message[use_custom]', array(
			'label'      => 'Message: ',
			'description' => __( 'Choose which message to display for your copyright notice.',  'r3df-copyright-message' ),
			'section'    => 'r3df_copyright_message_message',
			'type'       => 'radio',
			'choices'    => array(
				'default' => $this->get_default( 'copyright' ),
				'custom' => 'Custom message...',
			),
		) );

		$wp_customize->add_setting( 'r3df_copyright_message[custom_message]', array(
			'default' => $this->get_default( 'copyright' ),
			'type'    => 'option',
			'transport' => 'postMessage',
		) );

		$wp_customize->add_control( 'r3df_copyright_message[custom_message]', array(
			'section'         => 'r3df_copyright_message_message',
			'type'            => 'text',
			'active_callback' => array( $this, 'is_custom_copyright' ),
		) );

		// ***************
		// Location section

		$wp_customize->add_section( 'r3df_copyright_message_location', array(
			'title' => __( ' Location', 'r3df-copyright-message' ),
			'panel' => 'r3df_copyright_message_settings',
		) );

		$wp_customize->add_setting( 'r3df_copyright_message[location]', array(
			'default'   => $this->get_default( 'location' ),
			'type'      => 'option',
			'transport' => 'postMessage',
		) );

		$locations = array( 'wp_footer' => 'wp_footer' );
		if ( $this->twenty_astric_theme ) {
			$locations[ $this->twenty_astric_theme . '_credits' ] = $this->twenty_astric_theme . '_credits';
		}
		//$locations['other'] = __( 'Other registered action...', 'r3df-copyright-message' );

		$wp_customize->add_control( 'r3df_copyright_message[location]', array(
			'label'       => __( 'Message location: ', 'r3df-copyright-message' ),
			'description' => __( 'Choose which action hook to use to insert your copyright notice.', 'r3df-copyright-message' ),
			'section'     => 'r3df_copyright_message_location',
			'type'        => 'radio',
			'choices'     => $locations,
		));

		//$wp_customize->add_setting( 'r3df_copyright_message[other_hook]', array(
		//	'default'   => 'wp_footer',
		//	'type'      => 'option',
		//	'sanitize_callback' => 'sanitize_key',
		//	//'transport' => 'postMessage',
		//) );
		//
		//$wp_customize->add_control( 'r3df_copyright_message[other_hook]', array(
		//	'section'         => 'r3df_copyright_message_location',
		//	'type'            => 'text',
		//	'active_callback' => array( $this, 'is_other_hook' ),
		//) );

	}


	/**
	 * Return values for defaults, false if not set
	 *
	 * @param $setting - string, setting to be returned
	 *
	 * @return mixed
	 */
	function get_default( $setting ) {
		$defaults = apply_filters( 'r3df_copyright_message_defaults', array(
			'copyright' => '&#169; ' . date( 'Y' ) . ', ' . get_bloginfo(),
			'location' => $this->twenty_astric_theme ? $this->twenty_astric_theme . '_credits' : 'wp_footer',
		));
		return ( isset( $defaults[ $setting ] ) ? $defaults[ $setting ] : false );
	}


	/**
	 * Add empty div to mark action output location
	 *
	 */
	function action_marker() {
		echo  '<div class="r3df-cm-marker" data-action="'.current_filter().'"></div>';
	}


	/**
	 * Is custom copyright set
	 *
	 * @return bool
	 */
	function is_custom_copyright() {
		$options = get_option( 'r3df_copyright_message', null );
		if ( ! empty( $options['use_custom'] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Is other location selected
	 *
	 * @return bool
	 */
	function is_other_hook() {
		$options = get_option( 'r3df_copyright_message', null );
		if ( ! empty( $options['location'] ) && 'other' == $options['location'] ) {
			return true;
		}
		return false;
	}


	/**
	 * Add copyright
	 *
	 */
	function copyright_html() {
		$options = get_option( 'r3df_copyright_message', null );
		if ( ! empty( $options['use_custom'] ) && ! empty( $options['custom_message'] ) ) { ?>
			<span id="r3df-copyright-message"><?php echo $options['custom_message'] ?></span>
		<?php } else { ?>
			<span id="r3df-copyright-message"><?php echo $this->get_default( 'copyright' ); ?></span>
		<?php }
	}

	/**
	 * Load transport js for customizer preview
	 * Send default value as well, to enable switching in js
	 *
	 */
	function load_customizer_preview_js() {
		wp_register_script(  'r3df_copyright_message_preview', plugins_url( '/js/r3df_copyright_message_preview.js', __FILE__ ), array( 'customize-preview', 'jquery' ) );

		// Add object with default message
		$r3df_copyright_message = array(
			'default' => $this->get_default( 'copyright' ),
		);
		wp_localize_script( 'r3df_copyright_message_preview', 'r3df_copyright_message', $r3df_copyright_message );

		wp_enqueue_script( 'r3df_copyright_message_preview' );
	}


	/* ****************************************************
	 * Utility functions
     * ****************************************************/

	/**
	 * Plugin language file loader
	 *
	 */
	function _text_domain() {
		// Load language files - files must be r3df-copyright-message-xx_XX.mo
		load_plugin_textdomain( 'r3df-copyright-message', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
	}

	/**
	 * Admin scripts and styles loader
	 *
	 * @param $hook
	 *
	 */
	function _load_admin_scripts_and_styles( $hook ) {
		// Get the plugin version (added to files loaded to clear browser caches on change)
		$plugin = get_file_data( __FILE__, array( 'Version' => 'Version' ) );

		// Register and enqueue the admin css files
		wp_register_style( 'r3df_cm_admin_style', plugins_url( '/css/admin-style.css', __FILE__ ), false, $plugin['Version'] );
		wp_enqueue_style( 'r3df_cm_admin_style' );
	}

	/**
	 * Site scripts and styles loader
	 *
	 * @param $hook
	 *
	 */
	function _load_scripts_and_styles( $hook ) {
		// Get the plugin version (added to files loaded to clear browser caches on change)
		$plugin = get_file_data( __FILE__, array( 'Version' => 'Version' ) );

		// Register and enqueue the site css files
		wp_register_style( 'r3df_cm_style', plugins_url( '/css/style.css', __FILE__ ), false, $plugin['Version'] );
		wp_enqueue_style( 'r3df_cm_style' );
	}
}
