<?php

use App\Services\AlertsEngineService;
use App\Services\SupplierRatingService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    app(SupplierRatingService::class)->recalculateAll();
})->dailyAt('06:00')->name('recalculate-supplier-ratings');

Schedule::call(function () {
    app(AlertsEngineService::class)->scan();
})->dailyAt('06:05')->name('alerts-engine-scan');
