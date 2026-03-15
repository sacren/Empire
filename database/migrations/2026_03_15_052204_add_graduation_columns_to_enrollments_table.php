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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('graduated_at')->nullable()->after('enrolled_at');
            $table->string('certificate_number')->nullable()->after('graduated_at');
            $table->date('certificate_issued_at')->nullable()->after('certificate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['graduated_at', 'certificate_number', 'certificate_issued_at']);
        });
    }
};
