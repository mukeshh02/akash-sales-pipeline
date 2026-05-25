<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akash_stage_mappings', function (Blueprint $table) {
            // Add a JSON column for flexible tool configuration
            $table->json('config')->nullable()->after('sales_step');
        });

        // Migrate existing sales_step data to the new config format
        $mappings = DB::table('akash_stage_mappings')->get();
        foreach ($mappings as $mapping) {
            $config = [];
            switch ($mapping->sales_step) {
                case 'New Lead':
                    $config = ['show_script' => true];
                    break;
                case 'Contact Made':
                    $config = ['whatsapp_template' => 'intro'];
                    break;
                case 'Sample Shared':
                    $config = ['whatsapp_template' => 'sample', 'show_samples' => true];
                    break;
                case 'Estimate Shared':
                    $config = ['whatsapp_template' => 'estimate', 'show_documents' => true];
                    break;
                case 'Negotiation':
                case 'Follow-up':
                    $config = ['whatsapp_template' => 'followup'];
                    break;
            }

            DB::table('akash_stage_mappings')
                ->where('id', $mapping->id)
                ->update(['config' => json_encode($config)]);
        }

        Schema::table('akash_stage_mappings', function (Blueprint $table) {
            $table->dropColumn('sales_step');
        });
    }

    public function down(): void
    {
        Schema::table('akash_stage_mappings', function (Blueprint $table) {
            $table->string('sales_step')->nullable();
        });

        Schema::table('akash_stage_mappings', function (Blueprint $table) {
            $table->dropColumn('config');
        });
    }
};
