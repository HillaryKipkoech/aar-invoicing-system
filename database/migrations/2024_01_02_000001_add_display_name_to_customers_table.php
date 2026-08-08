<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // "Name" in the screenshot (e.g. "Walk In Customer - HQ") is distinct
            // from customer_name (e.g. "TEST TEST") — SAP B1 shows both.
            $table->string('display_name')->nullable()->after('customer_code');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
