<?php

namespace App\Http\Controllers;

use App\Models\PartnerCompany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $partners = PartnerCompany::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where('name', 'like', $term)
                  ->orWhere('contact_person', 'like', $term)
                  ->orWhere('phone', 'like', $term);
            })
            ->withCount('itemLoans')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('partner_companies.index', compact('partners'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255', 'unique:partner_companies,name'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'email'          => ['nullable', 'email', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
            'notes'          => ['nullable', 'string'],
        ]);

        $partner = PartnerCompany::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Partner company created successfully.',
                'partner' => $partner,
            ]);
        }

        return redirect()->back()->with('success', 'Partner company created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PartnerCompany $partnerCompany)
    {
        return view('partner_companies.edit', ['partner' => $partnerCompany]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PartnerCompany $partnerCompany)
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255', Rule::unique('partner_companies')->ignore($partnerCompany)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:50'],
            'email'          => ['nullable', 'email', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
            'notes'          => ['nullable', 'string'],
        ]);

        $partnerCompany->update($validated);

        return redirect()->route('partner-companies.index')->with('success', 'Partner updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PartnerCompany $partnerCompany)
    {
        if ($partnerCompany->itemLoans()->exists()) {
            return back()->withErrors(['delete' => 'Cannot delete partner with existing loans.']);
        }

        $partnerCompany->delete();

        return redirect()->route('partner-companies.index')->with('success', 'Partner deleted successfully.');
    }
}
