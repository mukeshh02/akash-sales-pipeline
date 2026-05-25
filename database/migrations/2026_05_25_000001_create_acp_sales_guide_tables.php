<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ACP Sales Guide — Base Schema
 *
 * Safe migration: every table creation is guarded by Schema::hasTable()
 * so running this migration twice (or after an upgrade) never fails.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Sales Logs ────────────────────────────────────────────────
        if (!Schema::hasTable('acp_sales_logs')) {
            Schema::create('acp_sales_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('deal_id');
                $table->string('stage_name')->default('Unknown');
                $table->string('action_type');
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('deal_id');
            });
        }

        // ── Follow-ups ────────────────────────────────────────────────
        if (!Schema::hasTable('acp_followups')) {
            Schema::create('acp_followups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('deal_id');
                $table->date('followup_date');
                $table->time('followup_time')->nullable();
                $table->string('followup_type')->default('call');   // call | whatsapp
                $table->string('template_name')->nullable();
                $table->text('note')->nullable();
                $table->string('status')->default('pending');       // pending | done
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['deal_id', 'status']);
                $table->index('followup_date');
            });
        }

        // ── WhatsApp Templates ────────────────────────────────────────
        if (!Schema::hasTable('acp_templates')) {
            Schema::create('acp_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('content')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── Stage Mappings ────────────────────────────────────────────
        if (!Schema::hasTable('acp_stage_mappings')) {
            Schema::create('acp_stage_mappings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pipeline_id');
                $table->unsignedBigInteger('stage_id')->unique();
                $table->json('config')->nullable();
                $table->timestamps();
            });
        }

        // ── Settings (call script, links) ─────────────────────────────
        if (!Schema::hasTable('acp_settings')) {
            Schema::create('acp_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('label');
                $table->string('input_type')->default('text');
                $table->text('value')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // ── Checklist Completions ─────────────────────────────────────
        if (!Schema::hasTable('acp_checklist_completions')) {
            Schema::create('acp_checklist_completions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('deal_id');
                $table->unsignedBigInteger('stage_id');
                $table->string('item_key');
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('completed_by')->nullable();
                $table->timestamps();
                $table->unique(['deal_id', 'stage_id', 'item_key']);
            });
        }

        // ── Loss Reasons ──────────────────────────────────────────────
        if (!Schema::hasTable('acp_loss_reasons')) {
            Schema::create('acp_loss_reasons', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Drop in reverse dependency order
        Schema::dropIfExists('acp_checklist_completions');
        Schema::dropIfExists('acp_stage_mappings');
        Schema::dropIfExists('acp_followups');
        Schema::dropIfExists('acp_sales_logs');
        Schema::dropIfExists('acp_templates');
        Schema::dropIfExists('acp_settings');
        Schema::dropIfExists('acp_loss_reasons');
    }
};
