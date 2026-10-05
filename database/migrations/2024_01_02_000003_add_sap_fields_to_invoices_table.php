<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Header extras
            $table->date('value_date')->nullable()->after('posting_date');
            $table->date('document_date')->nullable()->after('value_date');
            $table->string('owner')->nullable()->after('sales_employee_id');

            // Footer extras (right-hand totals block)
            $table->decimal('total_down_payment', 18, 3)->default(0)->after('discount_percent');
            $table->decimal('freight', 18, 3)->default(0)->after('total_down_payment');
            $table->boolean('rounding')->default(false)->after('freight');
            $table->decimal('tax', 18, 3)->default(0)->after('rounding');
            $table->decimal('applied_amount', 18, 3)->default(0)->after('total_after_discount');
            $table->decimal('balance_due', 18, 3)->default(0)->after('applied_amount');
            $table->string('qr_code')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'value_date', 'document_date', 'owner',
                'total_down_payment', 'freight', 'rounding', 'tax',
                'applied_amount', 'balance_due', 'qr_code',
            ]);
        });
    }
};
