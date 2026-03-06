<?php
namespace EmiManager\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class EmiCalculator {
    private $bank_service;

    public function __construct( BankService $bank_service ) {
        $this->bank_service = $bank_service;
    }

    public function calculate( float $base_price, array $plan, ?int $product_id = null ): array {
        $surcharge_percent = (float) ( $plan['surcharge_percent'] ?? 0 );
        $fixed_fee         = (float) ( $plan['fixed_fee'] ?? 0 );
        $months            = (int) ( $plan['months'] ?? 1 );

        if ( null !== $product_id ) {
            $override = $this->bank_service->get_product_surcharge_override( $product_id );
            if ( null !== $override ) { $surcharge_percent = $override; }
        }

        $total   = $base_price + ( $base_price * $surcharge_percent / 100 ) + $fixed_fee;
        $monthly = $months > 0 ? $total / $months : $total;

        $rounding = $this->bank_service->get_rounding_rule();
        $monthly  = $this->apply_rounding( $monthly, $rounding );
        $total    = $this->apply_rounding( $total, $rounding );

        return [ 'months' => $months, 'fee_pct' => $surcharge_percent, 'monthly' => $monthly, 'total' => $total ];
    }

    public function calculate_for_product( float $price, int $product_id, int $bank_id = 0 ): array {
        $banks  = $this->bank_service->get_banks_for_product( $product_id );
        $result = [];

        foreach ( $banks as $bank ) {
            if ( $bank_id > 0 && (int) $bank['id'] !== $bank_id ) { continue; }
            $bank_data = [ 'id' => (int) $bank['id'], 'name' => $bank['name'], 'logo_url' => $bank['logo_url'], 'plans' => [] ];
            if ( ! empty( $bank['plans'] ) ) {
                foreach ( $bank['plans'] as $plan ) { $bank_data['plans'][] = $this->calculate( $price, $plan, $product_id ); }
            }
            $result[] = $bank_data;
        }
        return $result;
    }

    public function get_display_price( \WC_Product $product, ?int $variation_id = null ): float {
        if ( $variation_id && $product->is_type( 'variable' ) ) {
            $variation = wc_get_product( $variation_id );
            if ( $variation ) { $product = $variation; }
        }
        $tax_mode = $this->bank_service->get_tax_mode();
        $args = [];
        if ( 'include' === $tax_mode ) {
            $args['qty'] = 1;
            $args['price'] = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
        } else {
            $args['price'] = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
        }
        return (float) wc_get_price_to_display( $product, $args );
    }

    private function apply_rounding( float $value, string $rule ): float {
        switch ( $rule ) {
            case 'nearest_whole': return (float) round( $value, 0 );
            case 'bankers': return (float) round( $value, 2, PHP_ROUND_HALF_EVEN );
            case 'two_decimal': default: return (float) round( $value, 2 );
        }
    }
}