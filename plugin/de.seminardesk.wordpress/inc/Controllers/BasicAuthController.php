<?php
/**
 * @package SeminardeskPlugin
 */

namespace Inc\Controllers;

// exit if accessed directly
defined( 'ABSPATH' ) or die ( 'not allowed to access this file' );

/**
 * Handles Basic Authentication for the WordPress site.
 * 
 * Note: Slidely modified version of Basic-Auth Plugin
 * 
 * @link https://github.com/WP-API/Basic-Auth
 */
class BasicAuthController
{
	/**
	 * Code that runs to register the controller
	 *
	 * @return void
	 */
	public function register(){
		add_filter( 'mod_rewrite_rules', array( $this, 'add_htaccess_rules' ) );
		add_filter( 'determine_current_user', array( $this, 'json_basic_auth_handler'), 20 );
		add_filter( 'rest_authentication_errors', array( $this, 'json_basic_auth_error' ) );
	}

	/**
	 * Adds rules to .htaccess
	 * 
	 * @param string $rules 
	 * @return string 
	 */
	public function add_htaccess_rules( $rules )
	{
		$pos = strpos( $rules, 'RewriteRule' );
		// Heredoc - closing identifier is not "clean", PHP will continue looking for one -> parse error result error of autoload
		// https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
		$custom_rules = <<<EOT
# BEGIN Additional rules by SeminarDesk Plugin
# Set BasicAuth for PHP with FastCGI
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
# END Additional rules by SeminarDesk Plugin\n
EOT;
		$rules = substr_replace( $rules, $custom_rules, $pos, 0);
		return $rules;
	}

	public function json_basic_auth_handler( $user ) {
		global $wp_json_basic_auth_error;
	
		$wp_json_basic_auth_error = null;
	
		// Don't authenticate twice
		if ( !empty( $user ) ) {
			return $user;
		}

		// Digest non-'default' authorization params (used e.g. in 1und1 shared
		// hosting setups).
		if ( !empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
				explode(':', base64_decode(substr(
					$_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
		}
	
		// Check that we're trying to authenticate
		if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			return $user;
		}
	
		$username = $_SERVER['PHP_AUTH_USER'];
		$password = $_SERVER['PHP_AUTH_PW'];
	
		/**
		 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
		 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
		 * recursion and a stack overflow unless the current function is removed from the determine_current_user
		 * filter during authentication.
		 */
		remove_filter( 'determine_current_user', array( $this, 'json_basic_auth_handler'), 20 );
	
		$user = wp_authenticate( $username, $password );
	
		add_filter( 'determine_current_user', array( $this, 'json_basic_auth_handler'), 20 );
	
		if ( is_wp_error( $user ) ) {
			$wp_json_basic_auth_error = $user;
			return null;
		}
	
		$wp_json_basic_auth_error = true;
	
		return $user->ID;
	}

	public function json_basic_auth_error( $error ) {
		// Passthrough other errors
		if ( ! empty( $error ) ) {
			return $error;
		}
	
		global $wp_json_basic_auth_error;
	
		return $wp_json_basic_auth_error;
	}
}




