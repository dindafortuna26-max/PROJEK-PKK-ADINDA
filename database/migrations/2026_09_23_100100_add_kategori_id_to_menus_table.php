<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * kategori_id dibuat nullable + nullOnDelete: kalau kategori
     * dihapus, menu-nya TIDAK ikut terhapus, cuma jadi "Tanpa Kategori".
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->foreignId('kategori_id')
                ->nullable()
                ->after('id')
                ->constrained('kategoris')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropColumn('kategori_id');
        });
    }
};
