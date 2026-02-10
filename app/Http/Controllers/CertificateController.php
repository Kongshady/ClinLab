<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Display a listing of certificates.
     */
    public function index()
    {
        return view('certificates.index');
    }

    /**
     * Display a specific certificate.
     */
    public function show($id)
    {
        $certificate = Certificate::with(['patient', 'equipment', 'issuedBy', 'verifiedBy'])->findOrFail($id);
        return view('certificates.show', compact('certificate'));
    }

    /**
     * Display certificate templates page.
     */
    public function templates()
    {
        return view('certificates.templates.index');
    }

    /**
     * Display issued certificates page.
     */
    public function issued()
    {
        return view('certificates.issued.index');
    }

    /**
     * Display certificate verification page.
     */
    public function verify()
    {
        return view('certificates.verify.index');
    }
}
