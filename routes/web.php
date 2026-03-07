<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Prospects
    Route::livewire('prospects', 'pages::prospects.index')->name('prospects.index');
    Route::livewire('prospects/create', 'pages::prospects.create')->name('prospects.create');
    Route::livewire('prospects/{prospect}', 'pages::prospects.show')->name('prospects.show');

    // Cohorts
    Route::livewire('cohorts', 'pages::cohorts.index')->name('cohorts.index');
    Route::livewire('cohorts/create', 'pages::cohorts.create')->name('cohorts.create');
    Route::livewire('cohorts/{cohort}/edit', 'pages::cohorts.edit')->name('cohorts.edit');

    // Staff (admin only)
    Route::livewire('staff', 'pages::staff.index')->name('staff.index');
    Route::livewire('staff/create', 'pages::staff.create')->name('staff.create');
});

// Public inquiry form
Route::livewire('inquiry', 'pages::inquiry.inquiry-form')->name('inquiry.form');

require __DIR__.'/settings.php';
