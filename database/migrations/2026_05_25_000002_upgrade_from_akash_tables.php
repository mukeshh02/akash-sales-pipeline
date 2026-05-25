<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ACP Sales Guide — Upgrade Migration
 *
 * For users upgrading from the old "AkashSalesPipeline" module:
 * Copies data from akash_* tables → acp_* tables (without deleting old data).
 *
 * Safe to run multiple times — skips tables that have already been migrated.
 * Old akash_* tables are LEFT intact as backup. You can drop them manually later.
 *
 * Table mapping:
 *   akash_sales_pipeline_logs   → acp_sales_logs
 *   akash_sales_followups       → acp_followups
 *   akash_sales_templates       → acp_templates
 *   akash_stage_mappings        → acp_stage_mappings
 *   akash_sales_settings        → acp_settings
 *   akash_checklist_completions → acp_checklist_completions
 *   akash_sales_loss_reasons    → acp_loss_reasons
 */
return new class extends Migration
{
    private array $map = [
        'akash_sales_pipeline_logs'   => 'acp_sales_logs',
        'akash_sales_followups'       => 'acp_followups',
        'akash_sales_templates'       => 'acp_templates',
        'akash_stage_mappings'        => 'acp_stage_mappings',
        'akash_sales_settings'        => 'acp_settings',
        'akash_checklist_completions' => 'acp_checklist_completions',
        'akash_sales_loss_reasons'    => 'acp_loss_reasons',
    ];

    public function up(): void
    {
        foreach ($this->map as $oldTable => $newTable) {
            // Skip if old table doesn't exist (fresh install — nothing to migrate)
            if (!Schema::hasTable($oldTable)) continue;

            // Skip if new table already has data (already migrated)
            if (DB::table($newTable)->exists()) continue;

            // Copy all rows
            $rows = DB::table($oldTable)->get();

            if ($rows->isEmpty()) continue;

            $data = $rows->map(fn($row) => $this->transformRow($oldTable, (array) $row))->toArray();

            DB::table($newTable)->insert($data);

            logger()->info("[ACP_Sales_Guide] Migrated " . count($data) . " rows: {$oldTable} → {$newTable}");
        }
    }

    public function down(): void
    {
        // This migration only copies data — never deletes.
        // Rollback: clear acp_* tables (old akash_* data is still safe)
        foreach ($this->map as $oldTable => $newTable) {
            if (Schema::hasTable($newTable)) {
                DB::table($newTable)->truncate();
            }
        }
    }

    /**
     * Transform a row from old table format to new table format if needed.
     * Currently the column names are the same — this is a hook for future column renames.
     */
    private function transformRow(string $oldTable, array $row): array
    {
        // No column changes needed currently — schemas are identical
        return $row;
    }
};
