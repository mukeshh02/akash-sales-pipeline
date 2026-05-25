<?php

namespace Modules\ACP_Sales_Guide\Models;

use Illuminate\Database\Eloquent\Model;

class SalesLog extends Model
{
    protected $table = 'acp_sales_logs';

    protected $fillable = ['deal_id', 'stage_name', 'action_type', 'note', 'created_by'];

    public function deal()
    {
        return $this->belongsTo(config('acp_sales_guide.deal_model'));
    }

    public function creator()
    {
        return $this->belongsTo(config('acp_sales_guide.user_model'), 'created_by');
    }
}
