<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Presensi | Sistem Presensi</title>
    <style>
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif; }
        .container { width: min(100% - 32px, 1000px); margin: 32px auto; }
        .card { padding: 24px; background: #fff; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        th { background: #f8fafc; }
        .empty-state { padding: 32px; text-align: center; color: #64748b; }
    </style>
</head>
<body>
    <main class="container">
        <p><a href="{{ route('dashboard') }}">Kembali ke dashboard</a></p>
        <section class="card">
            <h1>Riwayat Presensi Saya</h1>

            @if ($attendances->isEmpty())
                <p class="empty-state">Belum ada data presensi.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->user->name }}</td>
                                <td>{{ $attendance->attendance_date }}</td>
                                <td>{{ $attendance->check_in_time ?? '—' }}</td>
                                <td>{{ $attendance->check_out_time ?? '—' }}</td>
                                <td>{{ ucfirst($attendance->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
</body>
</html>
