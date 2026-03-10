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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_owed', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('enrolled_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
