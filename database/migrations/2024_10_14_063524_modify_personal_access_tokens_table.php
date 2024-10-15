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
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Hapus kolom yang tidak diperlukan
            $table->dropMorphs('tokenable');
            $table->dropColumn('name');
            $table->dropColumn('abilities');
            $table->dropColumn('last_used_at');

            // Tambahkan kolom baru
            $table->string('email')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Tambahkan kembali kolom yang dihapus
            $table->morphs('tokenable');
            $table->string('name');
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();

            // Hapus kolom baru
            $table->dropColumn('email');
        });
    }
};