<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a content block be a switch rather than a string.
 *
 * Hiding a part of a page was only possible by emptying the text that filled
 * it, which meant deleting the wording to be rid of the thing — and typing it
 * back from memory to have it again. A block of type `boolean` holds "1" or
 * "0", the editor draws it as a toggle, and the words stay where they are while
 * the section is off.
 *
 * Changed through the schema builder rather than by raw ALTER, so it reaches
 * every driver. On SQLite an enum is a CHECK constraint written when the table
 * was created, and a database that already ran the create migration keeps the
 * old list however the create migration is later edited — so a MySQL-only
 * statement here would leave every existing development database rejecting the
 * new type while recording the migration as done. Widening keeps every row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->enum('type', ['text', 'richtext', 'image', 'url', 'boolean'])->default('text')->change();
        });
    }

    public function down(): void
    {
        // Any switch becomes an ordinary text block holding its own "1" or "0",
        // so narrowing the column back cannot fail on a row it no longer allows.
        DB::table('content_blocks')->where('type', 'boolean')->update(['type' => 'text']);

        Schema::table('content_blocks', function (Blueprint $table) {
            $table->enum('type', ['text', 'richtext', 'image', 'url'])->default('text')->change();
        });
    }
};
