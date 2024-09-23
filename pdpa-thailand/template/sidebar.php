<?php
    /* PDPA Thailand - Popup Settings template */
    /* Version: 1.2 */    
?>
<div class="dpdpa--popup-bg"></div>
<div class="dpdpa--popup-sidebar">
    <div class="dpdpa--popup-sidebar-header">
        <div class="dpdpa--popup-logo">
            <?php PDPA_THAILAND_Public::show_logo(); ?>
        </div>
        <a href="#" class="dpdpa--popup-settings-close" id="dpdpa--popup-settings-close"></a>
    </div>
    <div class="dpdpa--popup-sidebar-container">
        <div class="dpdpa--popup-section intro">
            <em><?php _e('Privacy Preferences', 'pdpa-thailand'); ?></em>
            <p><?php PDPA_THAILAND_Public::show_sidebar_message(); ?></p>
            <a href="#" class="dpdpa--popup-button" id="pdpa_settings_allow_all"><?php _e('Allow All', 'pdpa-thailand'); ?></a>
        </div>
        <div class="dpdpa--popup-section list">            
            <em><?php _e('Manage Consent Preferences', 'pdpa-thailand'); ?></em>
            <ul class="dpdpa--popup-list" id="dpdpa--popup-list">
                <li>
                    <div class="dpdpa--popup-header">
                        <div class="dpdpa--popup-title"><?php echo $this->cookie_necessary['cookie_necessary_title']; ?></div>
                        <div class="dpdpa--popup-action text"><?php _e('Always Active', 'pdpa-thailand'); ?></div>
                    </div>
                    <p><?php echo $this->cookie_necessary['cookie_necessary_description']; ?></p>
                </li>
                <?php
                    if ( $this->cookie_set ) {
                        foreach ( $this->cookie_set as $key => $val ) {
                ?>
                    <li>
                        <div class="dpdpa--popup-header">
                            <div class="dpdpa--popup-title"><?php echo stripslashes($val['consent_title']); ?></div>
                            <div class="dpdpa--popup-action">
                                <label class="dpdpa--popup-switch">
                                    <input type="checkbox" name="dpdpa_consent[]" value="<?php echo $key; ?>" checked>
                                    <span class="dpdpa--popup-slider round"></span>
                                </label>
                            </div>
                        </div>
                        <p><?php echo stripslashes($val['consent_description']); ?></p>
                    </li>
                <?php                            
                        }
                    }
                ?>                                
            </ul>
            <a href="#" class="dpdpa--popup-button" id="pdpa_settings_confirm"><?php _e('Save', 'pdpa-thailand'); ?></a>
        </div>
    </div>
</div>