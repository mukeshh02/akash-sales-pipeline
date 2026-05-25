<?php

namespace Modules\AkashSalesPipeline\Models;

use Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Deals\Models\Deal;

class SalesLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'akash_sales_pipeline_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'deal_id',
        'stage_name',
        'action_type',
        'note',
        'created_by',
    ];

    /**
     * Get the deal that the log belongs to.
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Get the user that created the log.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
