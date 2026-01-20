<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class SystemResetController extends Controller
{
    /**
     * Show app reset page
     */
    public function index()
    {
        // Only allow admin/boss role
        if (!Auth::user()->hasRole(['admin', 'boss'])) {
            abort(403, 'Only administrators can access this feature.');
        }

        return view('system.reset');
    }

    /**
     * Execute system reset
     */
    public function reset(Request $request)
    {
        // Only allow admin/boss role
        if (!Auth::user()->hasRole(['admin', 'boss'])) {
            abort(403, 'Only administrators can reset the system.');
        }

        $request->validate([
            'password' => 'required|string',
            'confirm_text' => 'required|in:RESET',
        ], [
            'confirm_text.in' => 'You must type RESET to confirm',
        ]);

        // Verify password
        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password']);
        }

        try {
            DB::beginTransaction();

            // Tables to truncate (preserve users, roles, permissions, categories)
            $tablesToClear = [
                // Products and inventory
                'products',
                'stock_movements',
                'stock_adjustments',
                
                // Sales and purchases
                'sales',
                'sale_items',
                'sale_returns',
                'purchases',
                'purchase_items',
                'purchase_returns',
                
                // Transactions and finance
                'transactions',
                'loans',
                'loan_payments',
                'item_loans',
                'debits_credits',
                'expenses',
                
                // Customers and suppliers
                'customers',
                'suppliers',
                'partner_companies',
                
                // Other transactional data
                'notifications',
            ];

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($tablesToClear as $table) {
                // Check if table exists before truncating
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->truncate();
                    Log::info("Truncated table: {$table}");
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            DB::commit();

            Log::warning('System reset completed', [
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'timestamp' => now(),
            ]);

            return redirect()->route('dashboard')->with('success', 
                'System reset completed successfully! All data has been cleared except users and categories.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('System reset failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            
            return back()->withErrors(['error' => 'Reset failed: ' . $e->getMessage()]);
        }
    }
}
