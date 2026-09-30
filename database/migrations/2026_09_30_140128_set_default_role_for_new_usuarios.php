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
        if (Schema::hasTable('usuario') && Schema::hasColumn('usuario', 'role')) {
            Schema::table('usuario', function (Blueprint $table): void {
                $table->string('role', 20)->default('Admin')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('usuario') && Schema::hasColumn('usuario', 'role')) {
            Schema::table('usuario', function (Blueprint $table): void {
                $table->string('role', 20)->default('Superadmin')->change();
            });
        }
    }
};
