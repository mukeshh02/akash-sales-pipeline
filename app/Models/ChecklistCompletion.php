<?php

namespace Modules\AkashSalesPipeline\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistCompletion extends Model
{
    protected $table = 'akash_checklist_completions';

    protected $fillable = [
        'deal_id',
        'stage_id',
        'item_key',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'completed_by');
    }
}
