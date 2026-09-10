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
        $tables = [
            'nexopos_products',
            'nexopos_orders',
            'nexopos_products_categories',
            'nexopos_customers'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    // nabd_businesses uses UUIDs
                    $table->uuid('business_id')->nullable()->index();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'nexopos_products',
            'nexopos_orders',
            'nexopos_products_categories',
            'nexopos_customers'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'business_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('business_id');
                });
            }
        }
    }
};
