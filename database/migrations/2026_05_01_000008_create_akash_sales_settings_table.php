<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akash_sales_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label');
            $table->string('input_type')->default('text'); // text | textarea | url
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default rows — admin fills values via the Settings page
        $now = now();
        DB::table('akash_sales_settings')->insertOrIgnore([
            [
                'key'        => 'call_script',
                'label'      => 'Call Script',
                'input_type' => 'textarea',
                'sort_order' => 1,
                'value'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'website_link',
                'label'      => 'Website Link',
                'input_type' => 'url',
                'sort_order' => 2,
                'value'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'pdf_portfolio_link',
                'label'      => 'PDF Portfolio Link',
                'input_type' => 'url',
                'sort_order' => 3,
                'value'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'client_review_link',
                'label'      => 'Client Review Link',
                'input_type' => 'url',
                'sort_order' => 4,
                'value'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('akash_sales_settings');
    }
};
