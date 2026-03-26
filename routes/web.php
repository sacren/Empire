<?php

use App\Models\Document;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    // Prospects
    Route::livewire('prospects', 'pages::prospects.index')->name('prospects.index');
    Route::livewire('prospects/create', 'pages::prospects.create')->name('prospects.create');
    Route::livewire('prospects/{prospect}', 'pages::prospects.show')->name('prospects.show');

    // Cohorts
    Route::livewire('cohorts', 'pages::cohorts.index')->name('cohorts.index');
    Route::livewire('cohorts/create', 'pages::cohorts.create')->name('cohorts.create');
    Route::livewire('cohorts/{cohort}/edit', 'pages::cohorts.edit')->name('cohorts.edit');
    Route::livewire('cohorts/{cohort}/attendance', 'pages::cohorts.attendance')->name('cohorts.attendance');
    Route::livewire('cohorts/{cohort}/message', 'pages::cohorts.message')->name('cohorts.message');

    // Staff (admin only)
    Route::livewire('staff', 'pages::staff.index')->name('staff.index');
    Route::livewire('staff/create', 'pages::staff.create')->name('staff.create');

    // Documents
    Route::get('documents/{document}/download', function (Document $document) {
        Gate::authorize('view', $document->prospect);

        return Storage::disk('local')->download($document->disk_path, $document->original_filename);
    })->name('documents.download');

    // Finance (admin only)
    Route::livewire('finance', 'pages::finance.index')->name('finance.index');

    // Reports (admin only)
    Route::livewire('reports', 'pages::reports.index')->name('reports.index');
});

// Public inquiry form
Route::livewire('inquiry', 'pages::inquiry.inquiry-form')->name('inquiry.form');

require __DIR__.'/settings.php';
