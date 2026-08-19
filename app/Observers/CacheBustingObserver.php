<?php

namespace App\Observers;

use App\Models\ContentBlock;
use App\Models\Setting;
use App\Support\Content;
use App\Support\Settings;

/**
 * Saving a setting or a content block invalidates the matching cache straight
 * away, so an admin edit is visible on the next request rather than in an hour.
 */
class CacheBustingObserver
{
    public function saved(Setting|ContentBlock $model): void
    {
        $this->bust($model);
    }

    public function deleted(Setting|ContentBlock $model): void
    {
        $this->bust($model);
    }

    private function bust(Setting|ContentBlock $model): void
    {
        if ($model instanceof Setting) {
            Settings::flush();

            return;
        }

        Content::flush($model->page_key);
    }
}
