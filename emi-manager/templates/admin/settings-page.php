<?php
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Fetch Global Settings
$enabled = get_option('emi_manager_enabled', '1');
$rounding = get_option('emi_manager_rounding', 'two_decimal');
$tax_mode = get_option('emi_manager_tax_mode', 'exclude');
$border_color = get_option('emi_manager_border_color', '#5c2079');
$hover_color = get_option('emi_manager_hover_color', '#9ca3af');
$terms_html = get_option('emi_manager_terms_html', '');

// Fetch Banks
// $this->bank_service is available from AdminMenu context.
$all_banks = $this->bank_service->get_all_banks();

// Presets data for quick add
$presets = [
    'boc' => ['name' => 'Bank of Ceylon', 'img' => 'boc-logo.png'],
    'combank' => ['name' => 'Commercial Bank', 'img' => 'com-bank.png'],
    'dfcc' => ['name' => 'DFCC Bank', 'img' => 'dfcc.png'],
    'hnb' => ['name' => 'Hatton National Bank', 'img' => 'hnb.png'],
    'hsbc' => ['name' => 'HSBC', 'img' => 'hsbc-via-sampath-ipg.png'],
    'lolc' => ['name' => 'LOLC Finance', 'img' => 'lolc-finance.png'],
    'ntb' => ['name' => 'Nations Trust Bank', 'img' => 'bank-ntb-amex-logo.png'],
    'panasia' => ['name' => 'Pan Asia Bank', 'img' => 'pan-asia-bank.png'],
    'peoples' => ['name' => 'People\'s Bank', 'img' => 'peoples-bank.png'],
    'sampath' => ['name' => 'Sampath Bank', 'img' => 'sampath-bank.png'],
    'seylan' => ['name' => 'Seylan Bank', 'img' => 'seylan-bank.png'],
];

// Build a list of already-added preset keys by matching bank names
$added_preset_keys = [];
foreach ($all_banks as $bank) {
    foreach ($presets as $key => $preset) {
        if (strtolower(trim($bank['name'])) === strtolower(trim($preset['name']))) {
            $added_preset_keys[] = $key;
        }
    }
}
?>

<div class="wrap emi-admin-wrap" id="emi-manager-admin">
    <div class="emi-toast-container" id="emi-toast-container" aria-live="polite"></div>

    <h1 class="wp-heading-inline">EMI Manager Dashboard</h1>
    <hr class="wp-header-end">

    <h2 class="nav-tab-wrapper hide-if-no-js" id="emi-tabs">
        <a href="#tab-global" class="nav-tab nav-tab-active">Global Settings</a>
        <a href="#tab-active-banks" class="nav-tab">Configured Banks</a>
        <a href="#tab-add-bank" class="nav-tab">Add Custom Bank</a>
    </h2>

    <div class="emi-tab-content">
        <!-- GLOBAL SETTINGS TAB -->
        <div id="tab-global" class="emi-tab-pane active">
            <div class="emi-card">
                <h3>Global Configuration</h3>
                <form id="emi-global-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable EMI System</th>
                            <td>
                                <label class="emi-toggle">
                                    <input type="checkbox" name="enabled" value="1" <?php checked($enabled, '1'); ?>>
                                    <span class="emi-toggle__slider"></span>
                                </label>
                                <p class="description">Turn on to display EMI tables under product prices.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Rounding Rule</th>
                            <td>
                                <select name="rounding">
                                    <option value="two_decimal" <?php selected($rounding, 'two_decimal'); ?>>Two Decimals (e.g. 10.99)</option>
                                    <option value="nearest_whole" <?php selected($rounding, 'nearest_whole'); ?>>Nearest Whole Number (e.g. 11)</option>
                                    <option value="bankers" <?php selected($rounding, 'bankers'); ?>>Banker's Rounding (Half Even)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Tax Calculation</th>
                            <td>
                                <select name="tax_mode">
                                    <option value="exclude" <?php selected($tax_mode, 'exclude'); ?>>Exclude Tax</option>
                                    <option value="include" <?php selected($tax_mode, 'include'); ?>>Include Tax</option>
                                </select>
                                <p class="description">Determine if the EMI calculations should be based on the price with or without tax.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Active Border Color</th>
                            <td>
                                <input type="text" name="border_color" value="<?php echo esc_attr($border_color); ?>" class="emi-color-picker" data-default-color="#5c2079" />
                                <p class="description">Used for active (selected) bank card border on the frontend.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Hover Color</th>
                            <td>
                                <input type="text" name="hover_color" value="<?php echo esc_attr($hover_color); ?>" class="emi-color-picker" data-default-color="#9ca3af" />
                                <p class="description">Used for hover state border on the frontend bank cards.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Terms & Conditions Message</th>
                            <td>
                                <?php
wp_editor(
    $terms_html,
    'terms_html',
[
    'textarea_name' => 'terms_html',
    'media_buttons' => true,
    'textarea_rows' => 5,
    'teeny' => false,
]
);
?>
                                <p class="description">This message will appear at the bottom of the EMI tables. Can include HTML.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="button" class="button button-primary" id="emi-save-global">Save Global Settings</button>
                    </p>
                </form>
            </div>
        </div>

        <!-- CONFIGURED BANKS TAB -->
        <div id="tab-active-banks" class="emi-tab-pane">
            
            <div class="emi-card" style="margin-bottom: 2rem;">
                <h3>Bulk Add Sri Lankan Banks</h3>
                <p>Select multiple banks below and click "Apply" to instantly add them to your store with standard 3, 6, 12, and 24-month preset plans.</p>
                <div class="emi-presets-grid emi-multi-select-grid">
                    <?php foreach ($presets as $key => $preset):
    $is_added = in_array($key, $added_preset_keys);
?>
                        <div class="emi-preset-card emi-preset-selectable <?php echo $is_added ? 'emi-preset-added' : ''; ?>" data-preset="<?php echo esc_attr($key); ?>">
                            <div class="emi-preset-checkbox-overlay">
                                <span class="dashicons <?php echo $is_added ? 'dashicons-yes-alt' : 'dashicons-saved'; ?>"></span>
                            </div>
                            <img src="<?php echo esc_url(EMI_MANAGER_URL . 'assets/images/' . $preset['img']); ?>" alt="<?php echo esc_attr($preset['name']); ?>">
                            <span><?php echo esc_html($preset['name']); ?></span>
                            <?php if ($is_added): ?>
                                <small style="color:#0f6c0f; font-size:11px;">✓ Added</small>
                            <?php
    endif; ?>
                        </div>
                    <?php
endforeach; ?>
                </div>
                <div style="margin-top:15px; text-align:right;">
                    <button type="button" class="button button-primary" id="emi-bulk-apply-presets" disabled>Apply Selected Banks</button>
                </div>
            </div>

            <hr>

            <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Configured Banks List</h3>

            <?php if (empty($all_banks)): ?>
                <div class="emi-empty-state">
                    <h3>No banks configured yet</h3>
                    <p>Select banks from the grid above or go to the <strong>Add Custom Bank</strong> tab.</p>
                </div>
            <?php
else: ?>
                <div class="emi-banks-accordion" id="emi-banks-accordion">
                    <?php foreach ($all_banks as $bank): ?>
                        <div class="emi-bank-accordion-item" data-bank-id="<?php echo esc_attr($bank['id']); ?>">
                            <div class="emi-bank-accordion-header">
                                <div class="emi-bank-summary">
                                    <?php if (!empty($bank['logo_url'])): ?>
                                        <img src="<?php echo esc_url($bank['logo_url']); ?>" alt="logo" class="emi-admin-bank-logo">
                                    <?php
        endif; ?>
                                    <strong><?php echo esc_html($bank['name']); ?></strong>
                                    <span class="emi-status-badge <?php echo $bank['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $bank['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div class="emi-bank-actions">
                                    <button class="button emi-toggle-accordion">Edit Plans <span class="dashicons dashicons-arrow-down-alt2"></span></button>
                                </div>
                            </div>
                            
                            <div class="emi-bank-accordion-body" style="display: none;">
                                <form class="emi-bank-form">
                                    <input type="hidden" name="bank_id" value="<?php echo esc_attr($bank['id']); ?>">
                                    
                                    <div class="emi-bank-header-edit">
                                        <div class="emi-field-group">
                                            <label>Bank Name</label>
                                            <input type="text" name="bank_name" value="<?php echo esc_attr($bank['name']); ?>" required>
                                        </div>
                                        <div class="emi-field-group">
                                            <label>Status</label>
                                            <select name="bank_active">
                                                <option value="1" <?php selected($bank['is_active'], 1); ?>>Active</option>
                                                <option value="0" <?php selected($bank['is_active'], 0); ?>>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="emi-field-group">
                                            <label>Logo</label>
                                            <div class="emi-logo-upload-wrap">
                                                <input type="hidden" name="bank_logo" value="<?php echo esc_attr($bank['logo_url']); ?>" class="emi-logo-input">
                                                <img src="<?php echo esc_url($bank['logo_url']); ?>" class="emi-logo-preview" style="<?php echo empty($bank['logo_url']) ? 'display:none;' : ''; ?>">
                                                <button type="button" class="button emi-upload-btn">Upload / Change</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="emi-plans-table-container">
                                        <h4>Installment Plans</h4>
                                        <table class="wp-list-table widefat striped emi-plans-table">
                                            <thead>
                                                <tr>
                                                    <th>Months</th>
                                                    <th>Surcharge (%)</th>
                                                    <th>Fixed Fee</th>
                                                    <th>Active</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bank['plans'] as $idx => $plan): ?>
                                                <tr class="emi-plan-row">
                                                    <td><input type="number" min="1" max="60" name="plans[<?php echo $idx; ?>][months]" value="<?php echo esc_attr($plan['months']); ?>" class="small-text"></td>
                                                    <td><input type="number" step="0.01" name="plans[<?php echo $idx; ?>][surcharge]" value="<?php echo esc_attr($plan['surcharge_percent']); ?>"></td>
                                                    <td><input type="number" step="0.01" name="plans[<?php echo $idx; ?>][fixed_fee]" value="<?php echo esc_attr($plan['fixed_fee']); ?>"></td>
                                                    <td>
                                                        <label class="emi-toggle emi-toggle--small">
                                                            <input type="checkbox" name="plans[<?php echo $idx; ?>][active]" value="1" <?php checked($plan['is_active'], 1); ?>>
                                                            <span class="emi-toggle__slider"></span>
                                                        </label>
                                                    </td>
                                                    <td><button type="button" class="button button-link-delete emi-remove-plan">Remove</button></td>
                                                </tr>
                                                <?php
        endforeach; ?>
                                            </tbody>
                                        </table>
                                        <div class="emi-plan-table-actions">
                                            <button type="button" class="button emi-add-plan">Add Month Plan</button>
                                        </div>
                                    </div>
                                    
                                    <div class="emi-bank-footer-actions">
                                        <button type="button" class="button button-primary emi-save-bank">Save Bank</button>
                                        <button type="button" class="button button-link-delete emi-delete-bank" data-id="<?php echo esc_attr($bank['id']); ?>">Remove Bank</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php
    endforeach; ?>
                </div>
            <?php
endif; ?>
        </div>

        <!-- ADD NEW BANK TAB -->
        <div id="tab-add-bank" class="emi-tab-pane">


            <div class="emi-card" style="margin-top: 2rem;">
                <h3>Create Custom Bank</h3>
                <form class="emi-bank-form" id="emi-custom-bank-form">
                    <input type="hidden" name="bank_id" value="0">
                    
                    <div class="emi-bank-header-edit">
                        <div class="emi-field-group">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" placeholder="e.g. My Custom Bank" required>
                        </div>
                        <div class="emi-field-group">
                            <label>Status</label>
                            <select name="bank_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="emi-field-group">
                            <label>Logo</label>
                            <div class="emi-logo-upload-wrap">
                                <input type="hidden" name="bank_logo" class="emi-logo-input">
                                <img src="" class="emi-logo-preview" style="display:none;">
                                <button type="button" class="button emi-upload-btn">Upload Image</button>
                            </div>
                        </div>
                    </div>

                    <div class="emi-plans-table-container">
                        <h4>Installment Plans</h4>
                        <table class="wp-list-table widefat striped emi-plans-table" id="custom-bank-plan-table">
                            <thead>
                                <tr>
                                    <th>Months</th>
                                    <th>Surcharge (%)</th>
                                    <th>Fixed Fee</th>
                                    <th>Active</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Template row for a new bank -->
                                <tr class="emi-plan-row">
                                    <td><input type="number" min="1" max="60" name="plans[0][months]" value="6" class="small-text"></td>
                                    <td><input type="number" step="0.01" name="plans[0][surcharge]" value="0"></td>
                                    <td><input type="number" step="0.01" name="plans[0][fixed_fee]" value="0"></td>
                                    <td>
                                        <label class="emi-toggle emi-toggle--small">
                                            <input type="checkbox" name="plans[0][active]" value="1" checked>
                                            <span class="emi-toggle__slider"></span>
                                        </label>
                                    </td>
                                    <td><button type="button" class="button button-link-delete emi-remove-plan">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="emi-plan-table-actions">
                            <button type="button" class="button emi-add-plan" data-target="#custom-bank-plan-table tbody">Add Month Plan</button>
                        </div>
                    </div>
                    
                    <div class="emi-bank-footer-actions">
                        <button type="button" class="button button-primary emi-save-bank">Create Bank</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JS Template for Table Row -->
<script type="text/html" id="tmpl-emi-plan-row">
    <tr class="emi-plan-row">
        <td><input type="number" min="1" max="60" name="plans[{{data.index}}][months]" value="12" class="small-text"></td>
        <td><input type="number" step="0.01" name="plans[{{data.index}}][surcharge]" value="0"></td>
        <td><input type="number" step="0.01" name="plans[{{data.index}}][fixed_fee]" value="0"></td>
        <td>
            <label class="emi-toggle emi-toggle--small">
                <input type="checkbox" name="plans[{{data.index}}][active]" value="1" checked>
                <span class="emi-toggle__slider"></span>
            </label>
        </td>
        <td><button type="button" class="button button-link-delete emi-remove-plan">Remove</button></td>
    </tr>
</script>