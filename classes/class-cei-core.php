<?php

/**
 * The main export/import class.
 *
 * @since 0.1
 */
final class CEI_Core {

	/**
	 * An array of core options that shouldn't be imported.
	 *
	 * @since 0.3
	 * @access private
	 * @var array $core_options
	 */
	static private $core_options = array(
		'blogname',
		'blogdescription',
		'show_on_front',
		'page_on_front',
		'page_for_posts',
	);

	/**
	 * Load a translation for this plugin.
	 *
	 * @since 0.1
	 * @return void
	 */	 
	static public function load_plugin_textdomain() 
	{
		load_plugin_textdomain( 'customizer-export-import', false, basename( CEI_PLUGIN_DIR ) . '/lang/' );
	}
	
	/**
	 * Check to see if we need to do an export or import.
	 * This should be called by the customize_register action.
	 *
	 * @since 0.1
	 * @since 0.3 Passing $wp_customize to the export and import methods.
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	static public function init( $wp_customize ) 
	{
		if ( current_user_can( 'edit_theme_options' ) ) {
			
			if ( isset( $_REQUEST['cei-export'] ) ) {
				self::_export( $wp_customize );
			}
			if ( isset( $_REQUEST['cei-import'] ) && isset( $_FILES['cei-import-file'] ) ) {
				self::_import( $wp_customize );
			}
		}
	}
	
	/**
	 * Prints scripts for the control.
	 *
	 * @since 0.1
	 * @return void
	 */
	static public function controls_print_scripts() 
	{
		global $cei_error;
		
		if ( $cei_error ) {
			echo '<script> alert("' . $cei_error . '"); </script>';
		}
	}
	
	/**
	 * Enqueues scripts for the control.
	 *
	 * @since 0.1
	 * @return void
	 */
	static public function controls_enqueue_scripts() 
	{
		// Register
		wp_register_style( 'cei-css', CEI_PLUGIN_URL . '/css/customizer.css', array(), CEI_VERSION );
		wp_register_script( 'cei-js', CEI_PLUGIN_URL . '/js/customizer.js', array( 'jquery' ), CEI_VERSION, true );
	
		// Localize
		wp_localize_script( 'cei-js', 'CEIl10n', array(
			'emptyImport'	=> __( 'Please choose a file to import.', 'customizer-export-import' )
		));
		
		// Config
		wp_localize_script( 'cei-js', 'CEIConfig', array(
			'customizerURL'	  => admin_url( 'customize.php' ),
			'exportNonce'	  => wp_create_nonce( 'cei-exporting' )
		));
	
		// Enqueue
		wp_enqueue_style( 'cei-css' );
		wp_enqueue_script( 'cei-js' );
	}
	
	/**
	 * Registers the control with the customizer.
	 *
	 * @since 0.1
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	static public function register( $wp_customize ) 
	{
		require_once CEI_PLUGIN_DIR . 'classes/class-cei-control.php';

		// Add the export/import section.
		$wp_customize->add_section( 'cei-section', array(
			'title'	   => __( 'Export/Import', 'customizer-export-import' ),
			'priority' => 10000000
		));
		
		// Add the export/import setting.
		$wp_customize->add_setting( 'cei-setting', array(
			'default' => '',
			'type'	  => 'none'
		));
		
		// Add the export/import control.
		$wp_customize->add_control( new CEI_Control( 
			$wp_customize, 
			'cei-setting', 
			array(
				'section'	=> 'cei-section',
				'priority'	=> 1
			)
		));
	}
	
	/**
	 * Export customizer settings.
	 *
	 * @since 0.1
	 * @since 0.3 Added $wp_customize param and exporting of options.
	 * @access private
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	static private function _export( $wp_customize ) 
	{
		if ( ! wp_verify_nonce( $_REQUEST['cei-export'], 'cei-exporting' ) ) {
			return;
		}
		
		$theme		= get_stylesheet();
		$template	= get_template();
		$charset	= get_option( 'blog_charset' );
		$mods		= get_theme_mods();
		$data		= array(
						  'template'  => $template,
						  'mods'	  => $mods ? $mods : array(),
						  'options'	  => array()
					  );
		
		// Get options from the Customizer API.
		$settings = $wp_customize->settings();
	
		foreach ( $settings as $key => $setting ) {
			
			if ( 'option' == $setting->type ) {
				
				// Don't save widget data.
				if ( stristr( $key, 'widget_' ) ) {
					continue;
				}
				
				// Don't save sidebar data.
				if ( stristr( $key, 'sidebars_' ) ) {
					continue;
				}
				
				// Don't save core options.
				if ( in_array( $key, self::$core_options ) ) {
					continue;
				}
				
				$data['options'][ $key ] = $setting->value();
			}
		}
					  
		// Plugin developers can specify additional option keys to export.
		$option_keys = apply_filters( 'cei_export_option_keys', array() );
		
		foreach ( $option_keys as $option_key ) {
			
			$option_value = get_option( $option_key );
			
			if ( $option_value ) {
				$data['options'][ $option_key ] = $option_value;
			}
		}
		
		// Set the download headers.
		header( 'Content-disposition: attachment; filename=' . $theme . '-export.dat' );
		header( 'Content-Type: application/octet-stream; charset=' . $charset );
		
		// Serialize the export data.
		echo serialize( $data );
		
		// Start the download.
		die();
	}
	
	/**
	 * Imports uploaded mods and calls WordPress core customize_save actions so
	 * themes that hook into them can act before mods are saved to the database.
	 *
	 * @since 0.1
	 * @since 0.3 Added $wp_customize param and importing of options.
	 * @access private
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	static private function _import( $wp_customize ) 
	{
		// Make sure we have a valid nonce.
		if ( ! wp_verify_nonce( $_REQUEST['cei-import'], 'cei-importing' ) ) {
			return;
		}
		
		// Make sure WordPress upload support is loaded.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		
		// Load the export/import option class.
		require_once CEI_PLUGIN_DIR . 'classes/class-cei-option.php';
		
		// Setup global vars.
		global $wp_customize;
		global $cei_error;
		
		// Setup internal vars.
		$cei_error	 = false;
		$template	 = get_template();
		$overrides   = array( 'test_form' => FALSE, 'mimes' => array('dat' => 'text/plain') );
		$file        = wp_handle_upload( $_FILES['cei-import-file'], $overrides );

		// Make sure we have an uploaded file.
		if ( isset( $file['error'] ) ) {
			$cei_error = $file['error'];
			return;
		}
		if ( ! file_exists( $file['file'] ) ) {
			$cei_error = __( 'Error importing settings! Please try again.', 'customizer-export-import' );
			return;
		}
		
		// Get the upload data.
		$raw  = file_get_contents( $file['file'] );
		$data = @unserialize( $raw );
		
		// Remove the uploaded file.
		unlink( $file['file'] );
		
		// Data checks.
		if ( 'array' != gettype( $data ) ) {
			$cei_error = __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'customizer-export-import' );
			return;
		}
		if ( ! isset( $data['template'] ) || ! isset( $data['mods'] ) ) {
			$cei_error = __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'customizer-export-import' );
			return;
		}
		if ( $data['template'] != $template ) {
			$cei_error = __( 'Error importing settings! The settings you uploaded are not for the current theme.', 'customizer-export-import' );
			return;
		}
		
		// Import images.
		if ( isset( $_REQUEST['cei-import-images'] ) ) {
			$data['mods'] = self::_import_images( $data['mods'] );
		}
		
		// Import custom options.
		if ( isset( $data['options'] ) ) {
			
			foreach ( $data['options'] as $option_key => $option_value ) {
				
				$option = new CEI_Option( $wp_customize, $option_key, array(
					'default'		=> '',
					'type'			=> 'option',
					'capability'	=> 'edit_theme_options'
				) );
				
				$option->import( $option_value );
			}
		}
		
		// Call the customize_save action.
		do_action( 'customize_save', $wp_customize );
		
		// Loop through the mods.
		foreach ( $data['mods'] as $key => $val ) {
			
			// Call the customize_save_ dynamic action.
			do_action( 'customize_save_' . $key, $wp_customize );
			
			// Save the mod.
			set_theme_mod( $key, $val );
		}
		
		// Call the customize_save_after action.
		do_action( 'customize_save_after', $wp_customize );
	}
	
	/**
	 * Imports images for settings saved as mods.
	 *
	 * @since 0.1
	 * @access private
	 * @param array $mods An array of customizer mods.
	 * @return array The mods array with any new import data.
	 */
	static private function _import_images( $mods ) 
	{
		foreach ( $mods as $key => $val ) {
			
			if ( self::_is_image_url( $val ) ) {
				
				$data = self::_sideload_image( $val );
				
				if ( ! is_wp_error( $data ) ) {
					
					$mods[ $key ] = $data->url;
					
					// Handle header image controls.
					if ( isset( $mods[ $key . '_data' ] ) ) {
						$mods[ $key . '_data' ] = $data;
						update_post_meta( $data->attachment_id, '_wp_attachment_is_custom_header', get_stylesheet() );
					}
				}
			}
		}
		
		return $mods;
	}
	
	/**
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @since 0.1
	 * @access private
	 * @param string $file The image file path.
	 * @return array An array of image data.
	 */
	static private function _sideload_image( $file ) 
	{
		$data = new stdClass();
		
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
		if ( ! empty( $file ) ) {
			
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array = array();
			$file_array['name'] = basename( $matches[0] );
	
			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );
	
			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}
	
			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, 0 );
	
			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $id;
			}
			
			// Build the object to return.
			$meta					= wp_get_attachment_metadata( $id );
			$data->attachment_id	= $id;
			$data->url				= wp_get_attachment_url( $id );
			$data->thumbnail_url	= wp_get_attachment_thumb_url( $id );
			$data->height			= $meta['height'];
			$data->width			= $meta['width'];
		}
	
		return $data;
	}
	
	/**
	 * Checks to see whether a string is an image url or not.
	 *
	 * @since 0.1
	 * @access private
	 * @param string $string The string to check.
	 * @return bool Whether the string is an image url or not.
	 */
	static private function _is_image_url( $string = '' ) 
	{
		if ( is_string( $string ) ) {
			
			if ( preg_match( '/\.(jpg|jpeg|png|gif)/i', $string ) ) {
				return true;
			}
		}
		
		return false;
	}
}
