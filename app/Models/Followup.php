<?php

namespace Modules\ACP_Sales_Guide\Models;

use Illuminate\Database\Eloquent\Model;

class Followup extends Model
{
    protected $table = 'acp_followups';

    protected $fillable = [
        'deal_id', 'followup_date', 'followup_time',
        'followup_type', 'template_name', 'note', 'status', 'created_by',
    ];

    protected $casts = ['followup_date' => 'date'];

    /** Loose relationship — no hard import of CRM Deal class */
    public function deal()
    {
        return $this->belongsTo(config('acp_sales_guide.deal_model'));
    }

    public function creator()
    {
        return $this->belongsTo(config('acp_sales_guide.user_model'), 'created_by');
    }

    public function scopePending($query)  { return $query->where('status', 'pending'); }
    public function scopeToday($query)    { return $query->whereDate('followup_date', today()); }

    public function getFormattedDateTimeAttribute(): string
    {
        $date = $this->followup_date->format('d M Y');
        $time = $this->followup_time ? substr($this->followup_time, 0, 5) : null;
        return $time ? "{$date} {$time}" : $date;
    }
}
