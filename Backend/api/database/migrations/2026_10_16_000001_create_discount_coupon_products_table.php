<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_coupon_products', function (Blueprint $table): void {
            $table->foreignId('discount_coupon_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('Prece_ID');
            $table->primary(['discount_coupon_id', 'Prece_ID']);
            $table->foreign('Prece_ID')->references('Prece_ID')->on('Prece')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_coupon_products');
    }
};
