<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin: rental reports per month, per day, per garage type and per tenant.
 */
class ReportController extends Controller
{
    /**
     * Show the chosen report: number of rentals and total paid for each group.
     */
    public function index(Request $request): View
    {
        $type = $request->query('type', 'month');
        $year = (int) $request->query('year', today()->year);
        $month = (int) $request->query('month', today()->month);

        $query = Rental::with(['garage', 'user'])->orderBy('start_date');

        if ($type === 'month') {
            $query->whereYear('start_date', $year);
        } elseif ($type === 'day') {
            $query->whereYear('start_date', $year)->whereMonth('start_date', $month);
        }

        $groups = $query->get()->groupBy(fn (Rental $rental) => match ($type) {
            'day' => $rental->start_date->format('d M Y'),
            'garage_type' => ucfirst($rental->garage->type),
            'tenant' => "{$rental->user->name} ({$rental->user->email})",
            default => $rental->start_date->format('F Y'),
        });

        return view('admin.reports', compact('type', 'year', 'month', 'groups'));
    }
}
