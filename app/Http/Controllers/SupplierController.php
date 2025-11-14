<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // Show list of suppliers
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));

        $suppliersQuery = Supplier::query();

        if ($query !== '') {
            $suppliersQuery->where(function ($qB) use ($query) {
                $qB->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            });
        }

        $suppliers = $suppliersQuery
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalSuppliers = Supplier::count();

        return view('suppliers.index', [
            'suppliers'      => $suppliers,
            'query'          => $query,
            'totalSuppliers' => $totalSuppliers,
        ]);
    }

    // Show create form
    public function create()
    {
        return view('suppliers.create');
    }

    // Store supplier
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        Supplier::create($data);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    // Show edit form
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    // Update supplier
    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $supplier->update($data);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    // Delete supplier
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
