@extends('layout')

@section('content')
    <h3>Rental Reports</h3>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md">
                <label class="form-label">Report</label>
                <select name="type" class="form-select">
                    <option value="month" @selected($type === 'month')>Per month in a year</option>
                    <option value="day" @selected($type === 'day')>Per day in a month</option>
                    <option value="garage_type" @selected($type === 'garage_type')>Per garage type</option>
                    <option value="tenant" @selected($type === 'tenant')>Per tenant</option>
                </select>
            </div>
            <div class="col-md">
                <label class="form-label">Year (month / day report)</label>
                <input type="number" name="year" value="{{ $year }}" class="form-control">
            </div>
            <div class="col-md">
                <label class="form-label">Month (day report)</label>
                <input type="number" name="month" min="1" max="12" value="{{ $month }}" class="form-control">
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary">Show</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered bg-white">
        <thead>
        <tr><th>Group</th><th>Number of rentals</th><th>Total paid</th></tr>
        </thead>
        <tbody>
        @forelse ($groups as $label => $rentals)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ $rentals->count() }}</td>
                <td>@rupiah($rentals->sum('total_cost'))</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted">No rentals found.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr class="table-secondary">
            <th>Total</th>
            <th>{{ $groups->flatten()->count() }}</th>
            <th>@rupiah($groups->flatten()->sum('total_cost'))</th>
        </tr>
        </tfoot>
    </table>
@endsection
