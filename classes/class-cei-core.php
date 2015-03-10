<?php

/**
 * @class CEI_Core
 */
final class CEI_Core {

	/**
     * @method load_plugin_textdomain
     */	 
    static public function load_plugin_textdomain() 
    {
        load_plugin_textdomain( 'customizer-export-import', false, basename( CEI_PLUGIN_DIR ) . '/lang/' );
    }
	
	/**
     * @method init
     */
	static public function init() 
	{
    	if ( current_user_can( 'edit_theme_options' ) ) {
        	
            if ( isset( $_REQUEST['cei-export'] ) ) {
                self::_export();
            }
            if ( isset( $_REQUEST['cei-import'] ) && isset( $_FILES['cei-import-file'] ) ) {
                self::_import();
            }
    	}
    }
	
	/**
     * @method controls_print_scripts
     */
	static public function controls_print_scripts() 
	{
        global $cei_error;
        
        if ( $cei_error ) {
            echo '<script> alert("' . $cei_error . '"); </script>';
        }
	}
	
	/**
     * @method controls_enqueue_scripts
     */
	static public function controls_enqueue_scripts() 
	{
    	// Register
    	wp_register_style( 'cei-css', CEI_PLUGIN_URL . '/css/customizer.css', array(), CEI_VERSION );
    	wp_register_script( 'cei-js', CEI_PLUGIN_URL . '/js/customizer.js', array( 'jquery' ), CEI_VERSION, true );
	
        // Localize
        wp_localize_script( 'cei-js', 'CEIl10n', array(
            'emptyImport'   => __( 'Please choose a file to import.', 'customizer-export-import' )
        ));
        
        // Config
        wp_localize_script( 'cei-js', 'CEIConfig', array(
            'customizerURL'   => admin_url( 'customize.php' ),
            'exportNonce'     => wp_create_nonce( 'cei-exporting' )
        ));
	
        // Enqueue
        wp_enqueue_style( 'cei-css' );
        wp_enqueue_script( 'cei-js' );
	}
	
	/**
     * @method register
     */
	static public function register( $customizer ) 
	{
    	require_once CEI_PLUGIN_DIR . 'classes/class-cei-control.php';

	    // Add the export/import section.
        $customizer->add_section( 'cei-section', array(
            'title'    => __( 'Export/Import', 'customizer-export-import' ),
            'priority' => 10000000
        ));
        
        // Add the export/import setting.
        $customizer->add_setting( 'cei-setting', array(
            'default' => '',
            'type'    => 'none'
        ));
        
        // Add the export/import control.
        $customizer->add_control( new CEI_Control( 
            $customizer, 
            'cei-setting', 
            array(
                'section'   => 'cei-section',
                'priority'  => 1
            )
        ));
	}
	
	/**
     * @method _export
     * @private
     */
	static private function _export() 
	{
    	if ( ! wp_verify_nonce( $_REQUEST['cei-export'], 'cei-exporting' ) ) {
        	return;
    	}
    	
    	$theme      = get_option( 'stylesheet' );
    	$template   = get_option( 'template' );
    	$charset    = get_option( 'blog_charset' );
    	$mods       = get_theme_mods();
    	$data		= array(
			        	  'template'  => $template,
			        	  'mods'      => $mods ? $mods : array(),
			        	  'options'	  => array()
			    	  );
			    	  
		// Plugin developers can specify option keys to export.
		$option_keys = apply_filters( 'cei_export_option_keys', array() );
		
		// Add options to the data.
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
     * @method _import
     * @private
     */
	static private function _import() 
	{
    	if ( ! wp_verify_nonce( $_REQUEST['cei-import'], 'cei-importing' ) ) {
        	return;
    	}
    	
    	global $wp_customize;
    	global $cei_error;
    	
    	$cei_error   = false;
    	$template    = get_option( 'template' );
    	$raw         = file_get_contents( $_FILES['cei-import-file']['tmp_name'] );
    	$data        = @unserialize( $raw );
    	
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
		    	update_option( $option_key, $option_value );
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
     * @method _import_images
     * @private
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
     * modified to return the url instead of html.
     *
     * @method _sideload_image
     * @private
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
    		$meta                   = wp_get_attachment_metadata( $id );
    		$data->attachment_id    = $id;
    		$data->url              = wp_get_attachment_url( $id );
    		$data->thumbnail_url    = wp_get_attachment_thumb_url( $id );
    		$data->height           = $meta['height'];
    		$data->width            = $meta['width'];
    	}
    
    	return $data;
    }
	
	/**
     * @method _is_image_url
     * @private
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
