<?php

namespace Modules\AkashSalesPipeline\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'akash_sales_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'content',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
