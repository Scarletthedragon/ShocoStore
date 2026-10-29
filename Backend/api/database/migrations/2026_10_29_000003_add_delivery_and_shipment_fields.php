<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Lietotajs', function (Blueprint $table): void {
            $table->json('delivery_address')->nullable();
        });
        Schema::table('Pasutijums', function (Blueprint $table): void {
            $table->uuid('shipment_id')->nullable()->index();
            $table->json('delivery_address')->nullable();
            $table->json('status_history')->nullable();
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->string('tracking_number', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('Pasutijums', function (Blueprint $table): void {
            $table->dropIndex(['shipment_id']);
            $table->dropColumn(['shipment_id', 'delivery_address', 'status_history', 'unit_price', 'tracking_number']);
        });
        Schema::table('Lietotajs', function (Blueprint $table): void {
            $table->dropColumn('delivery_address');
        });
    }
};
