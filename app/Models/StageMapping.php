<?php

namespace Modules\ACP_Sales_Guide\Models;

use Illuminate\Database\Eloquent\Model;

class StageMapping extends Model
{
    protected $table = 'acp_stage_mappings';

    protected $fillable = ['pipeline_id', 'stage_id', 'config'];

    protected $casts = ['config' => 'array'];

    /**
     * Loose relationship — resolves model class from config,
     * so this works even if Pipeline is renamed in the CRM.
     */
    public function pipeline()
    {
        return $this->belongsTo(config('acp_sales_guide.pipeline_model'));
    }

    public function stage()
    {
        return $this->belongsTo(config('acp_sales_guide.stage_model'));
    }

    public static function findConfigForStage(int $stageId): ?array
    {
        return static::where('stage_id', $stageId)->value('config');
    }
}
