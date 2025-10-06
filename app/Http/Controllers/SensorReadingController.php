<?php

namespace App\Http\Controllers;

use App\Events\SensorUpdated;
use App\Models\Sensor;
use App\Models\SensorReading;
use Illuminate\Http\Request;

class SensorReadingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $request->validate([
            "longitude" => "required|decimal:1,14",
            "latitude" => "required|decimal:1,14",
            "moisture" => "required|integer",
            "uuid" => "required|exists:sensors,id"
        ]);
        
        $sensor = Sensor::withCount('readings')->findOrFail($request->uuid);

        $sensor->readings()->create([
            "latitude" => $request->latitude,
            "longitude" => $request->longitude,
            "moisture" => $request->moisture,
        ]);

        if ($sensor->readings_count >= 100){
            $sensor->oldestReading()->delete();
        }

        SensorUpdated::dispatch($sensor);

        return response()->json(["status" => "ok"]);
    }

    /**
     * Display the specified resource.
     */
    public function show(SensorReading $sensorReading)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SensorReading $sensorReading)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SensorReading $sensorReading)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SensorReading $sensorReading)
    {
        //
    }
}
