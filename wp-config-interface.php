<?php
/**
 * Plugin Name: WP Config Interface
 * Version: 1.0
 * Author: Ehsaan
 * Author URI: http://iehsan.ir/
 * Description: Edit your WordPress base configuration easily with style ;)
 * Domain Path: languages
 * Text Domain: wpcfg
 *
 * WP Config Interface
 * An interface to edit wp-config.php file with ease._
 *
 * A Plugin by [Ehsaan](http://iehsan.ir)
 * Thanks to [WP-Parsi Group](http://wp-parsi.com)
 *
 * @author 				Ehsaan
 * @license 			GPL v2.0 or later
 * @version 			1.0
 */

/**
 * Setup constants,
 * They will be needed ;)
 *
 * @return 				void
 */
$func_setup_constants = function() {

	if ( ! defined( 'CONFIG_EDITOR_VERSION' ) )
		define( 'CONFIG_VERSION', '1.0' );

	if ( ! defined( 'CONFIG_EDITOR_DIR' ) )
		define( 'CONFIG_EDITOR_DIR', plugin_dir_path( __FILE__ ) );

};
$func_setup_constants();

/**
 * Load plugin textdomain
 *
 * @return 				void
 */
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( 'wpcfg', false, CONFIG_EDITOR_DIR . '/languages/' );
} );

/**
 * Add the menu page under "Settings" menu.
 *
 * @return 				void
 */
add_action( 'admin_menu', function() {
	add_options_page(
		__( 'Edit wp-config.php', 'wpcfg' ),
		__( 'Edit wp-config.php', 'wpcfg' ),
		'manage_options',
		'edit_wp_config.php',
		'wpcfg_menu_item'
	);
} );

/**
 * Menu item render
 *
 * @return 				void
 */
function wpcfg_menu_item() {
	echo '<div class="wrap">';
	echo '<h2>' . __( 'Edit WordPress basic configuration', 'wpcfg' ) . '</h2>';
	if ( wpcfg_config_writable() )
		echo '<p>' . __( 'We always make a back-up of wp-config.php before applying your settings, it\'ll available in your WordPress directory, called wp-config.backup.php', 'wpcfg' ) . '</p>';
	else
		echo '<p>' . __( '<strong>Attention:</strong> Because your wp-config.php is not writable, I\'ll give your configuration source. You have to apply it by yourself.', 'wpcfg' ) . '</p>';
	
	if ( get_transient( 'wpcfg_config_updated' ) == true ) {
		?><div class="updated notice is-dismissible"><p><?php _e( 'wp-config.php Updated.', 'wpcfg' ); ?> &ndash; <a href="<?php echo add_query_arg( array( 'wpcfg_updated' => false, 'restore_backup' => true, '_wpnonce' => wp_create_nonce( 'wpcfg_restore_backup' ) ) ); ?>"><?php _e( 'Restore backup', 'wpcfg' ); ?></a></p></div><?php
	}
	if ( get_transient( 'wpcfg_restore_backup' ) == true ) {
		?><div class="updated notice is-dismissible"><p><?php _e( 'Backup restored successfully', 'wpcfg' ); ?></p></div><?php
	}
	wpcfg_render_tabs();
	?>
	<form method="post">
		<input type="hidden" name="action" value="update_wp-config">
		<?php wp_nonce_field( 'update_wp-config' ); ?>
		<table class="form-table">
	<?php
	wpcfg_render_options();
	?>
		</table>
		<?php if ( wpcfg_config_writable() ) { ?>
			<?php submit_button( __( 'Write configurations to wp-config.php', 'wpcfg' ) ); ?>
		<?php } else { ?>
			<?php submit_button( __( 'Generate wp-config.php', 'wpcfg' ) ); ?>
		<?php } ?>
	</form>
	<?php
	echo '</div>';
}

/**
 * Render tabs for UI
 *
 * @return 				void
 */
function wpcfg_render_tabs() {
	$tabs = array(
		'general' 		=>	'<span class="dashicons dashicons-admin-settings"></span> ' . __( 'General', 'wpcfg' ),
		'performance' 	=>	'<span class="dashicons dashicons-performance"></span> ' . __( 'Performance', 'wpcfg' ),
		'database' 		=>	'<span class="dashicons dashicons-index-card"></span> ' . __( 'Database', 'wpcfg' ),
		'security' 		=>	'<span class="dashicons dashicons-lock"></span> ' . __( 'Security', 'wpcfg' ),
		'posts' 		=>	'<span class="dashicons dashicons-format-aside"></span> ' . __( 'Posts', 'wpcfg' ),
		'misc' 			=>	'<span class="dashicons dashicons-admin-generic"></span> ' . __( 'Misc', 'wpcfg' )
	);

	$current_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'general' );
	echo '<h2 class="nav-tab-wrapper">';

	foreach( $tabs as $key => $name ) {
		$active = $current_tab == $key ? ' nav-tab-active' : '';
		$url = add_query_arg( array(
			'wpcfg_updated'	=>	false,
			'tab' 			=>	$key
		) );

		echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . $active . '">' . $name . '</a>';
	}

	echo '</h2>';
}

/**
 * Render settings
 *
 * @return 			void
 */
function wpcfg_render_options() {
	$options = apply_filters( 'wpcfg_options_before_render', wpcfg_get_options() );
	$tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'general' );
	echo '<input type="hidden" name="tab" value="' . $tab . '">';

	// Fire up the render engine...
	require CONFIG_EDITOR_DIR . '/includes/render-options.php';

	$engine = new WPCFG_Render_Engine( $options, $tab );
	$engine->render(); 
}

/**
 * Get all options available options for editing
 *
 * @return 			array
 */
function wpcfg_get_options() {
	global $wpdb;

	return apply_filters( 'wpcfg_get_options', array(
		'general' 				=>	array(
			'debugging_hr' 		=>	array(
				'name' 			=>	__( 'Debugging', 'wpcfg' ),
				'input' 		=>	'hr'
			),
			'wp_debug' 			=>	array(
				'name' 			=>	__( 'Enable debugging', 'wpcfg' ),
				'desc' 			=>	__( 'When debugging is enabled, all errors, warnings and notices won\'t missed. Not recommended on a production site.', 'wpcfg' ),
				'const' 		=>	'WP_DEBUG',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	WP_DEBUG,
				'id' 			=>	'wp_debug',
				'default' 		=>	false
			),
			'wp_debug_log' 		=>	array(
				'name' 			=>	__( 'Enable errors log', 'wpcfg' ),
				'desc' 			=>	__( 'When it is enabled, all errors and warnings will be recorded to log file. It needs Debugging to be enabled.', 'wpcfg' ),
				'const' 		=>	'WP_DEBUG_LOG',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	WP_DEBUG_LOG,
				'id' 			=>	'wp_debug_log',
				'default' 		=>	false
			),
			'wp_debug_display' 	=>	array(
				'name' 			=>	__( 'Display errors, warnings, etc.', 'wpcfg' ),
				'desc' 			=>	__( 'If this option disabled, errors won\'t displayed. It\'s enabled by default if WP_DEBUG is enabled.', 'wpcfg' ),
				'const' 		=>	'WP_DEBUG_DISPLAY',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'WP_DEBUG_DISPLAY', WP_DEBUG ),
				'id' 			=>	'wp_debug_display',
				'default' 		=>	WP_DEBUG
			),
			'script_debug' 		=>	array(
				'name' 			=>	__( 'Enable scripts debugging', 'wpcfg' ),
				'desc' 			=>	__( 'If you are planning on modifying some of WordPress\' built-in JavaScript or Cascading Style Sheets, you may need to enable this option.', 'wpcfg' ),
				'const' 		=>	'SCRIPT_DEBUG',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	SCRIPT_DEBUG,
				'id' 			=>	'script_debug',
				'default' 		=>	false
			),
			//----------------------------------------------------------------------------------------
			'updates_hr' 		=>	array(
				'name' 			=>	__( 'Automatic updates', 'wpcfg' ),
				'input' 		=>	'hr'
			),
			'disable_auto_engine'=>	array(
				'name' 			=>	__( 'Disable all automatic updates', 'wpcfg' ),
				'desc' 			=>	__( 'If you want to disable every automatic update, check this. <strong>NOT SUGGESTED</strong>', 'wpcfg' ),
				'const' 		=>	'AUTOMATIC_UPDATER_DISABLED',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'AUTOMATIC_UPDATER_DISABLED' ),
				'id' 			=>	'disable_auto_engine',
				'default' 		=>	false
			),
			'update_core' 		=>	array(
				'name' 			=>	__( 'Auto-Update Core', 'wpcfg' ),
				'desc' 			=>	'',
				'const' 		=>	'WP_AUTO_UPDATE_CORE',
				'type' 			=>	'string',
				'input' 		=>	'select',
				'options' 		=>	array( 'true' => 'Major and Minor', 'minor' => 'Only Minor releases', 'false' => 'Deactivate' ),
				'value' 		=>	wpcfg_option( 'WP_AUTO_UPDATE_CORE', 'minor' ),
				'id' 			=>	'update_core',
				'default' 		=>	'minor'
			)
		//===============================================================================================
		),
		'performance' 			=>	array(
			'performance_hr' 	=>	array(
				'name' 			=>	__( 'Performance', 'wpcfg' ),
				'input' 		=>	'hr'
			),
			'wp_limit_memory' 	=>	array(
				'name' 			=>	__( 'Memory limit', 'wpcfg' ),
				'desc' 			=>	__( 'in Megabytes. You can increase/decrease WordPress memory allocate. Caution: It can take down your site.', 'wpcfg' ),
				'const' 		=>	'WP_MEMORY_LIMIT',
				'type' 			=>	'integer',
				'input' 		=>	'number',
				'value' 		=>	wpcfg_get_memory_limit(),
				'id' 			=>	'wp_limit_memory',
				'default' 		=>	( @ constant( 'WP_ALLOW_MULTISITE' ) ) ? 64 : 40
			),
			'wp_max_memory' 	=>	array(
				'name' 			=>	__( 'Maximum memory limit', 'wpcfg' ),
				'desc' 			=>	__( 'in Megabytes. When in the administration area, the memory can be increased or decreased from this.', 'wpcfg' ),
				'const' 		=>	'WP_MAX_MEMORY_LIMIT',
				'type' 			=>	'integer',
				'input' 		=>	'number',
				'value' 		=>	wpcfg_get_max_memory_limit(),
				'id' 			=>	'wp_max_memory',
				'default' 		=>	256
			),
			'block_external_http'=>	array(
				'name' 			=>	__( 'Block external HTTP requests', 'wpcfg' ),
				'desc' 			=>	__( 'This will block any future external HTTP requests to the other sites. If it\'s enabled, WordPress would block all updates, pings and trackbacks. <strong>NOT SUGGESTED</strong>', 'wpcfg' ),
				'const' 		=>	'WP_HTTP_BLOCK_EXTERNAL',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'WP_HTTP_BLOCK_EXTERNAL' ),
				'id' 			=>	'block_external_http',
				'default' 		=>	false
			),
			'image_edit_overwrite'=>	array(
				'name' 			=>	__( 'Cleanup Image Edits', 'wpcfg' ),
				'desc' 			=>	__( 'If this option is enabled, image edits copies won\'t created and will overwrited to original file. <strong>SUGGESTED IF YOU HAVE SPACE LIMIT</strong>', 'wpcfg' ),
				'const' 		=>	'IMAGE_EDIT_OVERWRITE',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'IMAGE_EDIT_OVERWRITE' ),
				'id' 			=>	'image_edit_overwrite',
				'default' 		=>	false
			),
			'compress_css'		=>	array(
				'name' 			=>	__( 'Compress CSS', 'wpcfg' ),
				'desc' 			=>	__( 'WordPress will compress all admin style sheets, if this is enabled', 'wpcfg' ),
				'const' 		=>	'COMPRESS_CSS',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'COMPRESS_CSS' ),
				'id' 			=>	'compress_css',
				'default' 		=>	false	
			),
			'compress_js'		=>	array(
				'name' 			=>	__( 'Compress JS', 'wpcfg' ),
				'desc' 			=>	__( 'WordPress will compress all admin Javascript files, if this is enabled', 'wpcfg' ),
				'const' 		=>	'COMPRESS_JS',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'COMPRESS_CSS' ),
				'id' 			=>	'compress_js',
				'default' 		=>	false	
			),
			'concatenate'		=>	array(
				'name' 			=>	__( 'Concatenate Scripts', 'wpcfg' ),
				'desc' 			=>	__( 'Enabling this would concatenate all your scripts and style sheets in admin side. <strong>DISABLING MAY DECREASE PERFORMANCE.</strong>', 'wpcfg' ),
				'const' 		=>	'CONCATENATE_SCRIPTS',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'CONCATENATE_SCRIPTS', true ),
				'id' 			=>	'concatenate',
				'default' 		=>	true
			),
			'gzip'		=>	array(
				'name' 			=>	__( 'Enforce gZip', 'wpcfg' ),
				'desc' 			=>	__( 'Enforces gZip compression technology to all data will sent to browser.', 'wpcfg' ),
				'const' 		=>	'ENFORCE_GZIP',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'ENFORCE_GZIP' ),
				'id' 			=>	'gzip'	,
				'default' 		=>	false	
			)
		),
		//===============================================================================================
		'database' 				=>	array(
			'db_name' 			=>	array(
				'name' 			=>	__( 'Database name', 'wpcfg' ),
				'desc' 			=>	__( 'Database schema name for WordPress. <strong style="color: red;">WARNING: EDITING MAY TAKE DOWN YOUR SITE AND LOSS YOUR DATA.</strong>', 'wpcfg' ),
				'const' 		=>	'DB_NAME',
				'type' 			=>	'string',
				'input' 		=>	'text',
				'value' 		=>	wpcfg_option( 'DB_NAME', '--empty string--' ),
				'id' 			=>	'db_name',
				'default' 		=>	'--empty string--'
			),
			'db_user'	 		=>	array(
				'name' 			=>	__( 'Database username', 'wpcfg' ),
				'desc' 			=>	__( 'Database authorization username. <strong style="color: red;">WARNING: EDITING MAY TAKE DOWN YOUR SITE AND LOSS YOUR DATA.</strong>', 'wpcfg' ),
				'const' 		=>	'DB_USER',
				'type' 			=>	'string',
				'input' 		=>	'text',
				'value' 		=>	wpcfg_option( 'DB_USER', '--empty string--' ),
				'id' 			=>	'db_user',
				'default' 		=>	'--empty string--'
			),
			'db_charset'	 	=>	array(
				'name' 			=>	__( 'Database charset', 'wpcfg' ),
				'desc' 			=>	__( 'Database charset. <strong style="color: red;">WARNING: EDITING MAY TAKE DOWN YOUR SITE AND CAUSE MAJOR PROBLEMS.</strong>', 'wpcfg' ),
				'const' 		=>	'DB_CHARSET',
				'type' 			=>	'string',
				'input' 		=>	'text',
				'value' 		=>	wpcfg_option( 'DB_CHARSET', 'utf8' ),
				'id' 			=>	'db_charset',
				'default' 		=>	'utf8'
			),
			'db_collation'	 	=>	array(
				'name' 			=>	__( 'Database collation', 'wpcfg' ),
				'desc' 			=>	__( 'Database collation. <strong style="color: red;">WARNING: EDITING MAY TAKE DOWN YOUR SITE AND CAUSE MAJOR PROBLEMS.</strong>', 'wpcfg' ),
				'const' 		=>	'DB_COLLATE',
				'type' 			=>	'string',
				'input' 		=>	'text',
				'value' 		=>	wpcfg_option( 'DB_COLLATE', '' ),
				'id' 			=>	'db_collation',
				'default' 		=>	''
			)
		),
		//===============================================================================================
		'security' 				=>	array(
			'note1'				=>	array(
				'name' 			=>	'',
				'desc' 			=>	__( 'Because of security issues, we can\'t show security salts. If you need to regenerate them, just click "Regenerate Salts" button.', 'wpcfg' ),
				'input' 		=>	'plain'
			),
			'regen_salt' 		=>	array(
				'name' 			=>	__( 'Regenerate Salt', 'wpcfg' ),
				'desc' 			=>	__( 'Regenerating salts will cause invalidation of all cookies and make your users and you to log in again. Salts will be generated with official WordPress API.', 'wpcfg' ),
				'caption' 		=>	__( 'Regenerate Salt', 'wpcfg' ),
				'class' 		=>	'button',
				'href' 			=>	add_query_arg( array( 'wpcfg_updated' => false, 'regen_salt' => true, '_wpnonce' => wp_create_nonce( 'regenerate_salt' ) ) ),
				'input' 		=>	'link'
			),
			'force_ssl_user' 	=>	array(
				'name' 			=>	__( 'Force SSL for users login', 'wpcfg' ),
				'desc' 			=>	__( 'If you\'re planning to give your users a chance to feel the safe, you may want to enable this option.' ),
				'const' 		=>	'FORCE_SSL_LOGIN',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'FORCE_SSL_LOGIN', false ),
				'id' 			=>	'force_ssl_user',
				'default' 		=>	false
			),
			'force_ssl_admin' 	=>	array(
				'name' 			=>	__( 'Force SSL for admin', 'wpcfg' ),
				'desc' 			=>	__( 'If you want your admin environment so secure, enable this option.' ),
				'const' 		=>	'FORCE_SSL_ADMIN',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'FORCE_SSL_ADMIN', false ),
				'id' 			=>	'force_ssl_admin',
				'default' 		=>	false
			),
			'disallow_file_edits' =>	array(
				'name' 			=>	__( 'Disallow file edits', 'wpcfg' ),
				'desc' 			=>	__( 'Occasionally you may wish to disable the plugin or theme editor to prevent overzealous users from being able to edit sensitive files and potentially crash the site.', 'wpcfg' ),
				'const' 		=>	'DISALLOW_FILE_EDIT',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'DISALLOW_FILE_EDIT', false ),
				'id' 			=>	'disallow_file_edits',
				'default' 		=>	false
			)
		),
		//===============================================================================================
		'posts' 				=>	array(
			'empty_trash_days' 	=>	array(
				'name' 			=>	__( 'Empty Trash', 'wpcfg' ),
				'desc' 			=>	__( 'It controls the number of days before WordPress permanently deletes posts, pages, etc. in trash bin.', 'wpcfg' ),
				'const' 		=>	'EMPTY_TRASH_DAYS',
				'type' 			=>	'integer',
				'input' 		=>	'number',
				'value' 		=>	wpcfg_option( 'EMPTY_TRASH_DAYS', 30 ),
				'id' 			=>	'empty_trash_days',
				'default' 		=>	30
			),
			'autosave_interval' =>	array(
				'name' 			=>	__( 'Autosave interval', 'wpcfg' ),
				'desc' 			=>	__( 'in seconds. Delay between each autosave while editing a post.', 'wpcfg' ),
				'const' 		=>	'AUTOSAVE_INTERVAL',
				'type' 			=>	'integer',
				'input' 		=>	'number',
				'value' 		=>	wpcfg_option( 'AUTOSAVE_INTERVAL', 60 ),
				'id' 			=>	'autosave_interval',
				'default' 		=>	60
			)
		),
		//===============================================================================================
		'misc' 					=>	array(
			'allow_multisite' 	=>	array(
				'name' 			=>	__( 'Allow Multisite', 'wpcfg' ),
				'desc' 			=>	__( 'Enable the Multisite feature, which allows you to create multiple sites with one WordPress installation.', 'wpcfg' ),
				'const' 		=>	'WP_ALLOW_MULTISITE',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'WP_ALLOW_MULTISITE', false ),
				'id' 			=>	'allow_multisite',
				'default' 		=>	false
			),
			'disable_wp_cron' 	=>	array(
				'name' 			=>	__( 'Disable Cron', 'wpcfg' ),
				'desc' 			=>	__( 'Disable the whole WordPress cron system. If you disable the cron, "Future" posts will not published, updates will not be automatic and some plugins may got broken. <strong>NOT SUGGESTED</strong>', 'wpcfg' ),
				'const' 		=>	'DISABLE_WP_CRON',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'DISABLE_WP_CRON', false ),
				'id' 			=>	'disable_wp_cron',
				'default' 		=>	false
			),
			'alternate_wp_cron'	=>	array(
				'name' 			=>	__( 'Alternative Cron', 'wpcfg' ),
				'desc' 			=>	__( 'Use this, for example, if scheduled posts are not getting published. <a href="https://codex.wordpress.org/Editing_wp-config.php#Alternative_Cron" target="_blank">More Details »</a>', 'wpcfg' ),
				'const' 		=>	'ALTERNATE_WP_CRON',
				'type' 			=>	'boolean',
				'input' 		=>	'check',
				'value' 		=>	wpcfg_option( 'ALTERNATE_WP_CRON', false ),
				'id' 			=>	'alternate_wp_cron',
				'default' 		=>	false
			)
		)
	) );
}

/**
 * WordPress actual memory limit, in Megabytes, without M postfix.
 *
 * @return 				int
 */
function wpcfg_get_memory_limit() {
	if ( WP_MEMORY_LIMIT == '' || WP_MEMORY_LIMIT == 0 ) {
		// No memory limit, read the defaults
		if ( WP_ALLOW_MULTISITE )
			return 64;

		return 40;
	}

	$memory = str_replace( 'M', '', WP_MEMORY_LIMIT );
	return (int) $memory;
}

/**
 * Same as wpcfg_get_memory_limit(), but it returns max limit.
 *
 * @uses 				wpcfg_get_memory_limit
 * @return 				int
 */
function wpcfg_get_max_memory_limit() {
	if ( WP_MAX_MEMORY_LIMIT == '' || WP_MAX_MEMORY_LIMIT == 0 ) {
		// No memory limit, agian!
		return wpcfg_get_memory_limit();
	}

	$memory = str_replace( 'M', '', WP_MAX_MEMORY_LIMIT );
	return (int) $memory;
}

/**
 * Constant reader for options, includes assign default feature.
 *
 * @param 				string $const Constant name
 * @param 				mixed $default Constant default value
 * @return 				mixed
 */
function wpcfg_option( $const, $default = false ) {
	$value = @ constant( $const );
	//echo 'Constant: ' . $const . '<br>Value: ' . $value . '<br>---------------------------------------------<br>';
	if ( $value === null ) {
		return $default;
	}

	return $value;
}

/**
 * Get wp-config.php path
 *
 * @return 				string
 */
function wpcfg_get_config_path() {
	return ABSPATH . 'wp-config.php';
}

/**
 * Determines if wp-config.php is writable or not.
 *
 * @return 				bool
 */
function wpcfg_config_writable() {
	return is_writable( wpcfg_get_config_path() );
}

/**
 * Waits for submit button, verifies the nonce and then send the changes.
 *
 * @return 				void
 */
function wpcfg_check_submit() {
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'update_wp-config' ) {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'update_wp-config' ) )
			wp_die( __( 'Cheating or something?', 'wpcfg' ) );

		if ( ! current_user_can( 'manage_options' ) )
			wp_die( __( 'This is big boys stuff, not yours. Understood?', 'wpcfg' ) );

		$config = wpcfg_generate_config( $_REQUEST );

		if ( wpcfg_config_writable() ) {
			wpcfg_make_backup();
			$success = wpcfg_write_config( $config );
			if ( $success ) {
				set_transient( 'wpcfg_config_updated', true, 10 );
				wp_redirect( add_query_arg( array(
					'tab' 				=>	isset( $_GET['tab'] ) ? $_GET['tab'] : 'general',
					'_wpnonce' 			=>	false
				) ) );
				exit;
			}
		} else {
			$output = __( '<p>All right, here is your new wp-config!</p>', 'wpcfg' );
			$output .= '<pre style="display: block;overflow: scroll;direction: ltr;height: 300px;">' . htmlspecialchars( $config ) . '</pre>';
			$output .= '<br><p>If you have any problems, contact your hosting support<br><a href="' . $_POST['_wp_http_referer'] . '">&larr; Back</a></p>';
			wp_die( $output, __( 'New wp-config.php Code', 'wpcfg' ), array( 'response' => 200 ) );
		}
	}
}
add_action( 'admin_init', 'wpcfg_check_submit', 9999 );

/**
 * Gets all options in once
 *
 * @return 				array
 */
function wpcfg_all_options() {
	$options = array();
	foreach( wpcfg_get_options() as $tab => $t_options ) {
		$options = array_merge( $options, $t_options );
	}

	return $options;
}

function bool2str( $val ) {
	if ( $val || $val == '1' )
		return 'true';

	return 'false';
}

/**
 * Generate new config depending on posted data
 *
 * @param 				array $data
 * @return 				string
 */
function wpcfg_generate_config( $data ) {
	global $wpdb;

	$config = $data['wpcfg'];
	foreach( $config as $k => $v ) {
		if ( $v == 'checked' || $v == 'on' )
			$config[ $k ] = true;
	}

	$options = wpcfg_all_options();
	$added_options = array();
	$to_remove_options = array();
	$output = '';

	$output .= '<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php automatically created by "wp-config.php Editor"
 * made by Ehsaan
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * Changed settings
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 * @author Ehsaan <iehsan.ir@gmail.com>
 *
 * @package WordPress
 */
	';

	// First add required options
	$added_options = array( 'db_name', 'db_user', 'db_host', 'db_password', 'db_charset', 'db_collate', 'wp_debug' );
	$db_name = ( isset( $config['db_name'] ) ? $config['db_name'] : DB_NAME );
	$db_user = ( isset( $config['db_user'] ) ? $config['db_user'] : DB_USER );
	$db_host = ( isset( $config['db_host'] ) ? $config['db_host'] : DB_HOST );
	$db_charset = ( isset( $config['db_charset'] ) ? $config['db_charset'] : DB_CHARSET );
	$db_collate = ( isset( $config['db_collate'] ) ? $config['db_collate'] : DB_COLLATE );
	$db_password = DB_PASSWORD;
	$table_prefix = $wpdb->prefix;

	if ( $_REQUEST['tab'] == 'general' )
		$wp_debug = ( isset( $config['wp_debug'] ) && $config['wp_debug'] ? bool2str( $config['wp_debug'] ) : 'false' );
	else
		$wp_debug = bool2str( WP_DEBUG );

	$output .= "

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', '$db_name' );

/** MySQL database username */
define( 'DB_USER', '$db_user' );

/** MySQL database password */
define( 'DB_PASSWORD', '$db_password' );

/** MySQL hostname */
define( 'DB_HOST', '$db_host' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', '$db_charset' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '$db_collate' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
" . '$table_prefix = \'' . $table_prefix . "';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', $wp_debug );
";
	
	$output .= "
/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '" . AUTH_KEY . "');
define('SECURE_AUTH_KEY',  '" . SECURE_AUTH_KEY . "');
define('LOGGED_IN_KEY',    '" . LOGGED_IN_KEY . "');
define('NONCE_KEY',        '" . NONCE_KEY . "');
define('AUTH_SALT',        '" . AUTH_SALT . "');
define('SECURE_AUTH_SALT', '" . SECURE_AUTH_SALT . "');
define('LOGGED_IN_SALT',   '" . LOGGED_IN_SALT . "');
define('NONCE_SALT',       '" . NONCE_SALT . "');

/**#@-*/\r\n
";
	
	// Add new options
	foreach( $config as $key => $value ) {
		$option = $options[ $key ];

		if ( in_array( $key, $added_options ) )
			continue; // Added already

		if ( $option['value'] == $value )
			continue; // We don't need it!

		if ( $value == $option['default'] ) {
			$to_remove_options[] = $key;
			continue; // Neither we don't need it, so we remove it!
		}

		$value2 = '';
		if ( $option['type'] == 'integer' )
			$value2 = (int) $value;

		if ( $option['type'] == 'boolean' )
			$value2 = bool2str( $value );

		if ( $option['type'] == 'string' )
			$value2 = "'" . $value . "'";

		if ( $key == 'wp_limit_memory' || $key == 'wp_max_memory' )
			$value2 = "'" . $value . "M'";

		$output .= "
define( '" . $option['const'] . "', $value2 );";
		$added_options[] = $key;
	}

	// Add previous options
	foreach( $options as $key => $value ) {
		$option = $value;

		if ( in_array( $key, $added_options ) )
			continue; // Added already

		if ( in_array( $key, $to_remove_options ) )
			continue; // Should remove

		if ( $option['input'] == 'hr' || $option['input'] == 'link' )
			continue;

		if ( ! isset( $option['value'] ) || ! isset( $option['default'] ) || ! isset( $option['const'] ) )
			continue; // Wrong

		if ( $option['value'] == $option['default'] )
			continue; // It's default

		$value2 = '';

		if ( $option['type'] == 'integer' )
			$value2 = (int) $option['value'];

		if ( $option['type'] == 'boolean' )
			$value2 = ( $option['value'] ? 'true' : 'false' );

		if ( $option['type'] == 'string' )
			$value2 = "'" . $option['value'] . "'";

		if ( $key == 'wp_limit_memory' || $key == 'wp_max_memory' )
			$value2 = "'" . $option['value'] . "M'";

		$output .= "
define( '" . $option['const'] . "', $value2 );";
		$added_options[] = $key;
	}

	$output .= "\r\n";
	$output .= "
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
";
	
	return $output;
}

/**
 * Wait for regenerate salt command.
 * When input received, new salts will downloaded from official API and will be replaced.
 *
 * @return 					void
 */
function wpcfg_check_regen_salt() {
	if ( isset( $_GET['regen_salt'] ) && isset( $_GET['_wpnonce'] ) ) {
		$nonce = $_GET['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'regenerate_salt' ) )
			wp_die( __( 'Cheating or something?', 'wpcfg' ) );

		if ( ! current_user_can( 'manage_options' ) )
			wp_die( __( 'This is big boys stuff, not yours. Understood?', 'wpcfg' ) );

		$new_config = wpcfg_regenerate_salt();
		if ( wpcfg_config_writable() ) {
			wpcfg_make_backup();
			$success = wpcfg_write_config( $new_config );
			if ( $success ) {
				set_transient( 'wpcfg_config_updated', true, 10 );
				wp_redirect( add_query_arg( array(
					'tab' 				=>	isset( $_GET['tab'] ) ? $_GET['tab'] : 'general',
					'regen_salt' 		=>	false,
					'_wpnonce' 			=>	false
				) ) );
				exit;
			}
		} else {
			$output = __( '<p>I regenerated all salts for you, but wp-config.php is not writable for me. So you have to copy your wp-config and paste it to your wp-config.</p>', 'wpcfg' );
			$output .= '<pre style="overflow: scroll;direction: ltr;height: 300px;">' . htmlspecialchars( $new_config ) . '</pre>';
			$output .= __( '<p>If you have any problems, contact your hosting support.<br><a href="javascript:;" onclick="window.history.back()">&larr; Back</a>', 'wpcfg' );
			
			wp_die( $output, 'Your new wp-config.php file', array( 'response' => 200 ) );		
		}
	}
}
add_action( 'admin_init', 'wpcfg_check_regen_salt', 998 );

/**
 * Download salts from official API and replace them into wp-config.php
 *
 * @return 					string
 */
function wpcfg_regenerate_salt() {
	/** Download salts **/
	$response = wp_remote_get( 'https://api.wordpress.org/secret-key/1.1/salt/' );
	if ( ! is_array( $response ) )
		wp_die( __( "Couldn't get new salts, make sure connection is sustainable", 'wpcfg' ) );

	$salts = $response['body'];

	/** Read wp-config.php **/
	$wp_config = file_get_contents( wpcfg_get_config_path() ); // I'd rather use it! It's easier!

	$lines = explode( PHP_EOL, $wp_config );
	$start_line = 0;
	$end_line = 0;

	foreach( $lines as $num => $line ) {
		$number = $num;
		if ( substr( $line, 0, 17 ) == "define('AUTH_KEY'" || substr( $line, 0, 18 ) == "define( 'AUTH_KEY'" ) {
			$start_line = $number;
		}

		if ( substr( $line, 0, 19 ) == "define('NONCE_SALT'" || substr( $line, 0, 20 ) == "define( 'NONCE_SALT'" ) {
			$end_line = $number;
		}
	}

	$salts = explode( "\n", $salts );
	$n = 0;
	foreach( $salts as $salt ) {
		$lines[ $start_line + $n ] = $salt;
		$n++;
	}

	return join( PHP_EOL, $lines );
}

/**
 * Make a backup from WordPress wp-config.php file
 * as wp-config.backup.php
 *
 * @return 				bool
 */
function wpcfg_make_backup() {
	return copy( wpcfg_get_config_path(), ABSPATH . 'wp-config.backup.php' );
}

/**
 * Writes new contents to wp-config.php
 *
 * @param 				string $contents
 * @return 				bool
 */
function wpcfg_write_config( $contents ) {
	$handle = fopen( wpcfg_get_config_path(), 'w' );
	if ( ! $handle )
		return false;

	fwrite( $handle , $contents );
	fclose( $handle );

	return true;
}

/**
 * Wait for restore backup command.
 * When input received, latest backup will be replaced and then removed.
 *
 * @return 					void
 */
function wpcfg_check_restore_backup() {
	if ( isset( $_GET['restore_backup'] ) && isset( $_GET['_wpnonce'] ) ) {
		$nonce = $_GET['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'wpcfg_restore_backup' ) )
			wp_die( __( 'Cheating or something?', 'wpcfg' ) );

		if ( ! current_user_can( 'manage_options' ) )
			wp_die( __( 'This is big boys stuff, not yours. Understood?', 'wpcfg' ) );

		$file = ABSPATH . 'wp-config.backup.php';
		if ( ! file_exists( $file ) )
			wp_die( __( 'Backup file doesn\'t exist.', 'wpcfg' ), 'Error', array( 'back_link' => true ) );

		$backup = file_get_contents( $file );

		unlink( $file );

		if ( wpcfg_config_writable() ) {
			if ( wpcfg_write_config( $backup ) ) {
				set_transient( 'wpcfg_restore_backup', true, 10 );
				wp_redirect( add_query_arg( array(
					'_wpnonce' 			=>	false,
					'restore_backup' 	=>	false
				) ) );
			} else {
				wp_die( __( 'We couldn\'t write backup to file', 'wpcfg' ) );
			}
		} else {
			$output = __( '<p>Here is your backup, sorry I could not write it back.</p>', 'wpcfg' );
			$output .= '<pre style="overflow: scroll;direction: ltr;height: 300px;">' . htmlspecialchars( $backup ) . '</pre>';
			$output .= __( '<p>If you have any problems, contact your hosting support.<br><a href="javascript:;" onclick="window.history.back()">&larr; Back</a>', 'wpcfg' );
			
			wp_die( $output, 'Your previous wp-config.php file', array( 'response' => 200 ) );	
		}
	}
}
add_action( 'admin_init', 'wpcfg_check_restore_backup', 996 );

/************* MISC STUFF *****************/
/**
 * Add about link to admin footer.
 *
 * @return 					void
 */
function wpcfg_admin_footer( $text ) {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'edit_wp_config.php' ) {
		echo sprintf( __( '<span id="footer-thankyou">Thank you for using <a href="%s">WP Config Editor</a> made by <a href="%s">Ehsaan</a></span>', 'wpcfg' ), 'http://wordpress.org/plugins/wp-config-interface', 'http://iehsan.ir' );
	} else {
		echo $text;
	}
}
add_filter( 'admin_footer_text', 'wpcfg_admin_footer' );

/**
 * Action links for plugins page
 *
 * @return 				array
 */
function wpcfg_action_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=edit_wp_config.php' ) ) . '">' . __( 'wp-config.php Edit page', 'wpcfg' ) . '</a>';
	if ( wpcfg_option( 'WPLANG', 'en_US' ) == 'fa_IR' ) {
		$links[] = '<a href="http://forum.wp-parsi.com">پشتیبانی پارسی</a>';
	}

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpcfg_action_links' );

/* End of ./wp-config-interface.php */