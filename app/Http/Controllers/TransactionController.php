<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with('patient')->orderBy('transaction_id', 'desc')->paginate(15);
        return Inertia::render('Transactions/Index', ['transactions' => $transactions]);
    }

    public function create()
    {
        $patients = Patient::active()->orderBy('lastname')->get(['patient_id', 'firstname', 'lastname']);
        return Inertia::render('Transactions/Create', ['patients' => $patients]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:patient,patient_id',
            'or_number' => 'required|integer',
            'client_designation' => 'nullable|string|max:50',
        ]);

        $validated['datetime_added'] = now();
        $validated['status_code'] = 1;

        Transaction::create($validated);
        return redirect()->route('transactions.index')->with('success', 'Transaction created successfully.');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('patient');
        return Inertia::render('Transactions/Show', ['transaction' => $transaction]);
    }
}
