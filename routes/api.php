<?php

use Illuminate\Support\Facades\Route;
use Modules\ACP_Sales_Guide\Http\Controllers\Api\FollowupController;
use Modules\ACP_Sales_Guide\Http\Controllers\Api\SalesContentController;
use Modules\ACP_Sales_Guide\Http\Controllers\Api\SalesGuideController;
use Modules\ACP_Sales_Guide\Http\Controllers\Api\StageMappingController;
use Modules\ACP_Sales_Guide\Http\Controllers\Api\TemplateController;
use Modules\ACP_Sales_Guide\Http\Controllers\Api\TodayFollowupsController;

Route::middleware('auth:sanctum')->prefix('acp-sales-guide')->group(function () {

    // Sales Guide panel (per deal)
    Route::get('deals/{deal}/guide',                  [SalesGuideController::class, 'show']);
    Route::post('deals/{deal}/guide',                 [SalesGuideController::class, 'store']);
    Route::post('deals/{deal}/guide/checklist-toggle',[SalesGuideController::class, 'toggleChecklistItem']);
    Route::get('deals/{deal}/followup-whatsapp',      [SalesGuideController::class, 'followupWhatsapp']);
    Route::get('deals/{deal}/documents',              [SalesGuideController::class, 'dealDocuments']);

    // WhatsApp Templates
    Route::get('templates',           [TemplateController::class, 'index']);
    Route::put('templates/{template}',[TemplateController::class, 'update']);

    // Stage Mappings
    Route::get('stage-mappings', [StageMappingController::class, 'index']);
    Route::post('stage-mappings',[StageMappingController::class, 'save']);

    // Follow-ups (per deal)
    Route::get('deals/{deal}/followups',        [FollowupController::class, 'index']);
    Route::post('deals/{deal}/followups',       [FollowupController::class, 'store']);
    Route::put('followups/{followup}/done',     [FollowupController::class, 'markDone']);

    // Today's Follow-ups dashboard
    Route::get('followups/today',               [TodayFollowupsController::class, 'index']);
    Route::put('followups/{followup}/today-done',[TodayFollowupsController::class, 'markDone']);

    // Sales Content Setup
    Route::get('sales-content', [SalesContentController::class, 'index']);
    Route::post('sales-content',[SalesContentController::class, 'save']);
});
