<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use App\Models\Sensor;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia()->render('sensors/index', [
            'sensors' => Sensor::with([
                'latestReading',
                'latestSchedule.commodity',
            ])->get(),
            'commodities' => Commodity::all(),
        ]);
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
    public function store(Request $request)
    {
        // dump($request->all());

        $request->validate([
            'uuid' => 'nullable|string',
            'mac' => 'required|mac_address',
        ]);

        if ($request->filled('uuid')) {
            $sensor = Sensor::find($request->uuid);

            if ($sensor) {
                return response()->json([
                    'uuid' => $sensor->id,
                    'status' => 'exists',
                ]);
            }
        }

        $sensor = Sensor::create([
            'mac_address' => $request->mac ?? null,
        ]);

        return response()->json([
            'uuid' => $sensor->id,
            'status' => 'registered',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sensor $sensor)
    {
        return inertia()->render('sensors/show', [
            'sensor' => $sensor->load('readings', 'latestReading', 'schedules'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sensor $sensor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sensor $sensor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sensor $sensor)
    {
        //
    }
}
