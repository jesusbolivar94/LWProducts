<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('characteristics_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('architecture_id');
            $table->timestamps();

            $table->foreign('architecture_id')
                ->references('id')->on('architectures');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characteristics_types');
    }
};
