<?php

namespace Modules\ACP_Sales_Guide\Models;

use Illuminate\Database\Eloquent\Model;

class SalesContent extends Model
{
    protected $table = 'acp_settings';

    protected $fillable = ['key', 'value', 'label', 'input_type', 'sort_order'];

    public static function getValue(string $key): ?string
    {
        return static::where('key', $key)->value('value') ?: null;
    }

    public static function allKeyed(): array
    {
        return static::orderBy('sort_order')->get()->keyBy('key')->toArray();
    }
}
