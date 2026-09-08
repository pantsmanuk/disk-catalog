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
        Schema::create('disks', function (Blueprint $table) {
            $table->id();
            $table->string('location_type');
            $table->string('location');
            $table->string('gptid')->nullable();
            $table->string('device')->nullable();
            $table->string('serial')->unique();
            $table->string('model');
            $table->string('capacity')->nullable();
            $table->string('interface');
            $table->string('pool')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['location_type', 'location']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disks');
    }
};
