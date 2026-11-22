@extends('layout')

@section('content')
    <h3>Garages</h3>

    <form method="POST" action="{{ route('admin.garages.store') }}" class="card card-body mb-4">
        @csrf
        <label class="form-label fw-bold">Create garages</label>
        <div class="row g-2 align-items-end">
            <div class="col-md">
                <label class="form-label">Garage type</label>
                <select name="type" class="form-select">
                    <option value="bicycle" @selected(old('type') === 'bicycle')>Bicycle (S)</option>
                    <option value="car" @selected(old('type') === 'car')>Car (C)</option>
                    <option value="electric" @selected(old('type') === 'electric')>Electric car (E)</option>
                </select>
            </div>
            <div class="col-md">
                <label class="form-label">Number of garages (max 999)</label>
                <input type="number" name="count" min="1" max="999" value="{{ old('count') }}" class="form-control" required>
            </div>
            <div class="col-md">
                <label class="form-label">Daily price (Rp)</label>
                <input type="number" name="daily_price" min="1" value="{{ old('daily_price') }}" class="form-control" required>
            </div>
            <div class="col-md">
                <label class="form-label">Charging price per kWh (electric only)</label>
                <input type="number" name="kwh_price" min="1" value="{{ old('kwh_price') }}" class="form-control">
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary">Create</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered bg-white">
        <thead>
        <tr><th>Code</th><th>Type</th><th>Daily price</th><th>Monthly price</th><th>Charging / kWh</th><th>Status today</th></tr>
        </thead>
        <tbody>
        @forelse ($garages as $garage)
            <tr>
                <td>{{ $garage->code }}</td>
                <td>{{ ucfirst($garage->type) }}</td>
                <td>@rupiah($garage->daily_price)</td>
                <td>@rupiah($garage->daily_price * \App\Services\RentalCalculator::MONTHLY_MULTIPLIER)</td>
                <td>@if ($garage->kwh_price) @rupiah($garage->kwh_price) @else - @endif</td>
                <td>
                    <span class="badge {{ $garage->is_used ? 'bg-danger' : 'bg-success' }}">
                        {{ $garage->is_used ? 'used' : 'available' }}
                    </span>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">No garages yet.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
