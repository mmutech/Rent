<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/tenant/index', 'pages::tenant.index')->name('tenant.index');
    Route::livewire('/tenant/create', 'pages::tenant.create')->name('tenant.create');
    Route::livewire('/tenant/{tenant}/edit', 'pages::tenant.edit')->name('tenant.edit');
    Route::livewire('/tenant/{tenant}/show', 'pages::tenant.show')->name('tenant.show');
});
