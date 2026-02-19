<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestRequestController extends Controller
{
    /**
     * Display the test requests management page for staff.
     */
    public function index()
    {
        return view('test-requests.index');
    }
}
