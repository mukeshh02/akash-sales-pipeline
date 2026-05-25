<?php

namespace Modules\ACP_Sales_Guide\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ACP_Sales_Guide\Models\Followup;
use Modules\ACP_Sales_Guide\Models\SalesLog;

class FollowupController extends Controller
{
    public function index(int $dealId)
    {
        $all = Followup::where('deal_id', $dealId)
            ->orderBy('followup_date')->orderBy('followup_time')
            ->get()->map(fn($f) => $this->format($f));

        return response()->json([
            'upcoming' => $all->filter(fn($f) => $f['is_upcoming'])->values(),
            'past'     => $all->filter(fn($f) => !$f['is_upcoming'])->values(),
        ]);
    }

    public function store(Request $request, int $dealId)
    {
        $data = $request->validate([
            'followup_date' => 'required|date',
            'followup_time' => 'sometimes|nullable|date_format:H:i',
            'followup_type' => 'required|in:call,whatsapp',
            'template_name' => 'nullable|string',
            'note'          => 'sometimes|nullable|string|max:1000',
        ]);

        $data['followup_time'] = $data['followup_time'] ?: null;
        $data['note']          = $data['note'] ?: null;

        $followup = Followup::create([
            ...$data,
            'deal_id'    => $dealId,
            'status'     => 'pending',
            'created_by' => auth()->id(),
        ]);

        return response()->json($this->format($followup), 201);
    }

    public function markDone(int $followupId)
    {
        $followup = Followup::findOrFail($followupId);
        $followup->update(['status' => 'done']);

        SalesLog::create([
            'deal_id'     => $followup->deal_id,
            'stage_name'  => 'Follow-up',
            'action_type' => 'followup',
            'note'        => 'Follow-up completed: ' . ($followup->note ?: '—'),
            'created_by'  => auth()->id(),
        ]);

        return response()->json($this->format($followup->fresh()));
    }

    private function format(Followup $f): array
    {
        $isUpcoming = $f->status === 'pending' && $f->followup_date->gte(today());

        return [
            'id'            => $f->id,
            'deal_id'       => $f->deal_id,
            'followup_date' => $f->followup_date->format('Y-m-d'),
            'followup_time' => $f->followup_time ? substr($f->followup_time, 0, 5) : null,
            'display_dt'    => $f->formatted_date_time,
            'followup_type' => $f->followup_type,
            'template_name' => $f->template_name,
            'note'          => $f->note,
            'status'        => $f->status,
            'is_upcoming'   => $isUpcoming,
        ];
    }
}
