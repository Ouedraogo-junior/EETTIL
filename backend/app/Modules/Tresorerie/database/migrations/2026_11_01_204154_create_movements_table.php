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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('category_id')->constrained('movement_categories');
            $table->foreignId('dossier_id')->nullable()->constrained('dossiers');
            $table->unsignedBigInteger('payment_id')->nullable()->unique();
            $table->string('direction', 3);
            $table->unsignedBigInteger('amount');
            $table->date('moved_on')->index();
            $table->string('label');
            $table->boolean('is_debours')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['account_id', 'cancelled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
