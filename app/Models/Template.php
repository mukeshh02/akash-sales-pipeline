<?php

namespace Modules\ACP_Sales_Guide\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'acp_templates';

    protected $fillable = ['name', 'content', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
