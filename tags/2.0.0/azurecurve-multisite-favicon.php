<?php
/*
Plugin Name: azurecurve Multisite Favicon
Plugin URI: http://development.azurecurve.co.uk/plugins/multisite-favicon/

Description: Allows Setting of Separate Favicon For Each Site In A Multisite Installation
Version: 2.0.0

Author: azurecurve
Author URI: http://development.azurecurve.co.uk/

Text Domain: azurecurve-multisite-favicon
Domain Path: /languages

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt
 */

add_action('plugins_loaded', 'azc_msfi_load_plugin_textdomain');

function azc_msfi_load_plugin_textdomain(){
	
	$loaded = load_plugin_textdomain( 'azurecurve-multisite-favicon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	//if ($loaded){ echo 'true'; }else{ echo 'false'; }
}

add_action( 'wp_head', 'azurecurve_msfi_load_favicon' );

function azurecurve_msfi_load_favicon() {
	$options = get_option( 'azc_msfi_options' );
	$network_options = get_site_option( 'azc_msfi_options' );
	
	$icon_url = '';
	if (strlen($options['default_path']) > 0 and strlen($options['default_favicon']) > 0){
		$icon_url = stripslashes($options['default_path']).stripslashes($options['default_favicon']);
	}elseif (strlen($options['default_path']) > 0 and strlen($options['default_favicon']) == 0 and strlen($network_options['default_favicon']) > 0){
		$icon_url = stripslashes($options['default_path']).stripslashes($network_options['default_favicon']);
	}elseif (strlen($options['default_path']) == 0 and strlen($options['default_favicon']) > 0 and strlen($network_options['default_path']) > 0){
		$icon_url = stripslashes($network_options['default_path']).stripslashes($options['default_favicon']);
	}elseif (strlen($options['default_path']) == 0 and strlen($options['default_favicon']) == 0 and strlen($network_options['default_path']) > 0 and strlen($network_options['default_favicon']) > 0){
		$icon_url = stripslashes($network_options['default_path']).stripslashes($network_options['default_favicon']);
	}

	if (strlen($icon_url) > 0){
		echo '<link rel="shortcut icon" href="'.$icon_url.'" />';
	}
	
}
 
register_activation_hook( __FILE__, 'azc_msfi_set_default_options' );

function azc_msfi_set_default_options($networkwide) {
	
	$new_options = array(
				'default_path' => plugin_dir_url(__FILE__).'images/',
				'default_favicon' => ''
			);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			global $wpdb;

			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

				if ( get_option( 'azc_msfi_options' ) === false ) {
					add_option( 'azc_msfi_options', $new_options );
				}
			}

			switch_to_blog( $original_blog_id );
		}else{
			if ( get_option( 'azc_msfi_options' ) === false ) {
				add_option( 'azc_msfi_options', $new_options );
			}
		}
		if ( get_site_option( 'azc_msfi_options' ) === false ) {
			add_site_option( 'azc_msfi_options', $new_options );
		}
	}
	//set defaults for single site
	else{
		if ( get_option( 'azc_msfi_options' ) === false ) {
			add_option( 'azc_msfi_options', $new_options );
		}
	}
}

add_filter('plugin_action_links', 'azc_msfi_plugin_action_links', 10, 2);

function azc_msfi_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=azc-msfi">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

/*
add_action( 'admin_menu', 'azc_msfi_settings_menu' );

function azc_msfi_settings_menu() {
	add_options_page( 'azurecurve Favicon Settings',
	'azurecurve Favicon', 'manage_options',
	'azurecurve-favicon', 'azc_msfi_config_page' );
}
*/

function azc_msfi_settings() {
	if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'azurecurve-multisite-favicon'));
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option( 'azc_msfi_options' );
	?>
	<div id="azc-msfi-general" class="wrap">
		<fieldset>
			<h2><?php _e('azurecurve Favicon Configuration', 'azc-msfi'); ?></h2>
			<?php if(isset($_GET['settings-updated'])) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Site settings have been saved.') ?></strong></p>
				</div>
			<?php } ?>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_azc_msfi_options" />
				<input name="page_options" type="hidden" value="default_path, default_favicon" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azc_msfi_nonce', 'azc_msfi_nonce' ); ?>
				<table class="form-table">
				<tr><td colspan=2>
					<p><?php _e('Set the path for where you will be storing the favicon; default is to the plugin/images folder.', 'azc-msfi'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php _e('Path', 'azc-msfi'); ?></label></th><td>
					<input type="text" name="default_path" value="<?php echo esc_html( stripslashes($options['default_path']) ); ?>" class="large-text" />
					<p class="description"><?php _e('Set folder for favicon', 'azc-msfi'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php _e('Favicon', 'azc-msfi'); ?></label></th><td>
					<input type="text" name="default_favicon" value="<?php echo esc_html( stripslashes($options['default_favicon']) ); ?>" class="regular-text" />
					<p class="description"><?php _e('Set favicon name', 'azc-msfi'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
<?php }

add_action( 'admin_init', 'azc_msfi_admin_init' );

function azc_msfi_admin_init() {
	add_action( 'admin_post_save_azc_msfi_options', 'process_azc_msfi_options' );
}

function process_azc_msfi_options() {
	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) ){
		wp_die( 'Not allowed' );
	}
	// Check that nonce field created in configuration form is present
	if ( ! empty( $_POST ) && check_admin_referer( 'azc_msfi_nonce', 'azc_msfi_nonce' ) ) {
		// Retrieve original plugin options array
		$options = get_option( 'azc_msfi_options' );
		
		$option_name = 'default_path';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = ($_POST[$option_name]);
		}
		
		$option_name = 'default_favicon';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = ($_POST[$option_name]);
		}
		
		// Store updated options array to database
		update_option( 'azc_msfi_options', $options );
		
		// Redirect the page to the configuration form that was processed
		wp_redirect( add_query_arg( 'page', 'azc-msfi&settings-updated', admin_url( 'admin.php' ) ) );
		exit;
	}
}

add_action('network_admin_menu', 'add_azc_msfi_network_settings_page');

function add_azc_msfi_network_settings_page() {
	if (function_exists('is_multisite') && is_multisite()) {
		add_submenu_page(
			'settings.php',
			'azurecurve Multisite Favicon Settings',
			'azurecurve Multisite Favicon',
			'manage_network_options',
			'azurecurve-multisite-favicon',
			'azc_msfi_network_settings_page'
			);
	}
}

function azc_msfi_network_settings_page(){
	$options = get_site_option('azc_msfi_options');

	?>
	<div id="azc-msfi-general" class="wrap">
		<fieldset>
			<h2><?php _e('azurecurve Multisite Favicon Configuration', 'azc-msfi'); ?></h2>
			<form action="edit.php?action=update_azc_msfi_network_options" method="post">
				<input type="hidden" name="action" value="save_azc_msfi_network_options" />
				<input name="page_options" type="hidden" value="default_path, default_favicon" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azc_msfi_nonce', 'azc_msfi_nonce' ); ?>
				<table class="form-table">
				<tr><td colspan=2>
					<p><?php _e('Set the default path for where you will be storing the favicons; default is to the plugin/images folder.', 'azc-msfi'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php _e('Default Path', 'azc-msfi'); ?></label></th><td>
					<input type="text" name="default_path" value="<?php echo esc_html( stripslashes($options['default_path']) ); ?>" class="large-text" />
					<p class="description"><?php _e('Set default folder for favicons', 'azc-msfi'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="width"><?php _e('Default Favicon', 'azc-msfi'); ?></label></th><td>
					<input type="text" name="default_favicon" value="<?php echo esc_html( stripslashes($options['default_favicon']) ); ?>" class="regular-text" />
					<p class="description"><?php _e('Set default favicon used when no img attribute set', 'azc-msfi'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary" />
			</form>
		</fieldset>
	</div>
	<?php
}

add_action('network_admin_edit_update_azc_msfi_network_options', 'process_azc_msfi_network_options');

function process_azc_msfi_network_options(){     
	if(!current_user_can('manage_network_options')) wp_die('FU');
	if ( ! empty( $_POST ) && check_admin_referer( 'azc_msfi_nonce', 'azc_msfi_nonce' ) ) {
		// Retrieve original plugin options array
		$options = get_site_option( 'azc_msfi_options' );

		$option_name = 'default_path';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = ($_POST[$option_name]);
		}

		$option_name = 'default_favicon';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = ($_POST[$option_name]);
		}
		
		update_site_option( 'azc_msfi_options', $options );

		wp_redirect(network_admin_url('settings.php?page=azurecurve-multisite-favicon'));
		exit;  
	}
}


// azurecurve menu
if (!function_exists(azc_create_plugin_menu)){
	function azc_create_plugin_menu() {
		global $admin_page_hooks;
		
		if ( empty ( $admin_page_hooks['azc-menu-test'] ) ){
			add_menu_page( "azurecurve Plugins"
							,"azurecurve"
							,'manage_options'
							,"azc-plugin-menus"
							,"azc_plugin_menus"
							,plugins_url( '/images/Favicon-16x16.png', __FILE__ ) );
			add_submenu_page( "azc-plugin-menus"
								,"Plugins"
								,"Plugins"
								,'manage_options'
								,"azc-plugin-menus"
								,"azc_plugin_menus" );
		}
	}
	add_action("admin_menu", "azc_create_plugin_menu");
}

function azc_create_msfi_plugin_menu() {
	global $admin_page_hooks;
    
	add_submenu_page( "azc-plugin-menus"
						,"Multisite Favicon"
						,"Multisite Favicon"
						,'manage_options'
						,"azc-msfi"
						,"azc_msfi_settings" );
}
add_action("admin_menu", "azc_create_msfi_plugin_menu");

if (!function_exists(azc_plugin_index_load_css)){
	function azc_plugin_index_load_css(){
		wp_enqueue_style( 'azurecurve_plugin_index', plugins_url( 'pluginstyle.css', __FILE__ ) );
	}
	add_action('admin_head', 'azc_plugin_index_load_css');
}

if (!function_exists(azc_plugin_menus)){
	function azc_plugin_menus() {
		echo "<h3>azurecurve Plugins";
		
		echo "<div style='display: block;'><h4>Active</h4>";
		echo "<span class='azc_plugin_index'>";
		if ( is_plugin_active( 'azurecurve-bbcode/azurecurve-bbcode.php' ) ) {
			echo "<a href='admin.php?page=azc-bbcode' class='azc_plugin_index'>BBCode</a>";
		}
		if ( is_plugin_active( 'azurecurve-comment-validator/azurecurve-comment-validator.php' ) ) {
			echo "<a href='admin.php?page=azc-cv' class='azc_plugin_index'>Comment Validator</a>";
		}
		if ( is_plugin_active( 'azurecurve-conditional-links/azurecurve-conditional-links.php' ) ) {
			echo "<a href='admin.php?page=azc-cl' class='azc_plugin_index'>Conditional Links</a>";
		}
		if ( is_plugin_active( 'azurecurve-display-after-post-content/azurecurve-display-after-post-content.php' ) ) {
			echo "<a href='admin.php?page=azc-dapc' class='azc_plugin_index'>Display After Post Content</a>";
		}
		if ( is_plugin_active( 'azurecurve-filtered-categories/azurecurve-filtered-categories.php' ) ) {
			echo "<a href='admin.php?page=azc-fc' class='azc_plugin_index'>Filtered Categories</a>";
		}
		if ( is_plugin_active( 'azurecurve-flags/azurecurve-flags.php' ) ) {
			echo "<a href='admin.php?page=azc-f' class='azc_plugin_index'>Flags</a>";
		}
		if ( is_plugin_active( 'azurecurve-floating-featured-image/azurecurve-floating-featured-image.php' ) ) {
			echo "<a href='admin.php?page=azc-ffi' class='azc_plugin_index'>Floating Featured Image</a>";
		}
		if ( is_plugin_active( 'azurecurve-get-plugin-info/azurecurve-get-plugin-info.php' ) ) {
			echo "<a href='admin.php?page=azc-gpi' class='azc_plugin_index'>Get Plugin Info</a>";
		}
		if ( is_plugin_active( 'azurecurve-insult-generator/azurecurve-insult-generator.php' ) ) {
			echo "<a href='admin.php?page=azc-ig' class='azc_plugin_index'>Insult Generator</a>";
		}
		if ( is_plugin_active( 'azurecurve-mobile-detection/azurecurve-mobile-detection.php' ) ) {
			echo "<a href='admin.php?page=azc-md' class='azc_plugin_index'>Mobile Detection</a>";
		}
		if ( is_plugin_active( 'azurecurve-multisite-favicon/azurecurve-multisite-favicon.php' ) ) {
			echo "<a href='admin.php?page=azc-msf' class='azc_plugin_index'>Multisite Favicon</a>";
		}
		if ( is_plugin_active( 'azurecurve-page-index/azurecurve-page-index.php' ) ) {
			echo "<a href='admin.php?page=azc-pi' class='azc_plugin_index'>Page Index</a>";
		}
		if ( is_plugin_active( 'azurecurve-posts-archive/azurecurve-posts-archive.php' ) ) {
			echo "<a href='admin.php?page=azc-pa' class='azc_plugin_index'>Posts Archive</a>";
		}
		if ( is_plugin_active( 'azurecurve-rss-feed/azurecurve-rss-feed.php' ) ) {
			echo "<a href='admin.php?page=azc-rssf' class='azc_plugin_index'>RSS Feed</a>";
		}
		if ( is_plugin_active( 'azurecurve-rss-suffix/azurecurve-rss-suffix.php' ) ) {
			echo "<a href='admin.php?page=azc-rsss' class='azc_plugin_index'>RSS Suffix</a>";
		}
		if ( is_plugin_active( 'azurecurve-series-index/azurecurve-series-index.php' ) ) {
			echo "<a href='admin.php?page=azc-si' class='azc_plugin_index'>Series Index</a>";
		}
		if ( is_plugin_active( 'azurecurve-shortcodes-in-comments/azurecurve-shortcodes-in-comments.php' ) ) {
			echo "<a href='admin.php?page=azc-sic' class='azc_plugin_index'>Shortcodes in Comments</a>";
		}
		if ( is_plugin_active( 'azurecurve-shortcodes-in-widgets/azurecurve-shortcodes-in-widgets.php' ) ) {
			echo "<a href='admin.php?page=azc-siw' class='azc_plugin_index'>Shortcodes in Widgets</a>";
		}
		if ( is_plugin_active( 'azurecurve-tag-cloud/azurecurve-tag-cloud.php' ) ) {
			echo "<a href='admin.php?page=azc-tc' class='azc_plugin_index'>Tag Cloud</a>";
		}
		if ( is_plugin_active( 'azurecurve-taxonomy-index/azurecurve-taxonomy-index.php' ) ) {
			echo "<a href='admin.php?page=azc-ti' class='azc_plugin_index'>Taxonomy Index</a>";
		}
		if ( is_plugin_active( 'azurecurve-theme-switcher/azurecurve-theme-switcher.php' ) ) {
			echo "<a href='admin.php?page=azc-ts' class='azc_plugin_index'>Theme Switcher</a>";
		}
		if ( is_plugin_active( 'azurecurve-timelines/azurecurve-timelines.php' ) ) {
			echo "<a href='admin.php?page=azc-t' class='azc_plugin_index'>Timelines</a>";
		}
		if ( is_plugin_active( 'azurecurve-toggle-showhide/azurecurve-toggle-showhide.php' ) ) {
			echo "<a href='admin.php?page=azc-tsh' class='azc_plugin_index'>Toggle Show/Hide</a>";
		}
		echo "</span></div>";
		echo "<p style='clear: both' />";
		
		echo "<div style='display: block;'><h4>Other Available Plugins</h4>";
		echo "<span class='azc_plugin_index'>";
		if ( !is_plugin_active( 'azurecurve-bbcode/azurecurve-bbcode.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-bbcode/' class='azc_plugin_index'>BBCode</a>";
		}
		if ( !is_plugin_active( 'azurecurve-comment-validator/azurecurve-comment-validator.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-comment-validator/' class='azc_plugin_index'>Comment Validator</a>";
		}
		if ( !is_plugin_active( 'azurecurve-conditional-links/azurecurve-conditional-links.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-conditional-links/' class='azc_plugin_index'>Conditional Links</a>";
		}
		if ( !is_plugin_active( 'azurecurve-display-after-post-content/azurecurve-display-after-post-content.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-display-after-post-content/' class='azc_plugin_index'>Display After Post Content</a>";
		}
		if ( !is_plugin_active( 'azurecurve-filtered-categories/azurecurve-filtered-categories.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-filtered-categories/' class='azc_plugin_index'>Filtered Categories</a>";
		}
		if ( !is_plugin_active( 'azurecurve-flags/azurecurve-flags.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-flags/' class='azc_plugin_index'>Flags</a>";
		}
		if ( !is_plugin_active( 'azurecurve-floating-featured-image/azurecurve-floating-featured-image.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-floating-featured-image/' class='azc_plugin_index'>Floating Featured Image</a>";
		}
		if ( !is_plugin_active( 'azurecurve-get-plugin-info/azurecurve-get-plugin-info.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-get-plugin-info/' class='azc_plugin_index'>Get Plugin Info</a>";
		}
		if ( !is_plugin_active( 'azurecurve-insult-generator/azurecurve-insult-generator.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-insult-generator/' class='azc_plugin_index'>Insult Generator</a>";
		}
		if ( !is_plugin_active( 'azurecurve-mobile-detection/azurecurve-mobile-detection.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-mobile-detection/' class='azc_plugin_index'>Mobile Detection</a>";
		}
		if ( !is_plugin_active( 'azurecurve-multisite-favicon/azurecurve-multisite-favicon.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-multisite-favicon/' class='azc_plugin_index'>Multisite Favicon</a>";
		}
		if ( !is_plugin_active( 'azurecurve-page-index/azurecurve-page-index.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-page-index/' class='azc_plugin_index'>Page Index</a>";
		}
		if ( !is_plugin_active( 'azurecurve-posts-archive/azurecurve-posts-archive.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-posts-archive/' class='azc_plugin_index'>Posts Archive</a>";
		}
		if ( !is_plugin_active( 'azurecurve-rss-feed/azurecurve-rss-feed.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-rss-feed/' class='azc_plugin_index'>RSS Feed</a>";
		}
		if ( !is_plugin_active( 'azurecurve-rss-suffix/azurecurve-rss-suffix.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-rss-suffix/' class='azc_plugin_index'>RSS Suffix</a>";
		}
		if ( !is_plugin_active( 'azurecurve-series-index/azurecurve-series-index.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-series-index/' class='azc_plugin_index'>Series Index</a>";
		}
		if ( !is_plugin_active( 'azurecurve-shortcodes-in-comments/azurecurve-shortcodes-in-comments.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-shortcodes-in-comments/' class='azc_plugin_index'>Shortcodes in Comments</a>";
		}
		if ( !is_plugin_active( 'azurecurve-shortcodes-in-widgets/azurecurve-shortcodes-in-widgets.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-shortcodes-in-widgets/' class='azc_plugin_index'>Shortcodes in Widgets</a>";
		}
		if ( !is_plugin_active( 'azurecurve-tag-cloud/azurecurve-tag-cloud.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-tag-cloud/' class='azc_plugin_index'>Tag Cloud</a>";
		}
		if ( !is_plugin_active( 'azurecurve-taxonomy-index/azurecurve-taxonomy-index.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-taxonomy-index/' class='azc_plugin_index'>Taxonomy Index</a>";
		}
		if ( !is_plugin_active( 'azurecurve-theme-switcher/azurecurve-theme-switcher.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-theme-switcher/' class='azc_plugin_index'>Theme Switcher</a>";
		}
		if ( !is_plugin_active( 'azurecurve-timelines/azurecurve-timelines.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-timelines/' class='azc_plugin_index'>Timelines</a>";
		}
		if ( !is_plugin_active( 'azurecurve-toggle-showhide/azurecurve-toggle-showhide.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-toggle-showhide/' class='azc_plugin_index'>Toggle Show/Hide</a>";
		}
		echo "</span></div>";
	}
}

?>