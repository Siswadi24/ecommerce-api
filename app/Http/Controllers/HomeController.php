<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

    public function getCategory()
    {
        $categories = Category::whereNull('parent_id')->with('childs')->get();

        return ResponseFormatter::success($categories->pluck('api_response'));
    }

    public function getProduct()
    {
        $product = \App\Models\Product\Products::orderBy('id', 'desc');

        if (!is_null(request()->category)) {
            $category = \App\Models\Category::where('slug', request()->category)->firstOrFail();
            $product->where('category_id', $category->id);
        }

        if (!is_null(request()->seller)) {
            $seller = \App\Models\User::where('username', request()->seller)->firstOrFail();
            $product->where('seller_id', $seller->id);
        }

        if (!is_null(request()->search)) {
            $product->where('name', 'LIKE', '%' . request()->search . '%');
        }

        $products = $product->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($products->through(function ($product) {
            return $product->api_response_excerpt;
        }));
    }

    public function GetProductDetail(string $slug)
    {
        $product = \App\Models\Product\Products::where('slug', $slug)->firstOrFail();

        // dd($product);

        return ResponseFormatter::success($product->api_response);
    }

    public function getProductReview(string $slug)
    {
        $product = \App\Models\Product\Products::where('slug', $slug)->firstOrFail();

        $reviews = $product->reviews();

        if (!is_null(request()->rating)) {
            $reviews->where('star_seller', request()->rating);
        }

        if (!is_null(request()->with_attachments)) {
            $reviews->whereNotNull('attachments');
        }

        if (!is_null(request()->with_description)) {
            $reviews->whereNotNull('description');
        }
        $reviews = $reviews->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($reviews->through(function ($review) {
            return $review->api_response;
        }));
    }

    public function getSellerDetail(string $username)
    {
        $seller = \App\Models\User::where('username', $username)->firstOrFail();

        return ResponseFormatter::success($seller->api_response_as_seller);
    }
}
