<?php

namespace Modules\AkashSalesPipeline\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\AkashSalesPipeline\Models\Followup;
use Modules\AkashSalesPipeline\Models\SalesLog;
use Modules\AkashSalesPipeline\Models\Template;

class TodayFollowupsController extends Controller
{
    /**
     * All pending follow-ups for today.
     */
    public function index()
    {
        $followups = Followup::with(['deal.contacts.phones', 'deal.user'])
            ->today()
            ->pending()
            ->orderBy('followup_time')
            ->get();

        $template = Template::where('name', 'followup')->where('is_active', true)->first();

        return response()->json(
            $followups->map(fn($f) => $this->formatRow($f, $template))
        );
    }

    /**
     * Mark a follow-up as done from the today dashboard.
     */
    public function markDone(int $followupId)
    {
        $followup = Followup::findOrFail($followupId);
        $followup->update(['status' => 'done']);

        SalesLog::create([
            'deal_id'     => $followup->deal_id,
            'stage_name'  => 'Follow-up',
            'action_type' => 'followup',
            'note'        => 'Follow-up completed from Today dashboard: ' . ($followup->note ?: '—'),
            'created_by'  => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }

    private function formatRow(Followup $f, ?Template $template): array
    {
        $deal    = $f->deal;
        $contact = $deal?->contacts->first();
        $phone   = $contact?->phones->first();
        $rawPhone = $phone ? preg_replace('/[^0-9]/', '', $phone->number) : '';

        $message = '';
        if ($template && $f->followup_type === 'whatsapp') {
            $message = $this->replacePlaceholders($template->content, $deal, $contact, $rawPhone);
        }

        return [
            'id'            => $f->id,
            'deal_id'       => $deal?->id,
            'deal_name'     => $deal?->name ?? '—',
            'contact_name'  => $contact?->name ?? '—',
            'phone'         => $rawPhone,
            'followup_type' => $f->followup_type,
            'followup_time' => $f->followup_time ? substr($f->followup_time, 0, 5) : '—',
            'note'          => $f->note,
            'status'        => $f->status,
            'whatsapp_message' => $message,
        ];
    }

    private function replacePlaceholders(string $content, $deal, $contact, string $phone): string
    {
        $user = auth()->user();

        return str_replace(
            ['{name}', '{phone}', '{event_date}', '{event_type}', '{budget}', '{package}', '{salesman_name}', '{company_name}'],
            [
                $contact?->name ?? 'Customer',
                $phone,
                $deal?->expected_close_date?->format('d-M-Y') ?? '',
                '',
                $deal?->amount ? number_format($deal->amount, 2) : '',
                '',
                $user?->name ?? '',
                settings('company_name') ?: 'Our Company',
            ],
            $content
        );
    }
}
