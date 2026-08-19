<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('password');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('locale', 5)->default('de')->after('avatar_path');
            $table->boolean('is_active')->default(true)->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->text('two_factor_secret')->nullable()->after('last_login_ip');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'avatar_path', 'locale',
                'is_active', 'last_login_at', 'last_login_ip',
                'two_factor_secret', 'two_factor_confirmed_at',
            ]);
        });
    }
};
