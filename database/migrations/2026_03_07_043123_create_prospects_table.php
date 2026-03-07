<?php

use App\Enums\ContactMethod;
use App\Enums\ContactTime;
use App\Enums\ProspectEntryPoint;
use App\Enums\ProspectStatus;
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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('preferred_contact_method')->default(ContactMethod::Phone->value);
            $table->string('best_time_to_contact')->default(ContactTime::Morning->value);
            $table->string('heard_about_us')->nullable();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->string('highest_education_level')->nullable();
            $table->string('employment_status')->nullable();
            $table->boolean('financing_interest')->default(false);
            $table->text('goals')->nullable();
            $table->string('status')->default(ProspectStatus::New->value);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entry_point')->default(ProspectEntryPoint::PublicForm->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
