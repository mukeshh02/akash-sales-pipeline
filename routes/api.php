<?php

use Illuminate\Support\Facades\Route;

use Modules\AkashSalesPipeline\Http\Controllers\Api\FollowupController;
use Modules\AkashSalesPipeline\Http\Controllers\Api\SalesContentController;
use Modules\AkashSalesPipeline\Http\Controllers\Api\SalesGuideController;
use Modules\AkashSalesPipeline\Http\Controllers\Api\StageMappingController;
use Modules\AkashSalesPipeline\Http\Controllers\Api\TemplateController;
use Modules\AkashSalesPipeline\Http\Controllers\Api\TodayFollowupsController;

Route::middleware('auth:sanctum')->group(function () {

    // Sales Guide
    Route::get('/akash-sales-pipeline/deals/{deal}/guide',              [SalesGuideController::class, 'show']);
    Route::post('/akash-sales-pipeline/deals/{deal}/guide',             [SalesGuideController::class, 'store']);
    Route::post('/akash-sales-pipeline/deals/{deal}/guide/checklist-toggle', [SalesGuideController::class, 'toggleChecklistItem']);
    Route::get('/akash-sales-pipeline/deals/{deal}/followup-whatsapp',  [SalesGuideController::class, 'followupWhatsapp']);
    Route::get('/akash-sales-pipeline/deals/{deal}/documents',          [SalesGuideController::class, 'dealDocuments']);

    // WhatsApp Templates
    Route::get('/akash-sales-pipeline/templates',                [TemplateController::class, 'index']);
    Route::put('/akash-sales-pipeline/templates/{template}',     [TemplateController::class, 'update']);

    // Stage Mappings
    Route::get('/akash-sales-pipeline/stage-mappings',  [StageMappingController::class, 'index']);
    Route::post('/akash-sales-pipeline/stage-mappings', [StageMappingController::class, 'save']);

    // Follow-ups (per deal)
    Route::get('/akash-sales-pipeline/deals/{deal}/followups',       [FollowupController::class, 'index']);
    Route::post('/akash-sales-pipeline/deals/{deal}/followups',      [FollowupController::class, 'store']);
    Route::put('/akash-sales-pipeline/followups/{followup}/done',    [FollowupController::class, 'markDone']);

    // Today's Follow-ups dashboard
    Route::get('/akash-sales-pipeline/followups/today',              [TodayFollowupsController::class, 'index']);
    Route::put('/akash-sales-pipeline/followups/{followup}/today-done', [TodayFollowupsController::class, 'markDone']);

    // Sales Content Setup (call script, sample links, WhatsApp templates)
    Route::get('/akash-sales-pipeline/sales-content',  [SalesContentController::class, 'index']);
    Route::post('/akash-sales-pipeline/sales-content', [SalesContentController::class, 'save']);
});
