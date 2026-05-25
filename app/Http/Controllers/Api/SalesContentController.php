<?php

namespace Modules\ACP_Sales_Guide\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ACP_Sales_Guide\Models\SalesContent;
use Modules\ACP_Sales_Guide\Models\Template;

class SalesContentController extends Controller
{
    private const MANAGED_TEMPLATES = ['intro', 'sample', 'estimate', 'followup'];

    public function index()
    {
        $this->seedDefaultSettingsIfEmpty();

        return response()->json([
            'settings'  => SalesContent::orderBy('sort_order')->get(),
            'templates' => Template::whereIn('name', self::MANAGED_TEMPLATES)
                ->orderByRaw("FIELD(name, 'intro', 'sample', 'estimate', 'followup')")
                ->get(),
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'settings'            => 'required|array',
            'settings.*.key'      => 'required|string',
            'settings.*.value'    => 'nullable|string',
            'templates'           => 'required|array',
            'templates.*.id'      => 'required|integer',
            'templates.*.content' => 'nullable|string',
        ]);

        foreach ($request->settings as $item) {
            SalesContent::where('key', $item['key'])->update(['value' => $item['value'] ?: null]);
        }

        foreach ($request->templates as $item) {
            Template::where('id', $item['id'])->update(['content' => $item['content'] ?? '']);
        }

        return response()->json(['message' => 'Sales content saved.']);
    }

    /**
     * Seed default settings rows on fresh install.
     */
    private function seedDefaultSettingsIfEmpty(): void
    {
        if (SalesContent::count() > 0) return;

        $defaults = [
            ['key' => 'call_script',         'label' => 'Call Script',         'input_type' => 'textarea', 'value' => null, 'sort_order' => 1],
            ['key' => 'website_link',        'label' => 'Website Link',        'input_type' => 'url',      'value' => null, 'sort_order' => 2],
            ['key' => 'pdf_portfolio_link',  'label' => 'PDF Portfolio Link',  'input_type' => 'url',      'value' => null, 'sort_order' => 3],
            ['key' => 'client_review_link',  'label' => 'Client Review Link',  'input_type' => 'url',      'value' => null, 'sort_order' => 4],
        ];

        foreach ($defaults as $row) {
            SalesContent::create($row);
        }
    }
}
