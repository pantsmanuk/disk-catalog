<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('disks', function (Blueprint $table) {
            $table->string('status')->default('active')->after('faulted');
        });

        DB::table('disks')->where('faulted', true)->update(['status' => 'faulted']);

        Schema::table('disks', function (Blueprint $table) {
            $table->dropColumn('faulted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disks', function (Blueprint $table) {
            $table->boolean('faulted')->default(false)->after('status');
        });

        DB::table('disks')->where('status', 'faulted')->update(['faulted' => true]);

        Schema::table('disks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
