<?php
namespace EmiManager\Frontend;

use EmiManager\Services\BankService;
use EmiManager\Services\EmiCalculator;

if (!defined('ABSPATH')) {
    exit;
}

class ProductDisplay
{
    private $bank_service;
    private $calculator;

    public function __construct(BankService $bank_service, EmiCalculator $calculator)
    {
        $this->bank_service = $bank_service;
        $this->calculator = $calculator;
        $this->register_hooks();
    }

    private function register_hooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('woocommerce_single_product_summary', [$this, 'render_emi_section'], 25);
    }

    public function enqueue_assets(): void
    {
        if (!is_product() || !$this->bank_service->is_enabled()) {
            return;
        }

        wp_enqueue_style('emi-manager-frontend', EMI_MANAGER_URL . 'assets/css/frontend.css', [], EMI_MANAGER_VERSION);
        wp_enqueue_script('emi-manager-frontend', EMI_MANAGER_URL . 'assets/js/frontend.js', ['jquery'], EMI_MANAGER_VERSION, true);

        // Inject custom colors as CSS variables
        $border_color = get_option('emi_manager_border_color', '#5c2079');
        $hover_color = get_option('emi_manager_hover_color', '#9ca3af');
        $custom_css = ":root { --emi-border-active: {$border_color}; --emi-border-hover: {$hover_color}; }";
        wp_add_inline_style('emi-manager-frontend', $custom_css);

        global $product;
        if (!$product instanceof \WC_Product) {
            $product = wc_get_product(get_the_ID());
        }
        $price = $product ? $this->calculator->get_display_price($product) : 0;

        wp_localize_script('emi-manager-frontend', 'emiManagerFrontend', [
            'restUrl' => esc_url_raw(rest_url('emi/v1/')),
            'restNonce' => wp_create_nonce('wp_rest'),
            'productId' => get_the_ID(),
            'initialPrice' => $price,
            'currency' => get_woocommerce_currency_symbol(),
            'isVariable' => $product ? $product->is_type('variable') : false,
            'strings' => [
                'monthly' => __('Monthly Rs.', 'emi-manager'),
                'months' => __('Month(s)', 'emi-manager'),
                'fee' => __('Handling Fee (%)', 'emi-manager'),
                'approx' => __('(Approx)', 'emi-manager'),
                'total' => __('Total', 'emi-manager'),
            ],
        ]);
    }

    public function render_emi_section(): void
    {
        if (!$this->bank_service->is_enabled()) {
            return;
        }

        global $product;
        if (!$product instanceof \WC_Product) {
            return;
        }

        $product_id = $product->get_id();
        $banks = $this->bank_service->get_banks_for_product($product_id);

        if (empty($banks)) {
            return;
        }

        $price = $this->calculator->get_display_price($product);
        $terms_html = $this->bank_service->get_terms_html();

        $emi_data = [];
        if ($price > 0) {
            $emi_data = $this->calculator->calculate_for_product($price, $product_id);
        }

        include EMI_MANAGER_PATH . 'templates/frontend/emi-display.php';
    }
}