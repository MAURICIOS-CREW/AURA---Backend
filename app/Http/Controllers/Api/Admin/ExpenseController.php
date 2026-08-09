<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('registeredBy:id,name,username');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('concept', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('expense_date', $request->month)
                  ->whereYear('expense_date', $request->year);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $expenses,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'concept' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:maintenance,security,utilities,salaries,services,supplies,other',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'provider' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:cash,transfer,card,check',
            'status' => 'nullable|in:pending,paid,cancelled',
            'receipt' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt') && $request->file('receipt')->isValid()) {
            $receiptPath = $request->file('receipt')->store('expenses', 'public');
        }

        $expense = Expense::create([
            'concept' => $request->concept,
            'description' => $request->description,
            'category' => $request->category,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'provider' => $request->provider,
            'payment_method' => $request->payment_method,
            'status' => $request->status ?? 'paid',
            'receipt' => $receiptPath,
            'registered_by_id' => $request->user()->id,
        ]);

        $expense->load('registeredBy:id,name,username');

        return response()->json([
            'status' => 'success',
            'message' => 'Egreso registrado correctamente.',
            'data' => $expense,
        ], 201);
    }

    public function show(Expense $expense)
    {
        $expense->load('registeredBy:id,name,username');

        return response()->json([
            'status' => 'success',
            'data' => $expense,
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'concept' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|in:maintenance,security,utilities,salaries,services,supplies,other',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'expense_date' => 'sometimes|required|date',
            'provider' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:cash,transfer,card,check',
            'status' => 'sometimes|required|in:pending,paid,cancelled',
            'receipt' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        $data = $request->only([
            'concept', 'description', 'category', 'amount',
            'expense_date', 'provider', 'payment_method', 'status',
        ]);

        if ($request->hasFile('receipt') && $request->file('receipt')->isValid()) {
            if ($expense->receipt && Storage::disk('public')->exists($expense->receipt)) {
                Storage::disk('public')->delete($expense->receipt);
            }
            $data['receipt'] = $request->file('receipt')->store('expenses', 'public');
        }

        $expense->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Egreso actualizado correctamente.',
            'data' => $expense->fresh('registeredBy:id,name,username'),
        ]);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Egreso eliminado correctamente.',
        ]);
    }
}
