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
            $table->dropColumn('id');
            $table->dropColumn('updated_at');

            // Jadikan email sebagai primary key
            $table->primary('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Tambahkan kembali kolom yang dihapus
            $table->id()->first();
            $table->timestamps();

            // Hapus primary key dari email
            $table->dropPrimary('email');
        });
    }
};