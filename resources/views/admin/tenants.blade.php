@extends('layout')

@section('content')
    <h3>Verify New Tenants</h3>
    <p class="text-muted">Check each tenant's NIK against their KTP, then accept or reject.</p>

    <table class="table table-bordered bg-white">
        <thead>
        <tr><th>NIK</th><th>Name</th><th>Phone</th><th>Email</th><th>Registered</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($tenants as $tenant)
            <tr>
                <td>{{ $tenant->nik }}</td>
                <td>{{ $tenant->name }}</td>
                <td>{{ $tenant->phone }}</td>
                <td>{{ $tenant->email }}</td>
                <td>{{ $tenant->created_at->format('d M Y') }}</td>
                <td class="d-flex gap-2">
                    <form method="POST" action="{{ route('admin.tenants.approve', $tenant) }}">
                        @csrf
                        <button class="btn btn-sm btn-success">Accept</button>
                    </form>
                    <form method="POST" action="{{ route('admin.tenants.reject', $tenant) }}">
                        @csrf
                        <button class="btn btn-sm btn-danger">Reject</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">No tenants waiting for verification.</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
