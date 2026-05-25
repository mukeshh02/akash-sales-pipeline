<?php

namespace Modules\ACP_Sales_Guide\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ACP_Sales_Guide\Models\StageMapping;
use Modules\ACP_Sales_Guide\Models\Template;

class StageMappingController extends Controller
{
    public function index()
    {
        // Resolve Pipeline model from config — no hard dependency
        $pipelineClass = config('acp_sales_guide.pipeline_model');

        try {
            $pipelines = $pipelineClass::with(['stages' => fn($q) => $q->orderBy('display_order')->orderBy('id')])->get();
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not load pipelines: ' . $e->getMessage()], 500);
        }

        $mappings = StageMapping::all()->keyBy('stage_id');

        $data = $pipelines->map(fn($pipeline) => [
            'id'     => $pipeline->id,
            'name'   => $pipeline->name,
            'stages' => $pipeline->stages->map(fn($stage) => [
                'id'     => $stage->id,
                'name'   => $stage->name,
                'config' => $mappings->get($stage->id)?->config,
            ]),
        ]);

        return response()->json([
            'pipelines' => $data,
            'templates' => Template::where('is_active', true)->get(['name']),
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'mappings'               => 'required|array',
            'mappings.*.stage_id'    => 'required|integer',
            'mappings.*.pipeline_id' => 'required|integer',
            'mappings.*.sales_step'  => 'nullable|string',
        ]);

        foreach ($request->mappings as $item) {
            $config = $item['config'] ?? null;

            if (empty($config)) {
                StageMapping::where('stage_id', $item['stage_id'])->delete();
            } else {
                StageMapping::updateOrCreate(
                    ['stage_id'    => $item['stage_id']],
                    ['pipeline_id' => $item['pipeline_id'], 'config' => $config]
                );
            }
        }

        return response()->json(['message' => 'Mappings saved.']);
    }
}
