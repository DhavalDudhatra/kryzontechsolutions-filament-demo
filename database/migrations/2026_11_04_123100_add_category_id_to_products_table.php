<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'category_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // The original products migration also defines this column, so dropping
        // it here would break databases where that migration created the table.
    }
};
