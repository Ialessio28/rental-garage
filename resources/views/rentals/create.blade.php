@extends('layout')

@section('content')
    <h3>New Rental</h3>

    {{-- Step 1: vehicle type, garage type and rental period --}}
    <form method="GET" action="{{ route('rentals.create') }}" class="card card-body mb-3">
        <label class="form-label fw-bold">1. Vehicle, garage type and rental period</label>
        <div class="mb-2">
            <label class="form-label">Vehicle type and garage type</label>
            <select name="choice" class="form-select" required>
                <option value="">-- choose --</option>
                @foreach ($choices as $value => $label)
                    <option value="{{ $value }}" @selected(old('choice', $choice) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="row mb-3">
            <div class="col">
                <label class="form-label">Start date (today or later)</label>
                <input type="date" name="start_date" min="{{ today()->toDateString() }}"
                       value="{{ old('start_date', $fields['start_date'] ?? today()->toDateString()) }}" class="form-control" required>
            </div>
            <div class="col">
                <label class="form-label">Rental type</label>
                <select name="rental_type" class="form-select">
                    <option value="daily" @selected(old('rental_type', $fields['rental_type'] ?? '') === 'daily')>Daily</option>
                    <option value="monthly" @selected(old('rental_type', $fields['rental_type'] ?? '') === 'monthly')>Monthly (25 x daily price)</option>
                </select>
            </div>
            <div class="col">
                <label class="form-label">Number of days (daily only, max = days in start month - 1)</label>
                <input type="number" name="days" min="1" value="{{ old('days', $fields['days'] ?? '') }}" class="form-control">
            </div>
        </div>

        <button class="btn btn-secondary">Next</button>
    </form>

    {{-- Step 2: garage, vehicle details and rental period --}}
    @if ($choice)
        <form method="GET" action="{{ route('rentals.create') }}" class="card card-body mb-3">
            @foreach (['choice', 'start_date', 'rental_type', 'days'] as $name)
                <input type="hidden" name="{{ $name }}" value="{{ $fields[$name] ?? '' }}">
            @endforeach
            <input type="hidden" name="calculate" value="1">
            <label class="form-label fw-bold">2. Rental details</label>
            <p class="mb-2">Period: <strong>{{ $period['start']->format('d M Y') }} - {{ $period['end']->format('d M Y') }}</strong> ({{ $period['days'] }} days)</p>

            @if ($garages->isEmpty())
                <p class="text-danger mb-0">No {{ $garageType }} garage is free for the whole period.</p>
            @else
                <div class="mb-2">
                    <label class="form-label">{{ ucfirst($garageType) }} garages free for the whole period</label>
                    <select name="garage_id" class="form-select" required>
                        @foreach ($garages as $garage)
                            <option value="{{ $garage->id }}" @selected(old('garage_id', $fields['garage_id'] ?? null) == $garage->id)>
                                {{ $garage->code }} - @rupiah($garage->daily_price)/day
                                @if ($garage->kwh_price)
                                    , charging @rupiah($garage->kwh_price)/kWh
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($vehicleType !== 'bicycle')
                    <div class="mb-2">
                        <label class="form-label">Plate number</label>
                        <input name="plate_number" value="{{ old('plate_number', $fields['plate_number'] ?? '') }}" class="form-control" required>
                    </div>
                @endif

                <div class="mb-2">
                    <label class="form-label">Brand and type</label>
                    <input name="brand_model" value="{{ old('brand_model', $fields['brand_model'] ?? '') }}" class="form-control" required>
                </div>

                @if ($vehicleType === 'electric_car')
                    <div class="mb-2">
                        <label class="form-label">Battery capacity (kWh)</label>
                        <input type="number" step="0.01" name="battery_capacity" value="{{ old('battery_capacity', $fields['battery_capacity'] ?? '') }}" class="form-control" required>
                    </div>
                @endif

                @if ($garageType === 'electric')
                    <div class="mb-2">
                        <label class="form-label">Current charge (kWh) - the car is charged to full</label>
                        <input type="number" step="0.01" name="current_charge" value="{{ old('current_charge', $fields['current_charge'] ?? '') }}" class="form-control" required>
                    </div>
                @endif

                <button class="btn btn-secondary mt-2">Calculate total</button>
            @endif
        </form>
    @endif

    {{-- Step 3: show the total, upload payment proof, save --}}
    @if (! empty($summary))
        <form method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data" class="card card-body">
            @csrf
            @foreach ($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            <label class="form-label fw-bold">3. Payment</label>
            <table class="table table-sm w-auto">
                <tr><th>Start date</th><td>{{ $summary['start_date']->format('d M Y') }}</td></tr>
                <tr><th>End date</th><td>{{ $summary['end_date']->format('d M Y') }}</td></tr>
                <tr><th>Days</th><td>{{ $summary['days'] }}</td></tr>
                <tr><th>Rent cost</th><td>@rupiah($summary['rent_cost'])</td></tr>
                <tr><th>Charging cost</th><td>@rupiah($summary['charge_cost'])</td></tr>
                <tr class="table-primary"><th>Total</th><td><strong>@rupiah($summary['total_cost'])</strong></td></tr>
            </table>

            <div class="mb-3">
                <label class="form-label">Payment proof (image)</label>
                <input type="file" name="payment_proof" accept="image/*" class="form-control" required>
            </div>
            <button class="btn btn-primary">Save rental</button>
        </form>
    @endif
@endsection
