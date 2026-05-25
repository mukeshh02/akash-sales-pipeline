<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akash_stage_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pipeline_id');
            $table->unsignedBigInteger('stage_id');
            $table->string('sales_step');
            $table->timestamps();

            $table->unique('stage_id'); // one CRM stage → one Akash step
            $table->index('pipeline_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akash_stage_mappings');
    }
};
