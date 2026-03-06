<?php
namespace EmiManager\Admin;
use EmiManager\Services\BankService;

if (!defined('ABSPATH')) { exit; }

class ProductMetaBox {
    private $bank_service;

    public function __construct(BankService $bank_service) {
        $this->bank_service = $bank_service;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('woocommerce_process_product_meta', [$this, 'save_meta']);
    }

    public function add_meta_box(): void {
        add_meta_box('emi-manager-product', __('EMI Settings', 'emi-manager'), [$this, 'render_meta_box'], 'product', 'side', 'default');
    }

    public function render_meta_box(\WP_Post $post): void {
        $product_id = $post->ID;
        $banks      = $this->bank_service->get_all_banks();
        $emi_mode   = get_post_meta($product_id, '_emi_mode', true);
        $allowed    = get_post_meta($product_id, '_emi_allowed_banks', true);
        $override   = get_post_meta($product_id, '_emi_surcharge_override', true);

        if (empty($emi_mode)) { $emi_mode = 'global'; }
        if (!is_array($allowed)) { $allowed = []; }

        wp_nonce_field('emi_manager_product_meta', '_emi_manager_nonce');
        include EMI_MANAGER_PATH . 'templates/admin/product-metabox.php';
    }

    public function save_meta(int $product_id): void {
        if (!isset($_POST['_emi_manager_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_emi_manager_nonce'])), 'emi_manager_product_meta')) { return; }
        if (!current_user_can('edit_post', $product_id)) { return; }

        $emi_mode = sanitize_text_field(wp_unslash($_POST['_emi_mode'] ?? 'global'));
        $valid_modes = ['global', 'custom', 'disabled'];
        if (!in_array($emi_mode, $valid_modes, true)) { $emi_mode = 'global'; }
        update_post_meta($product_id, '_emi_mode', $emi_mode);

        $allowed_banks = isset($_POST['_emi_allowed_banks']) ? array_map('sanitize_key', (array) $_POST['_emi_allowed_banks']) : [];
        update_post_meta($product_id, '_emi_allowed_banks', $allowed_banks);

        $surcharge_override = sanitize_text_field(wp_unslash($_POST['_emi_surcharge_override'] ?? ''));
        if ('' !== $surcharge_override) { update_post_meta($product_id, '_emi_surcharge_override', (float) $surcharge_override); }
        else { delete_post_meta($product_id, '_emi_surcharge_override'); }
    }
}