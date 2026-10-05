<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Lietotajs', function (Blueprint $table): void {
            $table->increments('Lietotajs_ID');
            $table->string('Vards', 100);
            $table->string('Epasts', 255)->unique();
            $table->string('Parole', 255);
        });

        Schema::create('Prece', function (Blueprint $table): void {
            $table->increments('Prece_ID');
            $table->string('Nosaukums', 100);
            $table->decimal('Cena', 10, 2);
            $table->unsignedInteger('Atlikums')->default(0);
            $table->string('Apraksts', 1000)->nullable();
            $table->string('Tonis', 25)->default('mango');
            $table->string('Birka', 50)->default('');
        });

        Schema::create('Pasutijums', function (Blueprint $table): void {
            $table->increments('Pasutijums_ID');
            $table->unsignedInteger('Lietotajs_ID')->nullable();
            $table->unsignedInteger('Prece_ID');
            $table->unsignedInteger('Daudzums');
            $table->decimal('Kopeja_cena', 10, 2);
            $table->string('Statuss', 20)->default('gaida');
            $table->string('Klienta_vards', 100)->default('Guest');
            $table->dateTime('Izveidots')->useCurrent();
            $table->index('Lietotajs_ID', 'IX_Pasutijums_Lietotajs_ID');
            $table->index('Prece_ID', 'IX_Pasutijums_Prece_ID');
            $table->foreign('Lietotajs_ID', 'FK_Pasutijums_Lietotajs')
                ->references('Lietotajs_ID')->on('Lietotajs')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('Prece_ID', 'FK_Pasutijums_Prece')
                ->references('Prece_ID')->on('Prece')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Pasutijums');
        Schema::dropIfExists('Prece');
        Schema::dropIfExists('Lietotajs');
    }
};