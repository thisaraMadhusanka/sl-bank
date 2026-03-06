<?php
/**
 * Plan Repository — data access for the wp_emi_bank_plans table.
 *
 * @package EmiManager\Repositories
 */

namespace EmiManager\Repositories;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PlanRepository
 */
class PlanRepository
{

    /**
     * Table name (without prefix).
     *
     * @var string
     */
    private const TABLE = 'emi_bank_plans';

    /**
     * Get the full table name.
     *
     * @return string
     */
    private function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    /**
     * Get all plans for a bank.
     *
     * @param int  $bank_id     Bank ID.
     * @param bool $active_only Whether to return only active plans.
     * @return array
     */
    public function get_by_bank(int $bank_id, bool $active_only = false): array
    {
        global $wpdb;

        $table = $this->table();
        $where = $active_only
            ? $wpdb->prepare('WHERE bank_id = %d AND is_active = 1', $bank_id)
            : $wpdb->prepare('WHERE bank_id = %d', $bank_id);

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY months ASC",
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Get a single plan by ID.
     *
     * @param int $plan_id Plan ID.
     * @return array|null
     */
    public function get_by_id(int $plan_id): ?array
    {
        global $wpdb;

        $table = $this->table();

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $plan_id),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Insert a new plan.
     *
     * @param array $data Plan data.
     * @return int|false Inserted ID or false on failure.
     */
    public function insert(array $data)
    {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table(),
        [
            'bank_id' => absint($data['bank_id'] ?? 0),
            'months' => absint($data['months'] ?? 0),
            'surcharge_percent' => floatval($data['surcharge_percent'] ?? 0),
            'fixed_fee' => floatval($data['fixed_fee'] ?? 0),
            'is_active' => absint($data['is_active'] ?? 1),
            'sort_order' => absint($data['sort_order'] ?? 0),
        ],
        ['%d', '%d', '%f', '%f', '%d', '%d']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an existing plan.
     *
     * @param int   $plan_id Plan ID.
     * @param array $data    Updated data.
     * @return bool
     */
    public function update(int $plan_id, array $data): bool
    {
        global $wpdb;

        $update_data = [];
        $format = [];

        if (isset($data['months'])) {
            $update_data['months'] = absint($data['months']);
            $format[] = '%d';
        }

        if (isset($data['surcharge_percent'])) {
            $update_data['surcharge_percent'] = floatval($data['surcharge_percent']);
            $format[] = '%f';
        }

        if (isset($data['fixed_fee'])) {
            $update_data['fixed_fee'] = floatval($data['fixed_fee']);
            $format[] = '%f';
        }

        if (isset($data['is_active'])) {
            $update_data['is_active'] = absint($data['is_active']);
            $format[] = '%d';
        }

        if (isset($data['sort_order'])) {
            $update_data['sort_order'] = absint($data['sort_order']);
            $format[] = '%d';
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update(
            $this->table(),
            $update_data,
        ['id' => $plan_id],
            $format,
        ['%d']
        );

        return false !== $result;
    }

    /**
     * Delete a plan by ID.
     *
     * @param int $plan_id Plan ID.
     * @return bool
     */
    public function delete(int $plan_id): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table(),
        ['id' => $plan_id],
        ['%d']
        );

        return false !== $result;
    }

    /**
     * Delete all plans for a bank.
     *
     * @param int $bank_id Bank ID.
     * @return bool
     */
    public function delete_by_bank(int $bank_id): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table(),
        ['bank_id' => $bank_id],
        ['%d']
        );

        return false !== $result;
    }

    /**
     * Bulk replace plans for a bank.
     *
     * Deletes existing plans and inserts the new set.
     *
     * @param int   $bank_id Bank ID.
     * @param array $plans   Array of plan data arrays.
     * @return bool
     */
    public function bulk_replace(int $bank_id, array $plans): bool
    {
        $this->delete_by_bank($bank_id);

        foreach ($plans as $index => $plan) {
            $plan['bank_id'] = $bank_id;
            $plan['sort_order'] = $index;

            $inserted = $this->insert($plan);
            if (false === $inserted) {
                return false;
            }
        }

        return true;
    }
}
