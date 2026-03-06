<?php
/**
 * Bank Repository — data access for the wp_emi_banks table.
 *
 * @package EmiManager\Repositories
 */

namespace EmiManager\Repositories;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BankRepository
 */
class BankRepository
{

    /**
     * Table name (without prefix).
     *
     * @var string
     */
    private const TABLE = 'emi_banks';

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
     * Get all banks.
     *
     * @param bool $active_only Whether to return only active banks.
     * @return array
     */
    public function get_all(bool $active_only = false): array
    {
        global $wpdb;

        $table = $this->table();
        $where = $active_only ? 'WHERE is_active = 1' : '';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, id ASC",
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Get a single bank by ID.
     *
     * @param int $bank_id Bank ID.
     * @return array|null
     */
    public function get_by_id(int $bank_id): ?array
    {
        global $wpdb;

        $table = $this->table();

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $bank_id),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Insert a new bank.
     *
     * @param array $data Bank data.
     * @return int|false Inserted ID or false on failure.
     */
    public function insert(array $data)
    {
        global $wpdb;

        $result = $wpdb->insert(
            $this->table(),
        [
            'name' => sanitize_text_field($data['name'] ?? ''),
            'logo_url' => esc_url_raw($data['logo_url'] ?? ''),
            'logo_id' => absint($data['logo_id'] ?? 0),
            'is_active' => absint($data['is_active'] ?? 1),
            'sort_order' => absint($data['sort_order'] ?? 0),
        ],
        ['%s', '%s', '%d', '%d', '%d']
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an existing bank.
     *
     * @param int   $bank_id Bank ID.
     * @param array $data    Updated data.
     * @return bool
     */
    public function update(int $bank_id, array $data): bool
    {
        global $wpdb;

        $update_data = [];
        $format = [];

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }

        if (isset($data['logo_url'])) {
            $update_data['logo_url'] = esc_url_raw($data['logo_url']);
            $format[] = '%s';
        }

        if (isset($data['logo_id'])) {
            $update_data['logo_id'] = absint($data['logo_id']);
            $format[] = '%d';
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
        ['id' => $bank_id],
            $format,
        ['%d']
        );

        return false !== $result;
    }

    /**
     * Delete a bank by ID.
     *
     * @param int $bank_id Bank ID.
     * @return bool
     */
    public function delete(int $bank_id): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table(),
        ['id' => $bank_id],
        ['%d']
        );

        return false !== $result;
    }

    /**
     * Get banks by a list of IDs.
     *
     * @param array $ids Array of bank IDs.
     * @return array
     */
    public function get_by_ids(array $ids): array
    {
        global $wpdb;

        if (empty($ids)) {
            return [];
        }

        $table = $this->table();
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id IN ({$placeholders}) AND is_active = 1 ORDER BY sort_order ASC",
            ...$ids
        ),
            ARRAY_A
        );

        return $results ?: [];
    }
}
