<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Document Portal')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @stack('styles')
    </head>
    <body class="bg-light">
    <nav class="navbar navbar-dark" style="background:#6c3483">
        <div class="container">
        <a class="navbar-brand" href="/"><i class="fas fa-file-pdf me-2"></i>Document Portal</a>
        <a href="/documents/upload" class="btn btn-outline-light btn-sm">Upload PDF</a>
        </div>
    </nav>
    <div class="container mt-4">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    </body>
</html>
