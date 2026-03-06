<?php
if (!defined('ABSPATH')) { exit; }
if (empty($emi_data)) { return; }
?>

<div class="emi-section" id="emi-section" role="region" aria-label="Installment Plans">
    <h3 class="emi-section__title">
        <?php esc_html_e('Easy Installment Plans', 'emi-manager'); ?>
        <span class="emi-section__title-arrow"></span>
    </h3>

    <div class="emi-banks" id="emi-banks-container">
        <!-- Render Bank Cards -->
        <div class="emi-bank-cards">
            <?php foreach ($emi_data as $bank): 
                $bank_id = esc_attr($bank['id']);
                $has_logo = !empty($bank['logo_url']);
                $lowest = !empty($bank['plans']) ? min(array_column($bank['plans'], 'monthly')) : 0;
            ?>
                <button type="button" class="emi-bank-card" data-bank-id="<?php echo $bank_id; ?>" aria-expanded="false" aria-controls="emi-panel-<?php echo $bank_id; ?>">
                    <?php if ($has_logo): ?>
                        <img class="emi-bank-card__logo" src="<?php echo esc_url($bank['logo_url']); ?>" alt="<?php echo esc_attr($bank['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <span class="emi-bank-card__name"><?php echo esc_html($bank['name']); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($lowest > 0): ?>
                        <div class="emi-bank-card__preview">
                            <span class="emi-preview-label">Monthly</span>
                            <span class="emi-preview-currency">Rs.</span>
                            <span class="emi-preview-amount"><?php echo number_format($lowest, 0); ?></span>
                        </div>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Render Bank Panels (initially hidden) -->
        <div class="emi-panels-container">
            <?php foreach ($emi_data as $bank): 
                $bank_id = esc_attr($bank['id']);
            ?>
                <div class="emi-panel" id="emi-panel-<?php echo $bank_id; ?>" hidden>
                    <button type="button" class="emi-panel__close" aria-label="Close panel">&times;</button>
                    <h4 class="emi-panel__title"><?php echo esc_html($bank['name']); ?> Payment Plans</h4>
                    
                    <?php if (!empty($bank['plans'])): ?>
                        <table class="emi-table" role="table">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e('Month (s)', 'emi-manager'); ?></th>
                                    <th scope="col"><?php esc_html_e('Handling Fee (%)', 'emi-manager'); ?></th>
                                    <th scope="col"><?php esc_html_e('Monthly (Approx)', 'emi-manager'); ?></th>
                                    <th scope="col"><?php esc_html_e('Total (Approx)', 'emi-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bank['plans'] as $plan): ?>
                                    <tr>
                                        <td><?php echo esc_html($plan['months']); ?></td>
                                        <td><?php echo number_format($plan['fee_pct'], 2); ?>%</td>
                                        <td class="emi-table__monthly">
                                            Rs. <span class="emi-amount"><?php echo number_format($plan['monthly'], 0); ?></span>
                                        </td>
                                        <td class="emi-table__total">
                                            Rs. <span class="emi-amount"><?php echo number_format($plan['total'], 0); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="emi-panel__no-plans"><?php esc_html_e('No valid plans.', 'emi-manager'); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($terms_html)): ?>
                        <div class="emi-terms">
                            <strong>Terms & Condition</strong>
                            <div class="emi-terms-content">
                                <?php echo wp_kses_post($terms_html); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>