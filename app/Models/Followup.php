<?php

namespace Modules\AkashSalesPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Deals\Models\Deal;
use Modules\Users\Models\User;

class Followup extends Model
{
    protected $table = 'akash_sales_followups';

    protected $fillable = [
        'deal_id',
        'followup_date',
        'followup_time',
        'followup_type',
        'template_name',
        'note',
        'status',
        'created_by',
    ];

    protected $casts = [
        'followup_date' => 'date',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('followup_date', today());
    }

    /**
     * Return a formatted datetime string for display.
     */
    public function getFormattedDateTimeAttribute(): string
    {
        $date = $this->followup_date->format('d M Y');
        $time = $this->followup_time ? substr($this->followup_time, 0, 5) : null;

        return $time ? "{$date} {$time}" : $date;
    }
}
