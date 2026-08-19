<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Every string on every public page. Addressed as page.section.field.
 */
class ContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_key', 'section_key', 'field_key', 'type', 'value', 'label_de', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function dottedKey(): string
    {
        return "{$this->page_key}.{$this->section_key}.{$this->field_key}";
    }

    public function scopeForPage(Builder $query, string $pageKey): Builder
    {
        return $query->where('page_key', $pageKey)->orderBy('sort_order');
    }
}
