<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Sistem Presensi</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 32px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .subtitle {
            margin: 0 0 24px;
            color: #64748b;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }

        input:focus {
            border-color: #2563eb;
            outline: 3px solid rgba(37, 99, 235, 0.15);
        }

        input.is-invalid {
            border-color: #dc2626;
        }

        .field-error {
            margin: 6px 0 0;
            color: #b91c1c;
            font-size: 14px;
        }

        .alert {
            margin-bottom: 20px;
            padding: 12px 14px;
            border-radius: 8px;
            line-height: 1.4;
        }

        .alert-success {
            border: 1px solid #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .alert-error {
            border: 1px solid #fca5a5;
            background: #fef2f2;
            color: #991b1b;
        }

        button {
            width: 100%;
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }
    </style>
</head>

<body>
    <main class="login-card">
        <h1>Login</h1>

        <p class="subtitle">
            Masuk ke Sistem Presensi dan Laporan Presensi.
        </p>

        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                Data login belum valid. Periksa kembali email dan password.
            </div>
        @endif

        <form method="POST" action="{{ route('login.authenticate') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="@error('email') is-invalid @enderror"
                    autocomplete="email"
                    required
                    autofocus
                    aria-describedby="email-error"
                >

                @error('email')
                    <p id="email-error" class="field-error">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="@error('password') is-invalid @enderror"
                    autocomplete="current-password"
                    required
                    aria-describedby="password-error"
                >

                @error('password')
                    <p id="password-error" class="field-error">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit">
                Masuk
            </button>
        </form>
    </main>
</body>
</html>