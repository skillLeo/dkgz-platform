<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class EmailTemplate extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'key', 'name_de', 'subject_de', 'preheader_de', 'body_html',
        'available_variables', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'name_de', 'subject_de', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('email-vorlage');
    }

    /**
     * Replaces {{ variable }} placeholders. Values are escaped: a template is
     * admin-editable content, and the data going into it is user data.
     */
    public function render(string $field, array $data): string
    {
        $source = $field === 'subject' ? $this->subject_de : $this->body_html;

        return preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            fn (array $m) => isset($data[$m[1]])
                ? e((string) $data[$m[1]])
                : '',
            (string) $source
        ) ?? '';
    }

    public function renderSubject(array $data): string
    {
        // Subjects are plain text; unescape the entities e() introduced.
        return html_entity_decode($this->render('subject', $data), ENT_QUOTES, 'UTF-8');
    }

    public function renderBody(array $data): string
    {
        return $this->render('body', $data);
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
