<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('price', 10);
            $table->timestamps();

            $table->foreign('location_id')
                ->references('id')->on('locations');
            $table->foreign('currency_id')
                ->references('id')->on('currencies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
