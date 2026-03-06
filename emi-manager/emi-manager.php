<?php
/**
 * Plugin Name: Sri Lanka EMI Manager for WooCommerce
 * Plugin URI:  
 * Description: Sri Lanka EMI Manager for WooCommerce allows store owners to display Sri Lankan bank installment (EMI) plans on WooCommerce product pages. Configure banks, installment months, and fees, and automatically calculate monthly payments based on product price.
 * Version:     1.0.0
 * Author:      Thisara madhusanka
 * Author URI:  
 * Text Domain: emi-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.1
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 *
 * @package EmiManager
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('EMI_MANAGER_VERSION', '1.0.0');
define('EMI_MANAGER_FILE', __FILE__);
define('EMI_MANAGER_PATH', plugin_dir_path(__FILE__));
define('EMI_MANAGER_URL', plugin_dir_url(__FILE__));

/**
 * Manually require all class files.
 * This is more reliable than an autoloader across different server environments.
 */
require_once EMI_MANAGER_PATH . 'includes/Core/Autoloader.php';
require_once EMI_MANAGER_PATH . 'includes/Repositories/BankRepository.php';
require_once EMI_MANAGER_PATH . 'includes/Repositories/PlanRepository.php';
require_once EMI_MANAGER_PATH . 'includes/Database/Installer.php';
require_once EMI_MANAGER_PATH . 'includes/Services/BankService.php';
require_once EMI_MANAGER_PATH . 'includes/Services/EmiCalculator.php';
require_once EMI_MANAGER_PATH . 'includes/Admin/AdminMenu.php';
require_once EMI_MANAGER_PATH . 'includes/Admin/ProductMetaBox.php';
require_once EMI_MANAGER_PATH . 'includes/Frontend/ProductDisplay.php';
require_once EMI_MANAGER_PATH . 'includes/API/RestController.php';
require_once EMI_MANAGER_PATH . 'includes/Core/Plugin.php';

/**
 * Activation hook.
 */
register_activation_hook(__FILE__, function (): void {
    // Flush rewrite rules for REST API.
    flush_rewrite_rules();
});

/**
 * Declare WooCommerce HPOS compatibility.
 */
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', EMI_MANAGER_FILE, true);
    }
});

/**
 * Deactivation hook.
 */
register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});

/**
 * Initialize the plugin.
 */
add_action('plugins_loaded', function (): void {
    \EmiManager\Core\Plugin::get_instance();
});