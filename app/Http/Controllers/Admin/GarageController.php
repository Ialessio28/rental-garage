<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin: create garages in batches and see their status.
 */
class GarageController extends Controller
{
    /**
     * Show the "create garages" form and the list of all garages with today's status.
     */
    public function index(): View
    {
        $garages = Garage::withExists(['rentals as is_used' => fn ($rental) => $rental->activeOn(today())])
            ->orderBy('type')->orderBy('code')->get();

        return view('admin.garages', ['garages' => $garages]);
    }

    /**
     * Create a batch of garages of one type.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Garage::PREFIXES))],
            'count' => ['required', 'integer', 'min:1', 'max:' . Garage::MAX_PER_TYPE],
            'daily_price' => ['required', 'integer', 'min:1'],
            'kwh_price' => ['exclude_unless:type,electric', 'required', 'integer', 'min:1'],
        ]);

        $spaceLeft = Garage::MAX_PER_TYPE - Garage::where('type', $data['type'])->count();

        if ($data['count'] > $spaceLeft) {
            return back()->withInput()->withErrors(['count' => "Only $spaceLeft more {$data['type']} garages can be created."]);
        }

        Garage::createBatch($data['type'], $data['count'], $data['daily_price'], $data['kwh_price'] ?? null);

        return back()->with('success', "{$data['count']} {$data['type']} garage(s) created.");
    }
}
