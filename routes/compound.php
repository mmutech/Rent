<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/compound/index', 'pages::compound.index')->name('compound.index');
    Route::livewire('/compound/create', 'pages::compound.create')->name('compound.create');
    Route::livewire('/compound/{compound}/edit', 'pages::compound.edit')->name('compound.edit');
    Route::livewire('/compound/{compound}/show', 'pages::compound.show')->name('compound.show');
});
