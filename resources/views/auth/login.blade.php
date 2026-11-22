@extends('layout')

@section('content')
    <div class="card mx-auto" style="max-width: 420px">
        <div class="card-body">
            <h4 class="mb-3">Login</h4>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Login</button>
            </form>
            <p class="mt-3 mb-0 text-center">No account? <a href="{{ route('register') }}">Register as tenant</a></p>
        </div>
    </div>
@endsection
