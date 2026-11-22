@extends('layout')

@section('content')
    <h3>Extend Garage {{ $rental->garage->code }}</h3>

    <table class="table table-sm w-auto">
        <tr><th>Tenant</th><td>{{ $rental->user->name }}</td></tr>
        <tr><th>Vehicle</th><td>{{ $rental->brand_model }} {{ $rental->plate_number }}</td></tr>
        <tr><th>Daily price</th><td>@rupiah($rental->garage->daily_price)</td></tr>
        <tr><th>Current end date</th><td>{{ $rental->end_date->format('d M Y') }}</td></tr>
    </table>

    {{-- Step 1: choose the new end date and see the remaining cost --}}
    <form method="GET" action="{{ route('admin.extensions.edit', $rental) }}" class="card card-body mb-3">
        <label class="form-label fw-bold">New end date</label>
        <div class="d-flex gap-2">
            <input type="date" name="new_end_date" value="{{ request('new_end_date') }}"
                   min="{{ $rental->end_date->copy()->addDay()->toDateString() }}" class="form-control" required>
            <button class="btn btn-secondary">Calculate</button>
        </div>
    </form>

    {{-- Step 2: upload payment proof and save --}}
    @if ($extraCost !== null)
        <form method="POST" action="{{ route('admin.extensions.update', $rental) }}" enctype="multipart/form-data" class="card card-body">
            @csrf
            <input type="hidden" name="new_end_date" value="{{ request('new_end_date') }}">
            <p class="mb-2">Remaining cost to pay:
                <strong>@rupiah($extraCost)</strong></p>
            <div class="mb-3">
                <label class="form-label">Payment proof (image)</label>
                <input type="file" name="payment_proof" accept="image/*" class="form-control" required>
            </div>
            <button class="btn btn-primary">Save extension</button>
        </form>
    @endif
@endsection
