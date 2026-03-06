<?php
/**
 * EMI Manager — Uninstall
 *
 * Fired when the plugin is deleted from WordPress admin.
 * Cleans up all database tables, options, and post meta.
 *
 * @package EmiManager
 */

// If uninstall not called from WordPress, abort.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * Drop custom tables.
 */
$tables = [
    $wpdb->prefix . 'emi_banks',
    $wpdb->prefix . 'emi_bank_plans',
];

foreach ($tables as $table) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

/**
 * Delete plugin options.
 */
$options = [
    'emi_manager_enabled',
    'emi_manager_rounding',
    'emi_manager_tax_mode',
    'emi_manager_terms_html',
    'emi_manager_db_version',
];

foreach ($options as $option) {
    delete_option($option);
}

/**
 * Delete all product meta created by the plugin.
 */
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_emi_mode', '_emi_allowed_banks', '_emi_surcharge_override')");

/**
 * Delete transients.
 */
delete_transient('emi_manager_active_banks_plans');

/**
 * Flush rewrite rules.
 */
flush_rewrite_rules();
