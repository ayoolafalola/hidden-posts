<?php
/**
 * Plugin Name: Ayoola's Customized Plugin for Hiding Posts
 * Plugin URI: https://digital.comeriver.com/
 * Description: Adds a functionality to hide posts of a particular user
 * Version: 1.0.5
 *
 * Author: Ayoola Falola
 * Author URI: https://ayoo.la/
 *
 * Text Domain: hidden-posts
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 4.2
 * Tested up to: 4.9
 *
 * Copyright: © 2009-2017 Ayoola Falola.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 *
 * @class Ayoola_HidePosts
 */
class Ayoola_HidePosts {

	protected static $_role = 'hidden-contributor';

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {


		register_activation_hook(__FILE__, function()
		{
			// Do we need to create any page?
			$saved_page_args = array(
				'post_title'   => __( 'Custom Plugin Page', 'custom-plugin-page' ),
				'post_name' => 'custom-plugin',
				'post_content' => '[custom-plugin]',
				'post_status'  => 'publish',
				'post_type'    => 'page'
			);
			// Insert the page and get its id.
			$saved_page_id = wp_insert_post( $saved_page_args );

			// Save page id to the database.
			//	So it will be easy to delete on uninstallation
			add_option( 'custom-plugin-page', $saved_page_id );

			//	add role
			add_role( self::$_role, __( "Hidden Contributor" ), array('read' => true));			

		});

		register_deactivation_hook(__FILE__, function()
		{
			// Get Saved page id.
			$saved_page_id = get_option( 'custom-plugin-page' );

			// Check if the saved page id exists.
			if ( $saved_page_id ) {

				// Delete saved page.
				wp_delete_post( $saved_page_id, true );

				// Delete saved page id record in the database.
				delete_option( 'custom-plugin-page' );

			}

			//	Remove Role
			remove_role( self::$_role );

		});

		// function that runs when shortcode is called
		function custom_plugin_shortcode() { 
			

			// Output needs to be return
		}
		// register shortcode
		add_shortcode('custom-plugin', 'custom_plugin_shortcode');


		// Do we need any web hook?
		// add_action( 'rest_api_init', function () {
		// 	register_rest_route( 'custom-plugin/v1', '/webhook', array(
		// 	  'methods' => WP_REST_Server::ALLMETHODS,
		// 	  'callback' => array( __CLASS__, 'custom_webhook' ),   
		// 	) );
		//   } 
		// );

		function add_custom_script_to_wp_head() {
			include 'head.php';
		}

		add_action( 'wp_head', 'add_custom_script_to_wp_head' );

		// Make a certain page available only to customrole users
		function shortcode_restricted_page( $roles ) {
			$current_user = wp_get_current_user();    
			$current_username = $current_user->user_login;
			$role = $current_user->roles[0];

			$roles[] = 'administrator';
			if ( in_array( $role, $roles ) ) {

				// Access granted to the page
				return;

			}
			else {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				get_template_part( 404 ); exit();
			} 
		}
		add_shortcode( 'custom_restricted_page', function()
		{
			shortcode_restricted_page( array( self::$_role ) );
		});

		error_reporting(-1);
		ini_set('display_errors', 1);
	}

	public static function changeUseRole ( $user_id, $newRole )
	{
		if ( $user_id > 0 ) {

			$user = new WP_User( $user_id );
			$roles = ( array ) $user->roles;

			// Remove previous role
			foreach( array_values( $roles ) as $role )
			{
				if( $role === 'administrator' )
				{
					//	do not change admin role
					return false;
				}

				if( $role )
				{
					$user->remove_role( $role );
				}
			}

			// Add role
			$user->add_role( $newRole );

			return true;
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

}

Ayoola_HidePosts::init();
