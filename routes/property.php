<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/property/index', 'pages::property.index')->name('property.index');
    Route::livewire('/property/create', 'pages::property.create')->name('property.create');
    Route::livewire('/property/{property}/edit', 'pages::property.edit')->name('property.edit');
    Route::livewire('/property/{property}/show', 'pages::property.show')->name('property.show');
});
