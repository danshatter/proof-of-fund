<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{StoreOptionRequest, UpdateOptionRequest};
use App\Models\Option;

class OptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $options = Option::all();

        return $this->sendSuccess(__('app.request_successful'), 200, $options);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOptionRequest $request)
    {
        $data = $request->validated();

        $option = Option::create([
            'type' => $data['type'],
            'interest' => $data['interest']
        ]);

        return $this->sendSuccess('Option created successfully.', 201, $option);
    }

    /**
     * Display the specified resource.
     */
    public function show(Option $option)
    {
        return $this->sendSuccess(__('app.request_successful'), 200, $option);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Option $option)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOptionRequest $request, Option $option)
    {
        $data = $request->validated();

        $option->update([
            'type' => $data['type'],
            'interest' => $data['interest']
        ]);

        return $this->sendSuccess('Option updated successfully.', 200, $option);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Option $option)
    {
        $option->delete();

        return $this->sendSuccess('Option deleted successfully.');
    }
}
