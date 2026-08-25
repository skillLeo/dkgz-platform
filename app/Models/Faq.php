<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    /**
     * The groups the public FAQ page sorts questions into.
     *
     * A fixed list rather than free text: the page renders one section per
     * distinct value, so "Kosten" and "kosten" typed on different days would
     * quietly become two headings saying the same thing.
     */
    public const CATEGORIES = [
        'Allgemein',
        'Ablauf',
        'Kosten',
        'Leistungen',
        'Sachverständige',
        'Datenschutz',
    ];

    protected $fillable = [
        'question_de', 'answer_de', 'category', 'sort_order', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
