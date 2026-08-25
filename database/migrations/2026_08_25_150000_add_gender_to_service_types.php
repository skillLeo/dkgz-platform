<?php

use App\Support\GermanNoun;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The grammatical gender of each service.
 *
 * The service pages are written once and reused for every service, so a
 * sentence built around "Unfallgutachten" was being handed to "Beweissicherung"
 * word for word — "zum Beweissicherung", "ein Beweissicherung". German decides
 * the word in front of a noun by that noun's gender, so the gender has to
 * travel with the service.
 *
 * Nullable, because it is guessed from the name and the operator only needs to
 * say anything when the guess is wrong.
 *
 * @see GermanNoun
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->char('gender', 1)->nullable()->after('name_de');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
