<?php
namespace EmiManager\Admin;
use EmiManager\Services\BankService;

if (!defined('ABSPATH')) { exit; }

class AdminMenu {
    private $bank_service;

    public function __construct(BankService $bank_service) {
        $this->bank_service = $bank_service;
        $this->register_hooks();
    }

    private function register_hooks(): void {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_emi_save_global_settings', [$this, 'ajax_save_global_settings']);
        add_action('wp_ajax_emi_save_bank', [$this, 'ajax_save_bank']);
        add_action('wp_ajax_emi_delete_bank', [$this, 'ajax_delete_bank']);
        add_action('wp_ajax_emi_add_preset_bank', [$this, 'ajax_add_preset_bank']);
    }

    public function add_menu_page(): void {
        add_menu_page(__('EMI Manager', 'emi-manager'), __('EMI Manager', 'emi-manager'), 'manage_woocommerce', 'emi-manager', [$this, 'render_page'], 'dashicons-calculator', 56);
    }

    public function enqueue_assets(string $hook_suffix): void {
        if ('toplevel_page_emi-manager' !== $hook_suffix) { return; }
        wp_enqueue_media();
        wp_enqueue_style('emi-manager-admin', EMI_MANAGER_URL . 'assets/css/admin.css', [], EMI_MANAGER_VERSION);
        wp_enqueue_script('emi-manager-admin', EMI_MANAGER_URL . 'assets/js/admin.js', ['jquery', 'wp-util'], EMI_MANAGER_VERSION, true);
        wp_localize_script('emi-manager-admin', 'emiManagerAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('emi_manager_admin'),
            'strings' => [
                'saved' => __('Settings saved successfully.', 'emi-manager'),
                'error' => __('An error occurred. Please try again.', 'emi-manager'),
                'confirmDelete' => __('Are you sure you want to delete this bank?', 'emi-manager'),
            ],
        ]);
    }

    public function render_page(): void {
        if (!current_user_can('manage_woocommerce')) { wp_die(esc_html__('You do not have permission to access this page.', 'emi-manager')); }
        include EMI_MANAGER_PATH . 'templates/admin/settings-page.php';
    }

    private function verify_ajax(): void {
        check_ajax_referer('emi_manager_admin', 'nonce');
        if (!current_user_can('manage_woocommerce')) { wp_send_json_error(['message' => __('Unauthorized.', 'emi-manager')]); }
    }

    public function ajax_save_global_settings(): void {
        $this->verify_ajax();
        
        $settings = [
            'emi_manager_enabled' => sanitize_text_field(wp_unslash($_POST['enabled'] ?? '0')),
            'emi_manager_rounding' => sanitize_text_field(wp_unslash($_POST['rounding'] ?? 'two_decimal')),
            'emi_manager_tax_mode' => sanitize_text_field(wp_unslash($_POST['tax_mode'] ?? 'exclude')),
            'emi_manager_terms_html' => wp_kses_post(wp_unslash($_POST['terms_html'] ?? '')),
        ];

        $this->bank_service->save_settings($settings);
        wp_send_json_success(['message' => __('Global settings saved.', 'emi-manager')]);
    }

    public function ajax_save_bank(): void {
        $this->verify_ajax();

        $bank_id = isset($_POST['bank_id']) ? (int) $_POST['bank_id'] : 0;
        
        $data = [
            'name' => sanitize_text_field(wp_unslash($_POST['bank_name'] ?? '')),
            'logo_url' => esc_url_raw(wp_unslash($_POST['bank_logo'] ?? '')),
            'is_active' => sanitize_text_field(wp_unslash($_POST['bank_active'] ?? '1')) === '1' ? 1 : 0,
        ];

        if (empty($data['name'])) {
            wp_send_json_error(['message' => __('Bank name is required.', 'emi-manager')]);
        }

        $plans_data = isset($_POST['plans']) && is_array($_POST['plans']) ? $_POST['plans'] : [];
        $plans = [];
        $sort = 0;
        foreach ($plans_data as $p) {
            $plans[] = [
                'months' => isset($p['months']) ? (int) $p['months'] : 1,
                'surcharge_percent' => isset($p['surcharge']) ? (float) $p['surcharge'] : 0.0,
                'fixed_fee' => isset($p['fixed_fee']) ? (float) $p['fixed_fee'] : 0.0,
                'is_active' => isset($p['active']) && $p['active'] === '1' ? 1 : 0,
                'sort_order' => $sort++
            ];
        }

        if ($bank_id > 0) {
            $this->bank_service->update_bank($bank_id, $data, $plans);
            wp_send_json_success(['message' => __('Bank updated.', 'emi-manager'), 'bank_id' => $bank_id]);
        } else {
            $data['sort_order'] = 99; // append to end
            $new_id = $this->bank_service->create_bank($data, $plans);
            if ($new_id) {
                wp_send_json_success(['message' => __('Bank created!', 'emi-manager'), 'bank_id' => $new_id]);
            } else {
                wp_send_json_error(['message' => __('Failed to create bank.', 'emi-manager')]);
            }
        }
    }

    public function ajax_delete_bank(): void {
        $this->verify_ajax();
        $bank_id = isset($_POST['bank_id']) ? (int) $_POST['bank_id'] : 0;
        if ($bank_id > 0) {
            $this->bank_service->delete_bank($bank_id);
            wp_send_json_success(['message' => __('Bank deleted.', 'emi-manager')]);
        }
        wp_send_json_error(['message' => __('Invalid bank ID.', 'emi-manager')]);
    }

    public function ajax_add_preset_bank(): void {
        $this->verify_ajax();
        
        $preset = sanitize_text_field(wp_unslash($_POST['preset'] ?? ''));
        if (empty($preset)) {
            wp_send_json_error(['message' => __('Preset ID missing.', 'emi-manager')]);
        }

        $presets = [
            'boc' => ['name' => 'Bank of Ceylon', 'logo' => 'boc.png'],
            'combank' => ['name' => 'Commercial Bank', 'logo' => 'combank.png'],
            'dfcc' => ['name' => 'DFCC Bank', 'logo' => 'dfcc.png'],
            'hnb' => ['name' => 'Hatton National Bank', 'logo' => 'hnb.png'],
            'hsbc' => ['name' => 'HSBC', 'logo' => 'hsbc-via-sampath-ipg.png'],
            'lolc' => ['name' => 'LOLC Finance', 'logo' => 'lolc-finance.png'],
            'ndb' => ['name' => 'NDB Bank', 'logo' => 'ndb.png'],
            'ntb' => ['name' => 'Nations Trust Bank', 'logo' => 'bank-ntb-amex-logo.png'],
            'panasia' => ['name' => 'Pan Asia Bank', 'logo' => 'pan-asia-bank.png'],
            'peoples' => ['name' => 'People\'s Bank', 'logo' => 'peoples-bank.png'],
            'sampath' => ['name' => 'Sampath Bank', 'logo' => 'sampath-bank.png'],
            'seylan' => ['name' => 'Seylan Bank', 'logo' => 'seylan-bank.png'],
        ];

        if (!isset($presets[$preset])) {
            wp_send_json_error(['message' => __('Unknown preset.', 'emi-manager')]);
        }

        $bank_info = $presets[$preset];
        $logo_url = EMI_MANAGER_URL . 'assets/images/' . $bank_info['logo'];
        
        $data = [
            'name' => $bank_info['name'],
            'logo_url' => $logo_url,
            'is_active' => 1,
            'sort_order' => 99,
        ];
        
        $plans = [
            ['months' => 3, 'surcharge_percent' => 0.0, 'fixed_fee' => 0.0, 'is_active' => 1, 'sort_order' => 0],
            ['months' => 6, 'surcharge_percent' => 0.0, 'fixed_fee' => 0.0, 'is_active' => 1, 'sort_order' => 1],
            ['months' => 12, 'surcharge_percent' => 0.0, 'fixed_fee' => 0.0, 'is_active' => 1, 'sort_order' => 2],
            ['months' => 24, 'surcharge_percent' => 0.0, 'fixed_fee' => 0.0, 'is_active' => 1, 'sort_order' => 3],
        ];

        $new_id = $this->bank_service->create_bank($data, $plans);
        if ($new_id) {
            wp_send_json_success(['message' => __('Preset Bank added!', 'emi-manager'), 'bank_id' => $new_id]);
        } else {
            wp_send_json_error(['message' => __('Failed to add preset bank.', 'emi-manager')]);
        }
    }
}