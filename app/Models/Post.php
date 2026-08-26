<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One article in the Ratgeber.
 *
 * The rest of the site answers somebody who has already decided they need an
 * assessor. This answers the person a week earlier, and the only way that works
 * is if the writing is worth reading — so an article carries a proper excerpt,
 * a date it stands behind and a name on it, rather than being a paragraph of
 * keywords with a link at the bottom.
 */
class Post extends Model
{
    use HasFactory, LogsActivity;

    /** Fixed, so the same subject is never filed under two spellings. */
    public const CATEGORIES = [
        'Unfall und Schaden',
        'Gutachten verstehen',
        'Versicherung und Recht',
        'Fahrzeugwert',
        'Ratgeber',
    ];

    /** How fast people read German prose, near enough for a "5 Minuten" line. */
    private const WORDS_PER_MINUTE = 200;

    protected $fillable = [
        'slug', 'title', 'category', 'excerpt', 'body',
        'cover_path', 'cover_alt', 'author',
        'meta_title', 'meta_description',
        'is_published', 'published_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'is_published', 'published_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('ratgeber');
    }

    /**
     * The address follows the title, with German umlauts spelled out.
     *
     * Str::slug turns "ü" into "u", and "unfallschaden-pruefen" is what people
     * type and what a search engine expects; "prufen" is neither. The slug
     * stops following once the article has been published, because an address
     * that has been shared or indexed is a promise.
     */
    protected static function booted(): void
    {
        static::saving(function (self $post) {
            if (filled($post->slug) && ! $post->isDirty('title')) {
                return;
            }

            if (filled($post->slug) && $post->getOriginal('is_published')) {
                return;
            }

            $base = Str::slug($post->title, '-', 'de', [
                'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
                'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss',
            ]) ?: 'beitrag';

            $slug = $base;
            $suffix = 2;

            while (static::where('slug', $slug)
                ->when($post->exists, fn ($q) => $q->whereKeyNot($post->getKey()))
                ->exists()
            ) {
                $slug = $base.'-'.$suffix++;
            }

            $post->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Published, and not dated in the future.
     *
     * An article dated next Tuesday is scheduled, not live — the operator
     * should be able to write ahead without the piece appearing the moment they
     * tick the box.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** Newest first, with the operator's own order breaking ties. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function date(): ?Carbon
    {
        return $this->published_at ?? $this->created_at;
    }

    /**
     * The line under the title, so somebody can judge before they commit.
     *
     * Rounded up rather than down: a piece that takes four and a half minutes
     * and says "4 Minuten" has misled the one person who was counting.
     */
    public function readingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->body));

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /** What a listing shows, falling back to the opening of the article. */
    public function summary(int $length = 220): string
    {
        if (filled($this->excerpt)) {
            return $this->excerpt;
        }

        return Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->body))), $length);
    }
}
