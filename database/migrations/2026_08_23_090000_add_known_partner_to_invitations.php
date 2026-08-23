<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks an invitation as going to somebody who already works with Carspector.
 *
 * They are not being asked to trust a stranger, they are being told that the
 * firm they already know has opened a second line of business. Read the wrong
 * way round, a cold invitation to an existing partner reads as a company that
 * has forgotten who they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->boolean('known_partner')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('known_partner');
        });
    }
};
