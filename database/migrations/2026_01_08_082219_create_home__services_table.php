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
        Schema::create('home_services', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('description');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('service_type', ['image_request', 'direct_request']);
            $table->string('profession')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home__services');
    }
};
