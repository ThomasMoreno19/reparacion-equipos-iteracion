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
        if (Schema::hasTable('usuario') && Schema::hasColumn('usuario', 'id_empresa')) {
            Schema::table('usuario', function (Blueprint $table): void {
                $table->dropForeign('usuario_id_empresa_foreign');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('El vínculo ahora apunta a empresa en efactweb_local y no puede recrearse en reparacion_local.');
    }
};
