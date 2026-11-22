@extends('layout')

@section('content')
    <div class="card mx-auto" style="max-width: 480px">
        <div class="card-body">
            <h4 class="mb-3">Register as Tenant</h4>
            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">NIK (KTP number, 16 digits)</label>
                    <input name="nik" value="{{ old('nik') }}" class="form-control" maxlength="16" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input name="name" value="{{ old('name') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone number</label>
                    <input name="phone" value="{{ old('phone') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email (used as username)</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password (min. 8 characters)</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Register</button>
            </form>
            <p class="mt-3 mb-0 text-center"><a href="{{ route('login') }}">Back to login</a></p>
        </div>
    </div>
@endsection
