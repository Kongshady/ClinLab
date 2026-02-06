<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PhysicianController extends Controller
{
    public function index()
    {
        return view('physicians.index');
    }
}
