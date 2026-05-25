<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akash_sales_followups', function (Blueprint $table) {
            // Add time field for scheduling
            $table->time('followup_time')->nullable()->after('followup_date');

            // Make note optional (was NOT NULL in original migration)
            $table->text('note')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('akash_sales_followups', function (Blueprint $table) {
            $table->dropColumn('followup_time');
            $table->text('note')->nullable(false)->change();
        });
    }
};
