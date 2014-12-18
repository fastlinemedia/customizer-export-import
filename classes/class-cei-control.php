<?php

/**
 * @class CEI_Control
 */
final class CEI_Control extends WP_Customize_Control {
    
    /**
     * @method render_content
     * @protected
     */
    protected function render_content()
    {
        include CEI_PLUGIN_DIR . 'includes/control.php';
    }
}