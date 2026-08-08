<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_no')->unique();
            $table->string('item_description');
            $table->string('uom_code', 20)->nullable();
            $table->decimal('unit_price', 18, 3)->default(0);
            $table->string('warehouse', 20)->nullable();
            $table->integer('qty_in_whse')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
