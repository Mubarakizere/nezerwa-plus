<?php

namespace App\Http\Controllers;

use App\Models\PaymentChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentChannelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payment-channels.view')->only(['index', 'show']);
        $this->middleware('permission:payment-channels.create')->only(['create', 'store']);
        $this->middleware('permission:payment-channels.edit')->only(['edit', 'update']);
        $this->middleware('permission:payment-channels.delete')->only(['destroy']);
    }
    public function index()
    {
        $channels = PaymentChannel::latest()->paginate(10);
        return view('payment_channels.index', compact('channels'));
    }

    public function create()
    {
        return view('payment_channels.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_channels,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        PaymentChannel::create($validated);

        return redirect()->route('payment-channels.index')
            ->with('success', 'Payment channel created successfully.');
    }

    public function edit(PaymentChannel $paymentChannel)
    {
        return view('payment_channels.edit', compact('paymentChannel'));
    }

    public function update(Request $request, PaymentChannel $paymentChannel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:payment_channels,name,' . $paymentChannel->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $paymentChannel->update($validated);

        return redirect()->route('payment-channels.index')
            ->with('success', 'Payment channel updated successfully.');
    }

    public function destroy(PaymentChannel $paymentChannel)
    {
        $paymentChannel->delete();

        return redirect()->route('payment-channels.index')
            ->with('success', 'Payment channel deleted successfully.');
    }
}
