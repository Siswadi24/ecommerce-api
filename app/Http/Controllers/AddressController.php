<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function getProvince()
    {
        $provinces = \App\Models\Province::get(['uuid', 'name']);

        return ResponseFormatter::success($provinces);
    }

    public function getCity()
    {
        $query = \App\Models\City::query();

        if (request()->province_uuid) {
            $query = $query->whereIn('province_id', function($subQuery) {
                $subQuery->from('provinces')->where('uuid', request()->province_uuid)->select('id');
            });
        }

        if (request()->search) {
            $query = $query->where('name', 'like', '%' . request()->search . '%');
        }

        $cities = $query->get();

        return ResponseFormatter::success($cities);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addresses = auth()->user()->addresses;

        // dd($addresses);

        // return response()->json($addresses);

        return ResponseFormatter::success($addresses->pluck('api_response'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidation(),
        );

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $address = auth()->user()->addresses()->create($this->prepareData());
        $address->refresh(); //Digunakan untuk refresh data uuid karena uuid nya di generate di database

        return $this->show($address->uuid);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();

        return ResponseFormatter::success($address->api_response);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidation(),
        );

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();
        $address->update($this->prepareData());
        $address->refresh();

        return $this->show($address->uuid);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();
        $address->delete();

        return ResponseFormatter::success([
            'is_deleted' => 'Address deleted successfully',
        ]);
    }

    public function setDefault(string $uuid)
    {
        $address = auth()->user()->addresses()->where('uuid', $uuid)->firstOrFail();
        $address->update([
            'is_default' => true
        ]);
        $ad = auth()->user()->addresses()->where('id', '!=', $address->id)->update([
            'is_default' => false
        ]);

        return ResponseFormatter::success([
            'is_success' => true,
        ]);
    }

    protected function getValidation()
    {
        return [
            'is_default' => 'required|in:1,0',
            'receiver_name' => 'required|min:2|max:30',
            'receiver_phone' => 'required|min:2|max:30',
            'city_uuid' => 'required|exists:cities,uuid',
            'district' => 'required|min:2|max:50',
            'postal_code' => 'required|numeric',
            'detail_address' => 'nullable|max:255',
            'address_note' => 'nullable|max:255',
            'type' => 'required|in:home,office',

        ];
    }

    protected function prepareData()
    {
        $payload = request()->only([
            'is_default',
            'receiver_name',
            'receiver_phone',
            'city_uuid',
            'district',
            'postal_code',
            'detail_address',
            'address_note',
            'type'
        ]);
        $payload['city_id'] = City::where('uuid', $payload['city_uuid'])->firstOrFail()->id;
        if ($payload['is_default'] == 1) {
            auth()->user()->addresses()->update([
                'is_default' => false
            ]);
        }

        return $payload;
    }
}
