<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Sistem Presensi</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, sans-serif;
        }
        main {
            width: 100%;
            max-width: 420px;
            padding: 32px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .12);
        }
        h1 { margin-top: 0; }
        label { display: block; margin: 16px 0 6px; font-weight: 600; }
        input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        .error { color: #b91c1c; margin: 6px 0 0; font-size: 14px; }
        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }
        a { color: #1d4ed8; }
    </style>
</head>
<body>
    <main>
        <h1>Daftar akun</h1>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <label for="name">Nama</label>
            <input id="name" name="name" value="{{ old('name') }}"
                   required maxlength="255" autocomplete="name">
            @error('name') <p class="error" role="alert">{{ $message }}</p> @enderror

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   required maxlength="255" autocomplete="email">
            @error('email') <p class="error" role="alert">{{ $message }}</p> @enderror

            <label for="password">Password</label>
            <input id="password" name="password" type="password"
                   required minlength="8" autocomplete="new-password">
            @error('password') <p class="error" role="alert">{{ $message }}</p> @enderror

            <label for="password_confirmation">Konfirmasi password</label>
            <input id="password_confirmation" name="password_confirmation"
                   type="password" required minlength="8"
                   autocomplete="new-password">

            <button type="submit">Daftar</button>
        </form>

        <p>Sudah punya akun? <a href="{{ route('login') }}">Login</a></p>
    </main>
</body>
</html>