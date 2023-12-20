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
        Schema::create('researchers', function (Blueprint $table) {
            $table->id();
            $table->string('orcid')->nullable();
            $table->string('ecris')->nullable();
            $table->string('scopusid')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('faculty')->nullable();
            $table->string('department')->nullable();
            $table->string('search_index')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('researchers');
    }
};
