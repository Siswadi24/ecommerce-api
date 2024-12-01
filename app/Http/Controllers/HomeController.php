<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use App\ResponseFormatter;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function getSlider()
    {
        $sliders = Slider::all();

        return ResponseFormatter::success($sliders->pluck('api_response'));
    }
}
