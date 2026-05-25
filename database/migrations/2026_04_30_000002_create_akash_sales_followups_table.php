<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akash_sales_followups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id');
            $table->date('followup_date');
            $table->enum('followup_type', ['call', 'whatsapp']);
            $table->text('note');
            $table->enum('status', ['pending', 'done'])->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('deal_id');
            $table->index('followup_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akash_sales_followups');
    }
};
