<?php

namespace Modules\ACP_Sales_Guide\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistCompletion extends Model
{
    protected $table = 'acp_checklist_completions';

    protected $fillable = ['deal_id', 'stage_id', 'item_key', 'completed_at', 'completed_by'];

    protected $casts = ['completed_at' => 'datetime'];
}
