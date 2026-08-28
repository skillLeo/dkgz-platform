<?php

use App\Models\Assessor;
use Illuminate\Database\Migrations\Migration;

/**
 * An address for every partner who already existed.
 *
 * The slug is filled in when a partner is saved, and the hundred and thirty
 * already approved were saved long before the column existed — so without this
 * the directory would list them and every profile link would go nowhere.
 *
 * Saving each one runs the model's own slug logic, which is what keeps the
 * numbering of duplicates consistent with anything created afterwards. Deleted
 * partners are included: their slugs are still reserved, and skipping them
 * would let a new firm take the address of one that was removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Assessor::withTrashed()
            ->whereNull('slug')
            ->orderBy('id')
            ->chunkById(100, function ($assessors) {
                foreach ($assessors as $assessor) {
                    // Saving is what generates it; nothing else changes.
                    $assessor->save();
                }
            });
    }

    /** Not reversible: the column goes with the migration that added it. */
    public function down(): void
    {
        //
    }
};
