<?php
    /* PDPA Thailand - Popup template */
    /* Version: 1.2 */    
?>

<div class="dpdpa--popup">
    <div class="container">
        <div class="dpdpa--popup-container">
            <div class="dpdpa--popup-text">                
                <p><?php PDPA_THAILAND_Public::show_cookie_consent_message(); ?></p>
            </div>
            <div class="dpdpa--popup-button-group">
                <a href="#" class="dpdpa--popup-button" id="dpdpa--popup-accept-all"><?php _e('Allow', 'pdpa-thailand'); ?></a>
            </div>
            <a href="#" class="dpdpa--popup-close" id="dpdpa--popup-close"></a>
        </div>
    </div>
</div>