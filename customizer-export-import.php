<?php
/**
 * Plugin Name: Customizer Export/Import
 * Plugin URI: http://www.wpbeaverbuilder.com/wordpress-customizer-export-import-plugin/?utm_source=external&utm_medium=customizer-export&utm_campaign=plugins-page
 * Description: Adds settings export and import functionality to the WordPress customizer.
 * Version: 0.4
 * Author: The Beaver Builder Team
 * Author URI: http://www.wpbeaverbuilder.com/?utm_source=external&utm_medium=customizer-export&utm_campaign=plugins-page
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: customizer-export-import
 */
define( 'CEI_VERSION', '0.4' );
define( 'CEI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CEI_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

/* Classes */
require_once CEI_PLUGIN_DIR . 'classes/class-cei-core.php';

/* Actions */
add_action( 'plugins_loaded', 'CEI_Core::load_plugin_textdomain' );
add_action( 'customize_controls_print_scripts', 'CEI_Core::controls_print_scripts' );
add_action( 'customize_controls_enqueue_scripts', 'CEI_Core::controls_enqueue_scripts' );
add_action( 'customize_register', 'CEI_Core::init', 999999 );
add_action( 'customize_register', 'CEI_Core::register' );