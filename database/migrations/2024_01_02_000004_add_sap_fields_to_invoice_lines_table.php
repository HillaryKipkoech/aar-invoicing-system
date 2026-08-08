<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->string('warehouse', 20)->nullable()->after('item_description');
            $table->integer('qty_in_whse')->default(0)->after('warehouse');
            $table->string('uom_code', 20)->nullable()->after('qty_in_whse');
            $table->string('vat_code', 10)->default('O0')->after('price_after_discount');
            $table->decimal('gross_price_after_discount', 18, 3)->default(0)->after('vat_code');
            $table->decimal('gross_total', 18, 3)->default(0)->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn([
                'warehouse', 'qty_in_whse', 'uom_code', 'vat_code',
                'gross_price_after_discount', 'gross_total',
            ]);
        });
    }
};
