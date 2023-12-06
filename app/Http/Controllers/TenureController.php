<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{StoreTenureRequest, UpdateTenureRequest};
use App\Models\Tenure;

class TenureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tenures = Tenure::all();

        return $this->sendSuccess(__('app.request_successful'), 200, $tenures);
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
    public function store(StoreTenureRequest $request)
    {
        $data = $request->validated();

        $tenure = Tenure::create([
            'months' => $data['months']
        ]);

        return $this->sendSuccess('Tenure created successfully.', 201, $tenure);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenure $tenure)
    {
        return $this->sendSuccess(__('app.request_successful'), 200, $tenure);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenure $tenure)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenureRequest $request, Tenure $tenure)
    {
        $data = $request->validated();

        $tenure->update([
            'months' => $data['months']
        ]);

        return $this->sendSuccess(__('app.request_successful'), 200, $tenure);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenure $tenure)
    {
        $tenure->delete();

        return $this->sendSuccess('Tenure deleted successfully.');
    }
}
