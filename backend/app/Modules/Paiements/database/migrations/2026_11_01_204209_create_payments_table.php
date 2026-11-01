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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents');
            $table->foreignId('dossier_id')->constrained('dossiers');
            $table->foreignId('account_id')->constrained('accounts');
            $table->date('paid_on')->index();
            $table->unsignedBigInteger('amount');
            $table->string('mode', 15);
            $table->string('reference', 100);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['document_id', 'cancelled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
