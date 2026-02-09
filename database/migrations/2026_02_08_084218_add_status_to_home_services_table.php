<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // في التيرمنال: php artisan make:migration add_status_to_home_services_table --table=home_services

public function up(): void
{
    Schema::table('home_services', function (Blueprint $table) {
        // إضافة عمود الحالة مع قيمة افتراضية 'pending'
        $table->enum('status', ['pending', 'accepted', 'rejected'])
              ->default('pending')
              ->after('service_type'); // مكان العمود (اختياري)
    });
}

public function down(): void
{
    Schema::table('home_services', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}
};
