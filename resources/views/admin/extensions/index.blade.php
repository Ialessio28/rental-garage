@extends('layout')

@section('content')
    <h3>Extend Rental</h3>
    <p class="text-muted">Garages rented today ({{ today()->format('d M Y') }}).</p>

    <table class="table table-bordered bg-white">
        <thead>
        <tr><th>Garage</th><th>Tenant</th><th>Vehicle</th><th>Start</th><th>End</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($rentals as $rental)
            <tr>
                <td>{{ $rental->garage->code }}</td>
                <td>{{ $rental->user->name }}</td>
                <td>{{ $rental->brand_model }} {{ $rental->plate_number }}</td>
                <td>{{ $rental->start_date->format('d M Y') }}</td>
                <td>{{ $rental->end_date->format('d M Y') }}</td>
                <td><a href="{{ route('admin.extensions.edit', $rental) }}" class="btn btn-sm btn-primary">Extend</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">No garage is rented today.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
