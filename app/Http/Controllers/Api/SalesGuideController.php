<?php

namespace Modules\AkashSalesPipeline\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AkashSalesPipeline\Models\Followup;
use Modules\AkashSalesPipeline\Models\SalesContent;
use Modules\AkashSalesPipeline\Models\SalesLog;
use Modules\AkashSalesPipeline\Models\StageMapping;
use Modules\AkashSalesPipeline\Models\Template;
use Modules\Deals\Models\Deal;

class SalesGuideController extends Controller
{
    /**
     * Get the sales guide data for a specific deal.
     *
     * The CRM stage is the single source of truth.
     * We never fall back to log entries to determine the current step.
     */
    public function show(int $dealId)
    {
        $deal = Deal::with(['contacts.phones', 'user', 'stage'])->findOrFail($dealId);

        // Actual CRM Stage Name is the source of truth
        $stageName = $deal->stage?->name ?? 'Unknown';

        // Load tools configuration for this stage
        $config = $deal->stage_id ? StageMapping::findConfigForStage($deal->stage_id) : null;

        $lastLog = SalesLog::where('deal_id', $dealId)
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'stage_name'       => $stageName,
            'is_mapped'        => $config !== null,
            'last_action'      => $lastLog?->action_type,
            'contact_phone'    => $this->getContactPhone($deal),
            'toolbox'          => $config ? $this->getToolboxContent($config, $deal) : null,
            'followup_summary' => $this->getFollowupSummary($deal),
            'templates'        => Template::where('is_active', true)->get(['name']),
        ]);
    }

    /**
     * Toggle completion of a checklist item for a deal stage.
     */
    public function toggleChecklistItem(Request $request, int $dealId)
    {
        $request->validate([
            'item_key' => 'required|string',
            'stage_id' => 'required|integer',
        ]);

        $itemKey = $request->item_key;
        $stageId = $request->stage_id;

        $completion = \Modules\AkashSalesPipeline\Models\ChecklistCompletion::where([
            'deal_id'  => $dealId,
            'stage_id' => $stageId,
            'item_key' => $itemKey,
        ])->first();

        if ($completion) {
            $completion->delete();
        } else {
            \Modules\AkashSalesPipeline\Models\ChecklistCompletion::create([
                'deal_id'      => $dealId,
                'stage_id'     => $stageId,
                'item_key'     => $itemKey,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        }

        return $this->show($dealId);
    }

    /**
     * Log a sales activity for the deal.
     *
     * The frontend sends ONLY the action type (and an optional note).
     * The backend always determines the current stage from the CRM —
     * the frontend never controls what stage is recorded.
     */
    public function store(Request $request, int $dealId)
    {
        $request->validate([
            'action_type' => 'required|string',
            'note'        => 'nullable|string|max:1000',
        ]);

        $deal = Deal::with(['stage'])->findOrFail($dealId);
        
        // Always record the ACTUAL CRM stage name in the log
        $stageName = $deal->stage?->name ?? 'Unknown';

        SalesLog::create([
            'deal_id'    => $dealId,
            'stage_name' => $stageName,
            'action_type'=> $request->action_type,
            'note'       => $request->note,
            'created_by' => auth()->id(),
        ]);

        return $this->show($dealId);
    }

    /**
     * Return the follow-up WhatsApp template with placeholders replaced
     * for a specific deal, plus the cleaned contact phone number.
     */
    public function followupWhatsapp(Request $request, int $dealId)
    {
        $deal = Deal::with(['contacts.phones', 'user'])->findOrFail($dealId);

        $templateName = $request->query('template', 'followup');

        $template = Template::where('name', $templateName)
            ->where('is_active', true)
            ->first();

        $phone = $this->getContactPhone($deal);

        return response()->json([
            'phone'        => $phone,
            'has_phone'    => $phone !== '',
            'has_template' => $template !== null,
            'message'      => $template ? $this->replacePlaceholders($template->content, $deal) : '',
        ]);
    }

    /**
     * Return all documents associated with a deal so the Estimate Shared step
     * can display them with status, public share URL, and quick actions.
     */
    public function dealDocuments(int $dealId)
    {
        $deal = Deal::with([
            'documents' => fn ($q) => $q->with('type')->orderByDesc('updated_at'),
        ])->findOrFail($dealId);

        return response()->json(
            $deal->documents->map(fn ($doc) => [
                'id'         => $doc->id,
                'title'      => $doc->title,
                'type'       => $doc->type?->name ?? 'Document',
                'status'     => $doc->status instanceof \BackedEnum
                                    ? $doc->status->value
                                    : (string) $doc->status,
                'public_url' => $doc->public_url,
                'updated_at' => $doc->updated_at->diffForHumans(),
            ])
        );
    }

    /**
     * Build the toolbox content based on enabled tools in stage config.
     */
    protected function getToolboxContent(array $config, Deal $deal): array
    {
        $content = [
            'script'    => null,
            'links'     => [],
            'message'   => null,
            'documents' => false,
            'checklist' => [],
        ];

        // 0. Checklist & Sequences
        if (!empty($config['checklist']) && is_array($config['checklist'])) {
            $completions = \Modules\AkashSalesPipeline\Models\ChecklistCompletion::where([
                'deal_id'  => $deal->id,
                'stage_id' => $deal->stage_id,
            ])->pluck('item_key')->toArray();

            // Fetch all active templates to see which checklist items match
            $allTemplates = Template::where('is_active', true)->get();

            foreach ($config['checklist'] as $item) {
                $template = $allTemplates->firstWhere('name', $item);
                
                $content['checklist'][] = [
                    'key'          => $item,
                    'is_completed' => in_array($item, $completions),
                    'is_template'  => $template !== null,
                    'message'      => $template ? $this->replacePlaceholders($template->content, $deal) : null,
                ];
            }
        }

        // 1. Primary Call Script (if enabled separately)
        if (!empty($config['show_script'])) {
            $content['script'] = SalesContent::getValue('call_script');
        }

        // 2. WhatsApp Template
        if (!empty($config['whatsapp_template'])) {
            $tpl = Template::where('name', $config['whatsapp_template'])
                ->where('is_active', true)
                ->first();
            
            if ($tpl) {
                $content['message'] = $this->replacePlaceholders($tpl->content, $deal);
            }
        }

        // 3. Work Samples
        if (!empty($config['show_samples'])) {
            foreach ([
                'Website'        => 'website_link',
                'PDF Portfolio'  => 'pdf_portfolio_link',
                'Client Reviews' => 'client_review_link',
            ] as $label => $key) {
                $url = SalesContent::getValue($key);
                if ($url) {
                    $content['links'][] = ['label' => $label, 'url' => $url];
                }
            }
        }

        // 4. Documents
        if (!empty($config['show_documents'])) {
            $content['documents'] = true;
        }

        return $content;
    }

    /**
     * Replace placeholders in template content.
     */
    protected function replacePlaceholders(string $content, Deal $deal): string
    {
        $contact = $deal->contacts->first();
        $user    = auth()->user();

        $placeholders = [
            '{name}'          => $contact ? $contact->name : 'Customer',
            '{phone}'         => $this->getContactPhone($deal),
            '{event_date}'    => $deal->expected_close_date
                                     ? $deal->expected_close_date->format('d-M-Y')
                                     : '',
            '{event_type}'    => '',
            '{budget}'        => $deal->amount ? number_format($deal->amount, 2) : '',
            '{package}'       => '',
            '{salesman_name}' => $user ? $user->name : '',
            '{company_name}'  => settings('company_name') ?: 'Our Company',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Return follow-up summary for the deal: done count, next pending, and whether any upcoming exist.
     */
    protected function getFollowupSummary(Deal $deal): array
    {
        $next = Followup::where('deal_id', $deal->id)
            ->pending()
            ->orderBy('followup_date')
            ->orderBy('followup_time')
            ->first();

        $doneCount = Followup::where('deal_id', $deal->id)
            ->where('status', 'done')
            ->count();

        return [
            'done_count'  => $doneCount,
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

    /**
     * Get clean contact phone number.
     */
    protected function getContactPhone(Deal $deal): string
    {
        $contact = $deal->contacts->first();
        if (! $contact) return '';

        $phone = $contact->phones->first();
        return $phone ? preg_replace('/[^0-9]/', '', $phone->number) : '';
    }
}
