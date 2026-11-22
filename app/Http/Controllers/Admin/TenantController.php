<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin: check new tenants' NIK and accept or reject them.
 */
class TenantController extends Controller
{
    /**
     * List tenants waiting for verification.
     */
    public function index(): View
    {
        $tenants = User::where('role', 'tenant')->where('status', 'pending')->oldest()->get();

        return view('admin.tenants', ['tenants' => $tenants]);
    }

    /**
     * Accept a tenant so they can log in.
     */
    public function approve(User $user): RedirectResponse
    {
        $user->update(['status' => 'approved']);

        return back()->with('success', "{$user->name} has been approved.");
    }

    /**
     * Reject a tenant; they will not be able to log in.
     */
    public function reject(User $user): RedirectResponse
    {
        $user->update(['status' => 'rejected']);

        return back()->with('success', "{$user->name} has been rejected.");
    }
}
