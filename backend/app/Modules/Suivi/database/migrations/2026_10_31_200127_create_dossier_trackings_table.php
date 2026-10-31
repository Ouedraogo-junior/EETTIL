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
        Schema::create('dossier_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->unique()->constrained('dossiers')->cascadeOnDelete();
            $table->string('supplier_invoices', 500)->nullable();
            $table->string('dpi_number', 50)->nullable();
            $table->date('dpi_date')->nullable();
            $table->string('bl_lta', 100)->nullable();
            $table->string('transit_order_number', 100)->nullable();
            $table->string('regime', 20)->nullable();
            $table->string('nature')->nullable();
            $table->unsignedInteger('packages')->nullable();
            $table->decimal('weight_kg', 12, 2)->nullable();
            $table->date('date_ot')->nullable();
            $table->date('date_arrival')->nullable();
            $table->date('date_docs')->nullable();
            $table->date('date_declaration')->nullable();
            $table->date('date_bae')->nullable();
            $table->date('date_delivery')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dossier_trackings');
    }
};
