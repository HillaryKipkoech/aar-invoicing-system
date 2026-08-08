<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('item_no')->nullable();
            $table->string('item_description');
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('price_before_discount', 18, 3)->default(0);
            $table->decimal('discount', 18, 3)->default(0); // percentage, max 50
            $table->decimal('price_after_discount', 18, 3)->default(0);
            $table->decimal('line_total', 18, 3)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
