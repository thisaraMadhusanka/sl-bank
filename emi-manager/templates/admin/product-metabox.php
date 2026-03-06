<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="emi-product-metabox">
    <p><label for="emi-mode"><strong><?php esc_html_e('EMI Status for this Product', 'emi-manager'); ?></strong></label></p>
    <p>
        <select id="emi-mode" name="_emi_mode" style="width:100%;">
            <option value="global" <?php selected($emi_mode, 'global'); ?>><?php esc_html_e('Use Global Settings', 'emi-manager'); ?></option>
            <option value="custom" <?php selected($emi_mode, 'custom'); ?>><?php esc_html_e('Custom (Select Specific Banks)', 'emi-manager'); ?></option>
            <option value="disabled" <?php selected($emi_mode, 'disabled'); ?>><?php esc_html_e('Disable EMI for this product', 'emi-manager'); ?></option>
        </select>
    </p>

    <div id="emi-custom-banks" style="<?php echo 'custom' === $emi_mode ? '' : 'display:none;'; ?>">
        <p><strong><?php esc_html_e('Allowed Banks', 'emi-manager'); ?></strong></p>
        <?php if (!empty($banks)): ?>
            <?php foreach ($banks as $bank): ?>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" name="_emi_allowed_banks[]" value="<?php echo esc_attr($bank['id']); ?>" <?php checked(in_array($bank['id'], $allowed, true)); ?>>
                    <?php echo esc_html($bank['name']); ?>
                </label>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="description">No banks configured.</p>
        <?php endif; ?>
    </div>
</div>
<script>
    (function() {
        var modeSelect = document.getElementById('emi-mode');
        var customDiv  = document.getElementById('emi-custom-banks');
        if (modeSelect && customDiv) {
            modeSelect.addEventListener('change', function() {
                customDiv.style.display = this.value === 'custom' ? '' : 'none';
            });
        }
    })();
</script>