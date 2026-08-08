<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('doc_no')->unique(); // auto-incremented sequential number shown to user
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('customer_code');
            $table->string('customer_name');
            $table->date('posting_date');
            $table->foreignId('sales_employee_id')->nullable()->constrained('sales_employees');
            $table->text('remarks'); // mandatory
            $table->decimal('total_before_discount', 18, 3)->default(0);
            $table->decimal('discount_percent', 6, 3)->default(0);
            $table->decimal('total_after_discount', 18, 3)->default(0);
            $table->boolean('needs_approval')->default(false);
            $table->string('status')->default('Open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
