<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'google2fa_secret')) {
                $table->text('google2fa_secret')->nullable();
            }

            if (! Schema::hasColumn('users', 'google2fa_recovery_codes')) {
                $table->text('google2fa_recovery_codes')->nullable();
            }

            if (! Schema::hasColumn('users', 'is_2fa_enabled')) {
                $table->boolean('is_2fa_enabled')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_filter(
            ['google2fa_secret', 'google2fa_recovery_codes', 'is_2fa_enabled'],
            fn (string $column): bool => Schema::hasColumn('users', $column),
        );

        if ($columns !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
