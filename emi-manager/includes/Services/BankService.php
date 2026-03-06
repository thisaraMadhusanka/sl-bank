<?php
namespace EmiManager\Services;
use EmiManager\Repositories\BankRepository;
use EmiManager\Repositories\PlanRepository;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class BankService {
    private $bank_repo;
    private $plan_repo;
    private const CACHE_TTL = 3600;

    public function __construct( BankRepository $bank_repo, PlanRepository $plan_repo ) {
        $this->bank_repo = $bank_repo;
        $this->plan_repo = $plan_repo;
    }

    public function is_enabled(): bool { return '1' === get_option( 'emi_manager_enabled', '1' ); }
    public function get_rounding_rule(): string { return get_option( 'emi_manager_rounding', 'two_decimal' ); }
    public function get_tax_mode(): string { return get_option( 'emi_manager_tax_mode', 'exclude' ); }
    public function get_terms_html(): string { return get_option( 'emi_manager_terms_html', '' ); }

    public function get_active_banks_with_plans(): array {
        $cache_key = 'emi_manager_active_banks_plans';
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) { return $cached; }
        $banks = $this->bank_repo->get_all( true );
        foreach ( $banks as &$bank ) { $bank['plans'] = $this->plan_repo->get_by_bank( (int) $bank['id'], true ); }
        unset( $bank );
        set_transient( $cache_key, $banks, self::CACHE_TTL );
        return $banks;
    }

    public function get_all_banks(): array {
        $banks = $this->bank_repo->get_all( false );
        foreach ( $banks as &$bank ) { $bank['plans'] = $this->plan_repo->get_by_bank( (int) $bank['id'], false ); }
        unset( $bank );
        return $banks;
    }

    public function get_bank( int $bank_id ): ?array {
        $bank = $this->bank_repo->get_by_id( $bank_id );
        if ( null === $bank ) { return null; }
        $bank['plans'] = $this->plan_repo->get_by_bank( $bank_id, false );
        return $bank;
    }

    public function create_bank( array $data, array $plans = [] ) {
        $bank_id = $this->bank_repo->insert( $data );
        if ( ! $bank_id ) { return false; }
        if ( ! empty( $plans ) ) { $this->plan_repo->bulk_replace( $bank_id, $plans ); }
        $this->flush_cache();
        return $bank_id;
    }

    public function update_bank( int $bank_id, array $data, array $plans = [] ): bool {
        $updated = $this->bank_repo->update( $bank_id, $data );
        if ( is_array( $plans ) ) {
            $this->plan_repo->bulk_replace( $bank_id, $plans );
            $updated = true;
        }
        $this->flush_cache();
        return $updated;
    }

    public function delete_bank( int $bank_id ): bool {
        $this->plan_repo->delete_by_bank( $bank_id );
        $deleted = $this->bank_repo->delete( $bank_id );
        $this->flush_cache();
        return $deleted;
    }

    public function save_settings( array $settings ): void {
        $allowed = [ 'emi_manager_enabled', 'emi_manager_rounding', 'emi_manager_tax_mode', 'emi_manager_terms_html' ];
        foreach ( $allowed as $key ) {
            if ( isset( $settings[ $key ] ) ) {
                if ( 'emi_manager_terms_html' === $key ) { update_option( $key, wp_kses_post( $settings[ $key ] ) ); }
                else { update_option( $key, sanitize_text_field( $settings[ $key ] ) ); }
            }
        }
        $this->flush_cache();
    }

    public function get_banks_for_product( int $product_id ): array {
        $emi_mode = get_post_meta( $product_id, '_emi_mode', true );
        if ( 'disabled' === $emi_mode ) { return []; }
        $all_banks = $this->get_active_banks_with_plans();
        if ( empty( $emi_mode ) || 'global' === $emi_mode ) { return $all_banks; }
        if ( 'custom' === $emi_mode ) {
            $allowed_bank_ids = get_post_meta( $product_id, '_emi_allowed_banks', true );
            if ( empty( $allowed_bank_ids ) || ! is_array( $allowed_bank_ids ) ) { return $all_banks; }
            $allowed_bank_ids = array_map( 'intval', $allowed_bank_ids );
            return array_filter( $all_banks, function ( array $bank ) use ( $allowed_bank_ids ): bool {
                return in_array( (int) $bank['id'], $allowed_bank_ids, true );
            } );
        }
        return $all_banks;
    }

    public function get_product_surcharge_override( int $product_id ): ?float {
        $override = get_post_meta( $product_id, '_emi_surcharge_override', true );
        if ( '' === $override || false === $override ) { return null; }
        return (float) $override;
    }

    public function flush_cache(): void { delete_transient( 'emi_manager_active_banks_plans' ); }
}