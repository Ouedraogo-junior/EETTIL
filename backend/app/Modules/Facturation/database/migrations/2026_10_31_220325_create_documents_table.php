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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained('dossiers');
            $table->foreignId('source_document_id')->nullable()->constrained('documents');
            $table->string('type', 10);
            $table->string('number', 40)->unique();
            $table->string('state', 12)->default('emis')->index();
            $table->date('issued_on')->index();
            $table->string('currency', 3)->default('XOF');
            $table->decimal('exchange_rate', 12, 4)->nullable();
            $table->unsignedBigInteger('caf_value')->default(0);
            $table->unsignedSmallInteger('containers_20')->default(0);
            $table->unsignedSmallInteger('containers_40')->default(0);
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(18);
            $table->string('global_discount_kind', 3)->nullable();
            $table->decimal('global_discount_value', 14, 2)->default(0);
            // Totaux calculés par le serveur à chaque enregistrement (jamais saisis)
            $table->unsignedBigInteger('debours_total')->default(0);
            $table->unsignedBigInteger('prestations_subtotal')->default(0);
            $table->unsignedBigInteger('global_discount_amount')->default(0);
            $table->unsignedBigInteger('prestations_ht')->default(0);
            $table->unsignedBigInteger('vat_amount')->default(0);
            $table->unsignedBigInteger('prestations_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
