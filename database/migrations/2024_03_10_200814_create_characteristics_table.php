<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('characteristics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('characteristic_architecture_id');
            $table->unsignedBigInteger('characteristic_unit_id');
            $table->integer('capacity');
            $table->timestamps();

            $table->foreign('characteristic_architecture_id')
                ->references('id')->on('architectures');
            $table->foreign('characteristic_unit_id')
                ->references('id')->on('characteristics_units');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characteristics');
    }
};
