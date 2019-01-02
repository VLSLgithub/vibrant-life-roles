<?php
/**
 * Provides helper functions.
 *
 * @since	  {{VERSION}}
 *
 * @package	Vibrant_Life_Roles
 * @subpackage Vibrant_Life_Roles/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		{{VERSION}}
 *
 * @return		Vibrant_Life_Roles
 */
function VIBRANTLIFEROLES() {
	return Vibrant_Life_Roles::instance();
}