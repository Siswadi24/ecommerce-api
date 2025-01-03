<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function () {
    // Data yang dikirim
    $data = [
        [
            'name' => 'origin',
            'contents' => '128',
        ],
        [
            'name' => 'destination',
            'contents' => '17',
        ],
        [
            'name' => 'weight',
            'contents' => 1000,
        ],
        [
            'name' => 'courier',
            'contents' => 'jne',
        ],
    ];

    // HTTP Request
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'key' => config('services.rajaongkir.key'),
    ])->attach(
        $data
    )->post(config('services.rajaongkir.base_url') . '/domestic-cost');

    dd('Response:', $response->json());
});
