<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // $records = [\Webkul\Purchase\Models\Requisition::first()];

    // return view('purchases::filament.clusters.orders.purchase-agreements.print', compact('records'));

    return redirect()->route('filament.admin..');
});
