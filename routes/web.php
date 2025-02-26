<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // $records = [\Webkul\Purchase\Models\Order::first()];

    // return view('purchases::filament.clusters.orders.orders.actions.print-purchase-order', compact('records'));

    return redirect()->route('filament.admin..');
});
