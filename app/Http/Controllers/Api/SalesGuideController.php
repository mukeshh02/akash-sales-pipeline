<?php

namespace Modules\ACP_Sales_Guide\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ACP_Sales_Guide\Models\ChecklistCompletion;
use Modules\ACP_Sales_Guide\Models\Followup;
use Modules\ACP_Sales_Guide\Models\SalesContent;
use Modules\ACP_Sales_Guide\Models\SalesLog;
use Modules\ACP_Sales_Guide\Models\StageMapping;
use Modules\ACP_Sales_Guide\Models\Template;

class SalesGuideController extends Controller
{
    /**
     * Resolve the Deal model from config — no hard CRM dependency.
     */
    private function findDeal(int $dealId)
    {
        $dealClass = config('acp_sales_guide.deal_model');
        return $dealClass::with(['contacts.phones', 'user', 'stage'])->findOrFail($dealId);
    }

    public function show(int $dealId)
    {
        $deal      = $this->findDeal($dealId);
        $stageName = $deal->stage?->name ?? 'Unknown';
        $config    = $deal->stage_id ? StageMapping::findConfigForStage($deal->stage_id) : null;
        $lastLog   = SalesLog::where('deal_id', $dealId)->latest()->first();

        return response()->json([
            'stage_name'       => $stageName,
            'is_mapped'        => $config !== null,
            'last_action'      => $lastLog?->action_type,
            'contact_phone'    => $this->getContactPhone($deal),
            'toolbox'          => $config ? $this->buildToolbox($config, $deal) : null,
            'followup_summary' => $this->getFollowupSummary($deal),
            'templates'        => Template::where('is_active', true)->get(['name']),
        ]);
    }

    public function store(Request $request, int $dealId)
    {
        $request->validate([
            'action_type' => 'required|string',
            'note'        => 'nullable|string|max:1000',
        ]);

        $dealClass = config('acp_sales_guide.deal_model');
        $deal      = $dealClass::with(['stage'])->findOrFail($dealId);
        $stageName = $deal->stage?->name ?? 'Unknown';

        SalesLog::create([
            'deal_id'     => $dealId,
            'stage_name'  => $stageName,
            'action_type' => $request->action_type,
            'note'        => $request->note,
            'created_by'  => auth()->id(),
        ]);

        return $this->show($dealId);
    }

    public function toggleChecklistItem(Request $request, int $dealId)
    {
        $request->validate([
            'item_key' => 'required|string',
            'stage_id' => 'required|integer',
        ]);

        $existing = ChecklistCompletion::where([
            'deal_id'  => $dealId,
            'stage_id' => $request->stage_id,
            'item_key' => $request->item_key,
        ])->first();

        if ($existing) {
            $existing->delete();
        } else {
            ChecklistCompletion::create([
                'deal_id'      => $dealId,
                'stage_id'     => $request->stage_id,
                'item_key'     => $request->item_key,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        }

        return $this->show($dealId);
    }

    public function followupWhatsapp(Request $request, int $dealId)
    {
        $deal         = $this->findDeal($dealId);
        $templateName = $request->query('template', 'followup');
        $template     = Template::where('name', $templateName)->where('is_active', true)->first();
        $phone        = $this->getContactPhone($deal);

        return response()->json([
            'phone'        => $phone,
            'has_phone'    => $phone !== '',
            'has_template' => $template !== null,
            'message'      => $template ? $this->replacePlaceholders($template->content, $deal) : '',
        ]);
    }

    public function dealDocuments(int $dealId)
    {
        $dealClass = config('acp_sales_guide.deal_model');

        try {
            $deal = $dealClass::with([
                'documents' => fn($q) => $q->with('type')->orderByDesc('updated_at'),
            ])->findOrFail($dealId);
        } catch (\Throwable $e) {
            return response()->json([]);
        }

        return response()->json(
            $deal->documents->map(fn($doc) => [
                'id'         => $doc->id,
                'title'      => $doc->title,
                'type'       => $doc->type?->name ?? 'Document',
                'status'     => $doc->status instanceof \BackedEnum ? $doc->status->value : (string) $doc->status,
                'public_url' => $doc->public_url,
                'updated_at' => $doc->updated_at->diffForHumans(),
            ])
        );
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function buildToolbox(array $config, $deal): array
    {
        $completions = ChecklistCompletion::where([
            'deal_id'  => $deal->id,
            'stage_id' => $deal->stage_id,
        ])->pluck('item_key')->toArray();

        $allTemplates = Template::where('is_active', true)->get();

        $checklist = [];
        foreach ($config['checklist'] ?? [] as $item) {
            $tpl = $allTemplates->firstWhere('name', $item);
            $checklist[] = [
                'key'          => $item,
                'is_completed' => in_array($item, $completions),
                'is_template'  => $tpl !== null,
                'message'      => $tpl ? $this->replacePlaceholders($tpl->content, $deal) : null,
            ];
        }

        $links = [];
        if (!empty($config['show_samples'])) {
            foreach ([
                'Website'        => 'website_link',
                'PDF Portfolio'  => 'pdf_portfolio_link',
                'Client Reviews' => 'client_review_link',
            ] as $label => $key) {
                $url = SalesContent::getValue($key);
                if ($url) $links[] = ['label' => $label, 'url' => $url];
            }
        }

        $message = null;
        if (!empty($config['whatsapp_template'])) {
            $tpl = Template::where('name', $config['whatsapp_template'])->where('is_active', true)->first();
            if ($tpl) $message = $this->replacePlaceholders($tpl->content, $deal);
        }

        return [
            'script'    => !empty($config['show_script']) ? SalesContent::getValue('call_script') : null,
            'links'     => $links,
            'message'   => $message,
            'documents' => !empty($config['show_documents']),
            'checklist' => $checklist,
        ];
    }

    private function replacePlaceholders(string $content, $deal): string
    {
        $contact = $deal->contacts->first();
        $user    = auth()->user();

        return str_replace(
            ['{name}', '{phone}', '{event_date}', '{event_type}', '{budget}', '{package}', '{salesman_name}', '{company_name}'],
            [
                $contact?->name ?? 'Customer',
                $this->getContactPhone($deal),
                $deal->expected_close_date?->format('d-M-Y') ?? '',
                '',
                $deal->amount ? number_format($deal->amount, 2) : '',
                '',
                $user?->name ?? '',
                settings('company_name') ?: 'Our Company',
            ],
            $content
        );
    }

    private function getFollowupSummary($deal): array
    {
        $next = Followup::where('deal_id', $deal->id)->pending()
            ->orderBy('followup_date')->orderBy('followup_time')->first();
        $doneCount = Followup::where('deal_id', $deal->id)->where('status', 'done')->count();

        return [
            'done_count'   => $doneCount,
            'has_upcoming' => $next !== null,
            'next'         => $next ? [
                'id'            => $next->id,
                'followup_date' => $next->followup_date->format('Y-m-d'),
                'followup_time' => $next->followup_time ? substr($next->followup_time, 0, 5) : null,
                'display_dt'    => $next->formatted_date_time,
                'followup_type' => $next->followup_type,
                'note'          => $next->note,
            ] : null,
        ];
    }

    private function getContactPhone($deal): string
    {
        $contact = $deal->contacts->first();
        if (!$contact) return '';
        $phone = $contact->phones->first();
        return $phone ? preg_replace('/[^0-9]/', '', $phone->number) : '';
    }
}
