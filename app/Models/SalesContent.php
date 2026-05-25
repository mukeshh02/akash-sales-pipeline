<?php

namespace Modules\AkashSalesPipeline\Models;

use Illuminate\Database\Eloquent\Model;

class SalesContent extends Model
{
    protected $table = 'akash_sales_settings';

    protected $fillable = ['key', 'value', 'label', 'input_type', 'sort_order'];

    /**
     * Get the value for a given key, or null if not found / empty.
     */
    public static function getValue(string $key): ?string
    {
        $value = static::where('key', $key)->value('value');

        return $value ?: null;
    }

    /**
     * Return all settings as an associative array keyed by 'key'.
     */
    public static function allKeyed(): array
    {
        return static::orderBy('sort_order')->get()
            ->keyBy('key')
            ->toArray();
    }
}
