<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Garage Rental</title>
    {{-- Library: Bootstrap 5.3.3 (MIT license), loaded from the jsDelivr CDN.
         Purpose: ready-made CSS for forms, tables and the navbar, so no CSS is written by hand.
         Docs: https://getbootstrap.com/docs/5.3 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand">Garage Rental</span>
        @auth
            <div class="d-flex align-items-center gap-3">
                @if (auth()->user()->isAdmin())
                    <a class="link-light" href="{{ route('admin.garages.index') }}">Garages</a>
                    <a class="link-light" href="{{ route('admin.tenants.index') }}">Verify Tenants</a>
                    <a class="link-light" href="{{ route('admin.extensions.index') }}">Extend Rental</a>
                    <a class="link-light" href="{{ route('admin.reports.index') }}">Reports</a>
                @else
                    <a class="link-light" href="{{ route('rentals.create') }}">New Rental</a>
                @endif
                <span class="text-secondary">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-light">Logout</button>
                </form>
            </div>
        @endauth
    </div>
</nav>

<main class="container pb-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>
