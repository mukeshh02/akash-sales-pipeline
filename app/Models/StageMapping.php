<?php

namespace Modules\AkashSalesPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;

class StageMapping extends Model
{
    protected $table = 'akash_stage_mappings';

    protected $fillable = [
        'pipeline_id',
        'stage_id',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    /**
     * Find the tool configuration for a given CRM stage ID.
     */
    public static function findConfigForStage(int $stageId): ?array
    {
        $mapping = static::where('stage_id', $stageId)->first();

        return $mapping?->config;
    }
}
