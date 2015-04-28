<?php
	
/**
 * @class CEI_Option
 */
final class CEI_Option extends WP_Customize_Setting {
	
	public function import( $value ) {
		$this->update( $value );	
	}
}