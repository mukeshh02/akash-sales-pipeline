<?php

namespace Modules\AkashSalesPipeline\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AkashSalesPipeline\Models\StageMapping;
use Modules\Deals\Models\Pipeline;

class StageMappingController extends Controller
{
    /**
     * Return all pipelines with their stages and current mappings.
     */
    public function index()
    {
        $pipelines = Pipeline::with(['stages' => function ($query) {
            $query->orderBy('display_order')->orderBy('id');
        }])->get();

        $mappings = StageMapping::all()->keyBy('stage_id');

        $data = $pipelines->map(function ($pipeline) use ($mappings) {
            return [
                'id'   => $pipeline->id,
                'name' => $pipeline->name,
                'stages' => $pipeline->stages->map(function ($stage) use ($mappings) {
                    $mapping = $mappings->get($stage->id);
                    return [
                        'id'     => $stage->id,
                        'name'   => $stage->name,
                        'config' => $mapping ? $mapping->config : null,
                    ];
                }),
            ];
        });

        return response()->json([
            'pipelines'   => $data,
            'templates'   => \Modules\AkashSalesPipeline\Models\Template::where('is_active', true)->get(['name']),
        ]);
    }

    /**
     * Bulk save stage mappings.
     * Expects: { mappings: [ { stage_id, pipeline_id, sales_step }, ... ] }
     * Send sales_step as null/empty to clear a mapping.
     */
    public function save(Request $request)
    {
        $request->validate([
            'mappings'                  => 'required|array',
            'mappings.*.stage_id'       => 'required|integer',
            'mappings.*.pipeline_id'    => 'required|integer',
            'mappings.*.sales_step'     => 'nullable|string',
        ]);

        foreach ($request->mappings as $item) {
            $stageId = $item['stage_id'];
            $config  = $item['config'] ?? null;

            if (empty($config)) {
                StageMapping::where('stage_id', $stageId)->delete();
            } else {
                StageMapping::updateOrCreate(
                    ['stage_id' => $stageId],
                    [
                        'pipeline_id' => $item['pipeline_id'],
                        'config'      => $config,
                    ]
                );
            }
        }

        return response()->json(['message' => 'Mappings saved successfully.']);
    }
}
