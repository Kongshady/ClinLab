<?php

namespace App\Http\Controllers;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }
}
