<?php

/**
 * Module Name: Site Verification
 * Module Description: Verify your site with Google Search Console, Pinterest, and others.
 * First Introduced: 3.0
 * Sort Order: 33
 * Requires Connection: No
 * Auto Activate: Yes
 * Feature: Engagement
 * Additional Search Queries: webmaster, seo, google, bing, pinterest, search, console
 */

function jetpack_load_verification_tools() {
	include dirname( __FILE__ ) . "/verification-tools/blog-verification-tools.php";
}

function jetpack_verification_tools_loaded() {
	Jetpack::enable_module_configurable( __FILE__ );
	Jetpack::module_configuration_load( __FILE__, 'jetpack_verification_tools_configuration_load' );
}
add_action( 'jetpack_modules_loaded', 'jetpack_verification_tools_loaded' );

/**
 * Set default option on module activation
 */
function jetpack_verification_tools_set_default_options() {
	if ( false === get_option( 'verification_services_codes' ) ) {
		update_option( 'verification_services_codes', 0 );
	}
}

add_action( 'jetpack_activate_module_verification-tools', 'jetpack_verification_tools_set_default_options' );
add_action( 'jetpack_update_default_options_module_verification-tools', 'jetpack_verification_tools_set_default_options' );

function jetpack_verification_tools_configuration_load() {
	wp_safe_redirect( admin_url( 'tools.php' ) );
	exit;
}

jetpack_load_verification_tools();
