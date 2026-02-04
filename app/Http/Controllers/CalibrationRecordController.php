<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CalibrationRecordController extends Controller
{
    public function index()
    {
        return view('calibration.index', ['title' => 'Calibration Records']);
    }
}
