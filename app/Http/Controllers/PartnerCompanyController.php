<?php

namespace App\Http\Controllers;

use App\Models\PartnerCompany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerCompanyController extends Controller
{
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
}
