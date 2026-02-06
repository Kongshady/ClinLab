<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Patient;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        return view('transactions.index');
    }

    public function create()
    {
        return redirect()->route('transactions.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:patient,patient_id',
            'or_number' => 'required|integer',
        ]);

        $validated['datetime_added'] = now();
        $validated['status_code'] = 1;

        Transaction::create($validated);
        return redirect()->route('transactions.index')->with('success', 'Transaction created successfully.');
    }

    public function show(Transaction $transaction)
    {
        return redirect()->route('transactions.index');
    }

    public function edit(Transaction $transaction)
    {
        return redirect()->route('transactions.index');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:patient,patient_id',
            'or_number' => 'required|string|max:50',
            'client_designation' => 'nullable|string|max:50',
        ]);

        $transaction->update($validated);
        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
