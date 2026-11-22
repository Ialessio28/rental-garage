<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\RentalCalculator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin: extend a running rental (move its end date later).
 */
class ExtensionController extends Controller
{
    public function __construct(private RentalCalculator $calculator)
    {
    }

    /**
     * List garages that are rented today.
     */
    public function index(): View
    {
        $rentals = Rental::with(['garage', 'user'])->activeOn(today())->orderBy('end_date')->get();

        return view('admin.extensions.index', ['rentals' => $rentals]);
    }

    /**
     * Show the extend form. When a new end date is given, also show the remaining cost.
     */
    public function edit(Request $request, Rental $rental): View
    {
        $this->ensureActiveToday($rental);
        $extraCost = null;

        if ($request->filled('new_end_date')) {
            $newEnd = $this->validateNewEndDate($request, $rental);
            $extraCost = $this->calculator->extensionCost($rental->garage->daily_price, $rental->end_date, $newEnd);
        }

        return view('admin.extensions.edit', ['rental' => $rental, 'extraCost' => $extraCost]);
    }

    /**
     * Save the extension: record it with the payment proof and update the rental.
     */
    public function update(Request $request, Rental $rental): RedirectResponse
    {
        $this->ensureActiveToday($rental);
        $request->validate(['payment_proof' => ['required', 'image', 'max:' . Rental::MAX_PROOF_KB]]);
        $newEnd = $this->validateNewEndDate($request, $rental);
        $extraCost = $this->calculator->extensionCost($rental->garage->daily_price, $rental->end_date, $newEnd);

        DB::transaction(function () use ($request, $rental, $newEnd, $extraCost) {
            $rental->extensions()->create([
                'old_end_date' => $rental->end_date,
                'new_end_date' => $newEnd,
                'extra_cost' => $extraCost,
                'payment_proof' => $request->file('payment_proof')->store('proofs', 'public'),
            ]);

            $rental->update([
                'end_date' => $newEnd,
                'total_cost' => $rental->total_cost + $extraCost,
            ]);
        });

        return redirect()->route('admin.extensions.index')->with('success',
            "Garage {$rental->garage->code} extended until {$newEnd->format('d M Y')}. "
            . 'Paid: Rp ' . number_format($extraCost, 0, ',', '.'));
    }

    /**
     * The new end date must be after the current end date (extending only),
     * and the garage must not be booked by another rental in the added days.
     *
     * @param  Request  $request  the submitted form
     * @param  Rental   $rental   the rental being extended
     * @return Carbon  the validated new end date
     */
    private function validateNewEndDate(Request $request, Rental $rental): Carbon
    {
        $request->validate([
            'new_end_date' => ['required', 'date', 'after:' . $rental->end_date->toDateString()],
        ]);

        $newEnd = Carbon::parse($request->input('new_end_date'))->startOfDay();

        $booked = Rental::where('garage_id', $rental->garage_id)
            ->whereKeyNot($rental->id)
            ->overlapping($rental->end_date->copy()->addDay(), $newEnd)
            ->exists();

        if ($booked) {
            throw ValidationException::withMessages([
                'new_end_date' => 'The garage is already booked by another rental in these dates.',
            ]);
        }

        return $newEnd;
    }

    /**
     * Only rentals running today can be extended.
     */
    private function ensureActiveToday(Rental $rental): void
    {
        abort_unless(Rental::activeOn(today())->whereKey($rental->id)->exists(), 404);
    }
}
