<?php
/**
 * Plugin Name: Vibrant Life Roles
 * Plugin URI: https://github.com/VLSLgithub/vibrant-life-roles
 * Description: Holds Roles for Vibrant Life
 * Version: 0.1.0
 * Text Domain: vibrant-life-roles
 * Author: Eric Defore
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 * GitHub Plugin URI: VLSLgithub/vibrant-life-roles
 * GitHub Branch: master
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Vibrant_Life_Roles' ) ) {

	/**
	 * Main Vibrant_Life_Roles class
	 *
	 * @since	  {{VERSION}}
	 */
	final class Vibrant_Life_Roles {
		
		/**
		 * @var			array $plugin_data Holds Plugin Header Info
		 * @since		{{VERSION}}
		 */
		public $plugin_data;
		
		/**
		 * @var			array $admin_errors Stores all our Admin Errors to fire at once
		 * @since		{{VERSION}}
		 */
		private $admin_errors;

		/**
		 * @var			string $current_role The current user's role
		 * @since		{{VERSION}}
		 */
		public $current_role = false;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  {{VERSION}}
		 * @return	  object self::$instance The one true Vibrant_Life_Roles
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %sWordPress%s or higher to be installed!', 'First string is the plugin name, followed by the required WordPress version and then the anchor tag for a link to the Update screen.', 'vibrant-life-roles' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>', '</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}

			// Store the current Role in our Object
			add_action( 'init', array( $this, 'get_current_role' ) );
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );

			// Sometimes you cannot remove Menu Items by Capability correctly due to them requiring a hyper generic Cap with no way of changing it
			add_action( 'admin_menu', array( $this, 'remove_menu_items' ), 999 );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'Vibrant_Life_Roles_VER' ) ) {
				// Plugin version
				define( 'Vibrant_Life_Roles_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'Vibrant_Life_Roles_DIR' ) ) {
				// Plugin path
				define( 'Vibrant_Life_Roles_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'Vibrant_Life_Roles_URL' ) ) {
				// Plugin URL
				define( 'Vibrant_Life_Roles_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'Vibrant_Life_Roles_FILE' ) ) {
				// Plugin File
				define( 'Vibrant_Life_Roles_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = Vibrant_Life_Roles_DIR . '/languages/';
			$lang_dir = apply_filters( 'vibrant_life_roles_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'vibrant-life-roles' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'vibrant-life-roles', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/vibrant-life-roles/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/vibrant-life-roles/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'vibrant-life-roles', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/vibrant-life-roles/languages/ folder
				load_textdomain( 'vibrant-life-roles', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'vibrant-life-roles', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		private function require_necessities() {
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  {{VERSION}}
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  {{VERSION}}
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'vibrant-life-roles',
				Vibrant_Life_Roles_URL . 'dist/assets/css/app.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Vibrant_Life_Roles_VER
			);
			
			wp_register_script(
				'vibrant-life-roles',
				Vibrant_Life_Roles_URL . 'dist/assets/js/app.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Vibrant_Life_Roles_VER,
				true
			);
			
			wp_localize_script( 
				'vibrant-life-roles',
				'vibrantLifeRoles',
				apply_filters( 'vibrant_life_roles_localize_script', array() )
			);
			
			wp_register_style(
				'vibrant-life-roles-admin',
				Vibrant_Life_Roles_URL . 'dist/assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Vibrant_Life_Roles_VER
			);
			
			wp_register_script(
				'vibrant-life-roles-admin',
				Vibrant_Life_Roles_URL . 'dist/assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : Vibrant_Life_Roles_VER,
				true
			);
			
			wp_localize_script( 
				'vibrant-life-roles-admin',
				'vibrantLifeRoles',
				apply_filters( 'vibrant_life_roles_localize_admin_script', array() )
			);
			
		}
		
		/**
		 * Add Roles
		 * 
		 * @access		public
		 * @since		{{VERSION}}
		 * @return		void
		 */
		public static function activate() {
			
			$author = get_role( 'author' );
			
			$pv_author = add_role( 'pv_author', __( 'PV Author', 'vibrant-life-roles' ), array( 'read' => true ) );
			
			foreach ( $author->capabilities as $capability => $bool ) {
				
				if ( strpos( $capability, 'publish_' ) === 0 ) continue; // No publishing
				
				$pv_author->add_cap( $capability );
				
			}
			
			$pv_author->add_cap( 'delete_pages' );
			$pv_author->add_cap( 'delete_published_pages' );
			$pv_author->add_cap( 'edit_pages' );
			$pv_author->add_cap( 'edit_published_pages' );
			// $pv_author->add_cap( 'publish_pages' ); // No publishing

			$roles = Vibrant_Life_Roles::get_roles_to_adjust();
			$caps = Vibrant_Life_Roles::extra_capabilities();

			foreach ( $roles as $role ) {

				$role = get_role( $role );

				foreach ( $caps as $cap ) {
					$role->add_cap( $cap );
				}

			}
			
		}
		
		/**
		 * Remove Roles
		 * 
		 * @access		public
		 * @since		{{VERSION}}
		 * @return		void
		 */
		public static function deactivate() {

			$roles = Vibrant_Life_Roles::get_roles_to_adjust();
			$caps = Vibrant_Life_Roles::extra_capabilities();

			foreach ( $roles as $role ) {

				$role = get_role( $role );

				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}

			}
			
			remove_role( 'pv_author' );
			
		}

		/**
		 * Gets the current user's role.
		 * 
		 * @access		public
		 * @since		{{VERSION}}
		 * @return		void
		 */
		public function get_current_role() {
			
			if ( is_user_logged_in() ) {
				$current_user       = wp_get_current_user();
				$roles              = $current_user->roles;
				$this->current_role = array_shift( $roles );
			}

			// Staging for some reason always had NULL as the Role. This fixes it.
			// My Local environment worked just fine though, so maybe in most cases this won't be needed
			if ( $this->current_role === NULL ) {

				global $user_ID;

				$user_data = get_userdata( $user_ID );
				$user_role = array_shift( $user_data->roles );
				$this->current_role = $user_role;

			}

		}

		public static function get_roles_to_adjust() {

			return apply_filters( 'vlsl_roles_to_adjust', array( 'editor', 'author', 'pv_author' ) );

		}

		public static function extra_capabilities() {

			$view_calendar = apply_filters( 'ef_view_calendar_cap', 'ef_view_calendar' );
			$view_story_budget = apply_filters( 'ef_view_story_budget_cap', 'ef_view_story_budget' );

			return apply_filters( 'vlsl_roles_extra_caps', array(
				$view_calendar,
				$view_story_budget,
			) );

		}

		public function remove_menu_items() {

			if ( in_array( $this->current_role, array( 'editor', 'author', 'pv_author' ) ) ) {
				$success = remove_menu_page( 'activity_log_page' );
			}

		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true Vibrant_Life_Roles
 * instance to functions everywhere
 *
 * @since	  {{VERSION}}
 * @return	  \Vibrant_Life_Roles The one true Vibrant_Life_Roles
 */
add_action( 'plugins_loaded', 'vibrant_life_roles_load' );
function vibrant_life_roles_load() {

	require_once __DIR__ . '/core/vibrant-life-roles-functions.php';
	VIBRANTLIFEROLES();

}

register_activation_hook( __FILE__, array( 'Vibrant_Life_Roles', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Vibrant_Life_Roles', 'deactivate' ) );