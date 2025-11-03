<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile edit form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information and photo.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $user = $request->user();

    // --------------------------------------------------
    // 🧹 Remove existing photo if requested
    // --------------------------------------------------
    if ($request->has('remove_photo') && $user->photo) {
        if (Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }
        $user->photo = null;
    }

    // --------------------------------------------------
    // 📸 Handle new upload safely
    // --------------------------------------------------
    if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
        // Delete the old one if exists
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        // ✅ Force storing inside public/profile-photos/
        $file = $request->file('photo');
        $filename = uniqid('user_') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('profile-photos', $filename, 'public');

        // Store relative path only (no temp path)
        $user->photo = $path;
    }

    // --------------------------------------------------
    // ✏️ Update name/email
    // --------------------------------------------------
    $user->fill($request->only(['name', 'email']));

    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }

    $user->save();

    // --------------------------------------------------
    // ✅ Redirect with status
    // --------------------------------------------------
    return Redirect::route('profile.edit')
        ->with('status', 'profile-updated');
}


    /**
     * Permanently delete the user's account and profile photo.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Log out before deleting
        Auth::logout();

        // Delete stored photo
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        // Delete user record
        $user->delete();

        // End session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
