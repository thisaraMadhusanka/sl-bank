<?php
/**
 * Database Installer — creates custom tables on activation.
 *
 * @package EmiManager\Database
 */

namespace EmiManager\Database;

use EmiManager\Repositories\BankRepository;
use EmiManager\Repositories\PlanRepository;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Installer
 */
class Installer
{

    /**
     * Run the installation routine.
     *
     * Creates the wp_emi_banks and wp_emi_bank_plans tables
     * and seeds default global options.
     *
     * @return void
     */
    public function install(): void
    {
        $this->create_tables();
        $this->seed_options();
        $this->seed_banks();
    }

    /**
     * Create custom database tables.
     *
     * @return void
     */
    private function create_tables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $banks_table = $wpdb->prefix . 'emi_banks';
        $plans_table = $wpdb->prefix . 'emi_bank_plans';

        $sql = "CREATE TABLE {$banks_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            logo_url VARCHAR(500) DEFAULT '',
            logo_id BIGINT(20) UNSIGNED DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) {$charset_collate};

        CREATE TABLE {$plans_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bank_id BIGINT(20) UNSIGNED NOT NULL,
            months INT(11) NOT NULL,
            surcharge_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            fixed_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bank_id (bank_id),
            KEY is_active (is_active)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Store the DB version.
        update_option('emi_manager_db_version', EMI_MANAGER_VERSION);
    }

    /**
     * Seed default plugin options.
     *
     * @return void
     */
    private function seed_options(): void
    {
        $defaults = [
            'emi_manager_enabled' => '1',
            'emi_manager_rounding' => 'two_decimal',
            'emi_manager_tax_mode' => 'exclude',
            'emi_manager_terms_html' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Seed the default banks and their logos from assets/images.
     *
     * @return void
     */
    private function seed_banks(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'emi_banks';

        // Check if there are any banks already.
        $count = $wpdb->get_var("SELECT COUNT(id) FROM {$table}");

        if ((int)$count > 0) {
            return; // Already seeded.
        }

        $banks_to_seed = [
            ['name' => 'NTB Amex', 'logo' => 'bank-ntb-amex-logo.png'],
            ['name' => 'DFCC Bank', 'logo' => 'dfcc.png'],
            ['name' => 'Hatton National Bank', 'logo' => 'hnb.png'],
            ['name' => 'HSBC', 'logo' => 'hsbc-via-sampath-ipg.png'],
            ['name' => 'LOLC Finance', 'logo' => 'lolc-finance.png'],
            ['name' => 'Pan Asia Bank', 'logo' => 'pan-asia-bank.png'],
            ['name' => 'People\'s Bank', 'logo' => 'peoples-bank.png'],
            ['name' => 'Sampath Bank', 'logo' => 'sampath-bank.png'],
            ['name' => 'Seylan Bank', 'logo' => 'seylan-bank.png'],
        ];

        $bank_repo = new BankRepository();
        $plan_repo = new PlanRepository();

        foreach ($banks_to_seed as $index => $bank) {
            $logo_url = EMI_MANAGER_URL . 'assets/images/' . $bank['logo'];

            $bank_id = $bank_repo->insert([
                'name' => $bank['name'],
                'logo_url' => $logo_url,
                'logo_id' => 0, // No WP media ID, linked directly.
                'is_active' => 1,
                'sort_order' => $index,
            ]);

            if ($bank_id) {
                // Add default 3, 6, 12 month plans for the seeded bank.
                $plans = [
                    ['bank_id' => $bank_id, 'months' => 3, 'surcharge_percent' => 3.5, 'fixed_fee' => 0, 'is_active' => 1, 'sort_order' => 0],
                    ['bank_id' => $bank_id, 'months' => 6, 'surcharge_percent' => 5.0, 'fixed_fee' => 0, 'is_active' => 1, 'sort_order' => 1],
                    ['bank_id' => $bank_id, 'months' => 12, 'surcharge_percent' => 8.0, 'fixed_fee' => 0, 'is_active' => 1, 'sort_order' => 2],
                ];

                foreach ($plans as $plan) {
                    $plan_repo->insert($plan);
                }
            }
        }
    }
}
