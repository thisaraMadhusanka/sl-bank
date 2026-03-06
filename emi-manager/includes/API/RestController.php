<?php
namespace EmiManager\API;
use EmiManager\Services\BankService;
use EmiManager\Services\EmiCalculator;

if (!defined('ABSPATH')) { exit; }

class RestController {
    private const NAMESPACE = 'emi/v1';
    private $calculator;
    private $bank_service;

    public function __construct(EmiCalculator $calculator, BankService $bank_service) {
        $this->calculator = $calculator;
        $this->bank_service = $bank_service;
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route(self::NAMESPACE, '/product/(?P<id>\d+)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'get_product_emi'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => ['required' => true, 'validate_callback' => function ($param): bool { return is_numeric($param) && (int)$param > 0; }, 'sanitize_callback' => 'absint'],
                'variation' => ['required' => false, 'default' => 0, 'sanitize_callback' => 'absint'],
                'bank' => ['required' => false, 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);
    }

    public function get_product_emi(\WP_REST_Request $request) {
        if (!$this->bank_service->is_enabled()) { return new \WP_Error('emi_disabled', __('EMI disabled.', 'emi-manager'), ['status' => 403]); }
        $product_id = $request->get_param('id');
        $variation_id = $request->get_param('variation');
        $bank_id = $request->get_param('bank');
        $product = wc_get_product($product_id);

        if (!$product) { return new \WP_Error('product_not_found', __('Not found.', 'emi-manager'), ['status' => 404]); }

        $price = $this->calculator->get_display_price($product, $variation_id ?: null);
        if ($price <= 0) {
            return new \WP_REST_Response(['product_id' => $product_id, 'price' => 0, 'currency' => get_woocommerce_currency(), 'banks' => []]);
        }

        $banks = $this->calculator->calculate_for_product($price, $product_id, $bank_id);
        return new \WP_REST_Response(['product_id' => $product_id, 'variation_id' => $variation_id, 'price' => $price, 'currency' => get_woocommerce_currency(), 'banks' => $banks]);
    }
}