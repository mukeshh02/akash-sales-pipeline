<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akash_sales_pipeline_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->string('stage_name');
            $table->enum('action_type', [
                'call', 'whatsapp', 'sample', 'estimate',
                'negotiation', 'followup', 'lost', 'closed',
            ]);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('deal_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akash_sales_pipeline_logs');
    }
};
