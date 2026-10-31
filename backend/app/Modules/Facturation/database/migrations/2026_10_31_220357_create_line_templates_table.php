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
        Schema::create('line_templates', function (Blueprint $table) {
            $table->id();
            $table->string('block', 1);
            $table->string('label');
            $table->unsignedSmallInteger('position')->default(0);
            $table->unique(['block', 'label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_templates');
    }
};
