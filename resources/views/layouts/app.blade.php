<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Document Portal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-top: #eaf3ff;
            --bg-bottom: #f3fff9;
            --ink: #17253d;
            --ink-soft: #5a6a85;
            --glass: rgba(255, 255, 255, 0.62);
            --glass-strong: rgba(255, 255, 255, 0.78);
            --glass-border: rgba(255, 255, 255, 0.58);
            --accent: #1e9fb9;
            --accent-deep: #15738f;
            --success: #1f8d62;
            --shadow-soft: 0 12px 40px rgba(26, 55, 95, 0.14);
            --shadow-hard: 0 20px 45px rgba(20, 45, 72, 0.18);
        }

        body {
            min-height: 100vh;
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(80, 175, 255, 0.28), transparent 35%),
                radial-gradient(circle at 86% 12%, rgba(41, 219, 166, 0.2), transparent 32%),
                radial-gradient(circle at 50% 120%, rgba(64, 139, 255, 0.16), transparent 40%),
                linear-gradient(135deg, var(--bg-top), var(--bg-bottom));
            background-attachment: fixed;
        }

        .navbar.glass-nav {
            background: linear-gradient(115deg, rgba(20, 80, 128, 0.9), rgba(26, 137, 153, 0.85));
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.32);
            box-shadow: var(--shadow-soft);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .main-shell {
            margin-top: 1.8rem;
            margin-bottom: 2rem;
            border-radius: 20px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            padding: 1.35rem;
        }

        .card,
        .toolbar,
        #pdfContainer,
        .alert {
            border-radius: 16px !important;
            border: 1px solid var(--glass-border) !important;
            background: var(--glass-strong) !important;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .card-title,
        h3,
        h5,
        h6 {
            font-weight: 700;
            color: var(--ink);
        }

        .text-muted,
        .small {
            color: var(--ink-soft) !important;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.16s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 9px 22px rgba(18, 52, 84, 0.18);
        }

        .btn-primary,
        .btn-success {
            border: none;
            background: linear-gradient(125deg, var(--accent), var(--accent-deep));
        }

        .btn-success {
            background: linear-gradient(125deg, #27a06d, var(--success));
        }

        .btn-outline-primary {
            color: var(--accent-deep);
            border-color: rgba(21, 115, 143, 0.45);
            background: rgba(255, 255, 255, 0.45);
        }

        .btn-outline-success {
            color: var(--success);
            border-color: rgba(31, 141, 98, 0.45);
            background: rgba(255, 255, 255, 0.45);
        }

        .btn-outline-light {
            background: rgba(255, 255, 255, 0.16);
            border-color: rgba(255, 255, 255, 0.42);
        }

        .badge {
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
        }

        .pagination .page-link {
            border: 1px solid rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.7);
            color: #1f3f62;
            border-radius: 10px;
            margin: 0 3px;
        }

        .pagination .active .page-link {
            border-color: transparent;
            background: linear-gradient(125deg, var(--accent), var(--accent-deep));
            color: #fff;
        }

        @media (max-width: 768px) {
            .main-shell {
                border-radius: 16px;
                margin-top: 1rem;
                padding: 1rem;
            }

            .navbar-brand {
                font-size: 1rem;
            }
        }
    </style>
    @stack('styles')
    </head>
    <body class="bg-light">
    <nav class="navbar navbar-dark glass-nav">
        <div class="container">
        <a class="navbar-brand" href="/"><i class="fas fa-file-pdf me-2"></i>Document Portal</a>

        </div>
    </nav>
    <div class="container main-shell">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    </body>
</html>
