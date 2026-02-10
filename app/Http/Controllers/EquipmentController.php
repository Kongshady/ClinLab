<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        return view('equipment.index');
    }

    public function show($id)
    {
        return view('equipment.show', ['equipmentId' => $id]);
    }
}
