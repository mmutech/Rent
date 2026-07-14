<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/booking/index', 'pages::booking.index')->name('booking.index');
    Route::livewire('/booking/create', 'pages::booking.create')->name('booking.create');
    Route::livewire('/booking/{booking}/edit', 'pages::booking.edit')->name('booking.edit');
    Route::livewire('/booking/{booking}/show', 'pages::booking.show')->name('booking.show');
});
