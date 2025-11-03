<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // ✅ Spatie gives us roles directly
        $roles = $user->getRoleNames()->toArray();

        // 🔁 Use lowercase just in case
        $roles = array_map('strtolower', $roles);

        if (in_array('admin', $roles, true)) {
            return redirect()->route('dashboard');
        }

        if (in_array('manager', $roles, true)) {
            return redirect()->route('transactions.index');
        }

        if (in_array('cashier', $roles, true)) {
            return redirect()->route('sales.index');
        }

        // Fallback — unknown role
        return redirect()->route('dashboard');
    }
}
