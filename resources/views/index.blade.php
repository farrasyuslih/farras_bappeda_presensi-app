<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Sistem Presensi</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, sans-serif;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 28px;
            background: #1d4ed8;
            color: #ffffff;
        }

        .navbar h1 {
            margin: 0;
            font-size: 21px;
        }

        .logout-button {
            padding: 9px 15px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 7px;
            background: transparent;
            color: #ffffff;
            cursor: pointer;
            font-weight: 600;
        }

        .logout-button:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .container {
            width: min(100% - 32px, 1000px);
            margin: 32px auto;
        }

        .card {
            padding: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
        }

        .alert-success {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #86efac;
            border-radius: 8px;
            background: #f0fdf4;
            color: #166534;
        }

        .user-information {
            margin-bottom: 24px;
        }

        .user-information p {
            margin: 8px 0;
        }

        .empty-state {
            padding: 40px 20px;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            text-align: center;
        }

        .empty-state h2 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .empty-state p {
            margin: 0;
            color: #64748b;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <h1>Sistem Presensi</h1>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button class="logout-button" type="submit">
                Logout
            </button>
        </form>
    </nav>

    <main class="container">
        @if (session('success'))
            <div class="alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <section class="card">
            <div class="user-information">
                <h2>Selamat datang, {{ auth()->user()->name }}</h2>

                <p>
                    Email: {{ auth()->user()->email }}
                </p>

                <p>
                    Role: {{ ucfirst(auth()->user()->role) }}
                </p>
            </div>

            <div class="empty-state">
                <h2>Belum ada aktivitas presensi</h2>

                <p>
                    Fitur check-in dan check-out akan tersedia pada fase berikutnya.
                </p>
            </div>
        </section>
    </main>
</body>
</html>