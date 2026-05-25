<?php

namespace Modules\AkashSalesPipeline\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AkashSalesPipeline\Models\SalesContent;
use Modules\AkashSalesPipeline\Models\Template;

class SalesContentController extends Controller
{
    /** Template names this page manages. */
    private const MANAGED_TEMPLATES = ['intro', 'sample', 'estimate', 'followup'];

    /**
     * Return all settings rows + the 4 managed templates.
     */
    public function index()
    {
        $settings = SalesContent::orderBy('sort_order')->get();

        $templates = Template::whereIn('name', self::MANAGED_TEMPLATES)
            ->orderByRaw("FIELD(name, 'intro', 'sample', 'estimate', 'followup')")
            ->get();

        return response()->json([
            'settings'  => $settings,
            'templates' => $templates,
        ]);
    }

    /**
     * Bulk-save settings rows and template contents in a single request.
     *
     * Payload:
     * {
     *   settings:  [{ key, value }],
     *   templates: [{ id, content }]
     * }
     */
    public function save(Request $request)
    {
        $request->validate([
            'settings'              => 'required|array',
            'settings.*.key'        => 'required|string',
            'settings.*.value'      => 'nullable|string',
            'templates'             => 'required|array',
            'templates.*.id'        => 'required|integer',
            'templates.*.content'   => 'nullable|string',
        ]);

        foreach ($request->settings as $item) {
            SalesContent::where('key', $item['key'])
                ->update(['value' => $item['value'] ?: null]);
        }

        foreach ($request->templates as $item) {
            Template::where('id', $item['id'])
                ->update(['content' => $item['content'] ?? '']);
        }

        return response()->json(['message' => 'Sales content saved successfully.']);
    }
}
