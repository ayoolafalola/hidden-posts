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

			//	add role based on another capacity
			//add_role( self::$_role, __( "Hidden Contributor" ), array('read' => true ));
			$rolesX = (array) get_role( 'contributor' )->capabilities;
			$rolesX['publish_posts'] = true;
			$rolesX['edit_published_posts'] = true;
			$rolesX['delete_published_posts'] = true;
			add_role( self::$_role, __( "Special Contributor 1" ), $rolesX );
			
			//	Add category
			$term_id = get_option( 'custom-plugin-category' );

			// Check if the saved page id exists.
			if( empty( $term_id ) ) {

				$term = wp_insert_term( 'Sports Betting', 'category', array(
					'description' => 'Dedicated category for this category so as to hide it easily',
					'slug'        => 'sport-betting'
				  )
			  );
  
			  add_option( 'custom-plugin-category', $term['term_id'] );
  
			}


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

			$term_id = get_option( 'custom-plugin-category' );

			// Check if the saved page id exists.
			if ( $term_id ) {
				//	don't delete term again so we don't change ids
				//wp_delete_term( $term_id, 'category' );
				//delete_option( 'custom-plugin-category' );
			}


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

		
		
		//	hide post in list
		add_filter( 'pre_get_posts', function($query) {

			$removeFromList = function() use ( $query )
			{
				//	exclude custom category from post list in home page
				$category = get_option( 'custom-plugin-category' );
				$query->set( 'cat', '-' . $category  );

			};
			
			if ( $query->is_home() ) {
				$removeFromList();
			}

			elseif( $query->is_archive() && $query->is_main_query() )
			{
				$removeFromList();
			}
			return $query;
		});


		
		add_action('save_post', function($post_ID){

			$user = wp_get_current_user();
			$roles = ( array ) $user->roles;

			//	do this for specified role
			if( ! in_array( self::$_role, $roles ) )
			{
				return;
			}

			if(wp_is_post_autosave($post_ID) || wp_is_post_revision($post_ID)) {
				//return;
			} //	If this is just an autosave or post revision, don't do anything
		
			$postFormat = get_post_format( $post_ID ); //Get the post format of current post
		
			if( !empty( $postFormat ) ) {
				return;
			} //If post is not a standard format, don't do anything
		
			$currentCat = get_the_category(); //Get the current set Category
			//$defaultCat = get_cat_ID( "test" ); //Get ID of "test" category
		
			//if( empty( $currentCat ) ) 
			{ 
				//Check if category is set
				wp_set_post_categories( $post_ID, get_option( 'custom-plugin-category' ) );  //Set the current post ID's Category to "test"
			}
		});

		add_action( 'wp_head', function()	{
			if(is_single()){
				global $post;
				if( in_category( get_option( 'custom-plugin-category' ), $post ) )
				{
					//	block on Google news - https://support.google.com/news/publisher-center/answer/9605477
					echo '<meta name="Googlebot-News" content="noindex, nofollow">' . "\r\n";
				}
			}
		});

		$caps = array(
			'apple_news_publish_capability',
			'apple_news_settings_capability',
		);

		foreach( $caps as $cap )
		{
			add_filter( $cap, function( $capabilities ) {

				$user = wp_get_current_user();
				$roles = ( array ) $user->roles;
				
				//	do this for specified role
				if( in_array( self::$_role, $roles ) )
				{
					return false;
				}
				return $capabilities;
	
			});
		}


			//	turn off capabilities
			add_action( 'admin_enqueue_scripts', function()	{

				$metasToRemove = array(
					'apple_news_publish' => 'side',
					'categorydiv' => 'side',
					'tagsdiv-post_tag' => 'side',
					'tm-scheduler' => 'normal',
					'wp-convertkit-meta-box' => 'normal',
					'wppd-disclaimer' => 'normal'
				);
		
				foreach( $metasToRemove as $meta => $context )
				{

					$user = wp_get_current_user();
					$roles = ( array ) $user->roles;

					//	do this for specified role
					if( in_array( self::$_role, $roles ) )
					{
						//error_reporting( -1 );
						//ini_set( 'display_errors', 1 );
			
						remove_meta_box( $meta, 'post', $context );
					}	
				}				
		});

		add_filter( 'wppd_disclaimer_content_raw', function( $content ) {

			$post_author = get_post();
			$user = get_userdata( $post_author->post_author );

			$roles = (array) $user->roles;

			//	testing only
			//$user = wp_get_current_user();
			//$roles = ( array ) $user->roles;

			
			//	do this for specified role
			if( in_array( self::$_role, $roles ) )
			{
				$content = 'This Article is by a Third Party Author and has not been reviewed by Influencive. Opinions expressed here are opinions of the Author. Influencive does not endorse or review anything mentioned; does not and cannot investigate relationships with people or companies mentioned, and is up to the Author to make any required discloses. Accounts and articles may be professional fee-based. The Content is for informational purposes only, you should not construe any such information or other material as legal, tax, investment, financial, or other advice.';
			}

			return $content;
		});

			

		//error_reporting(-1);
		//ini_set('display_errors', 1);
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
