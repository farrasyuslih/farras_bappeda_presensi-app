<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Sistem Presensi</title>
</head>
<body>
    @include('partials.navigation')
    <main class="container">
        <header class="dashboard-head"><div><h1>Dashboard Admin</h1><p>Monitoring presensi seluruh pegawai · Asia/Jakarta</p></div><span class="date-chip">{{ $today->format('d M Y') }}</span></header>
        <p class="muted">Bulan berjalan: {{ $today->copy()->startOfMonth()->format('d M Y') }} – {{ $today->format('d M Y') }}</p>
        <div class="metric-grid">
            <a class="metric metric-link" href="{{ route('admin.employees.index') }}"><p>Total pegawai</p><strong>{{ $employeeCount }}</strong><small>Lihat daftar akun pegawai →</small></a>
            <article class="metric"><p>Ketepatan waktu</p><strong>{{ $onTime === null ? '—' : $onTime.'%' }}</strong><small>Hadir tepat waktu ÷ seluruh check-in</small></article>
            <article class="metric"><p>Pegawai terlambat</p><strong>{{ $lateEmployeeCount }}</strong><small>Pegawai unik dengan ≥1 keterlambatan bulan ini</small></article>
            <article class="metric"><p>Presensi hari ini</p><strong>{{ array_sum($todaySummary) }}</strong><small>Hadir, terlambat, izin, atau sakit</small></article>
        </div>
        <div class="dashboard-columns">
            <section class="dashboard-panel"><h2>Presensi tujuh hari terakhir</h2><p class="muted">Jumlah check-in per hari; izin dan sakit tidak dihitung sebagai check-in.</p>
                <div class="chart-key"><span>● Tepat waktu (≤ 07:30)</span><span>● Terlambat (&gt; 07:30)</span></div>
                <svg class="chart" viewBox="0 0 600 220" role="img" aria-labelledby="admin-week-title admin-week-desc"><title id="admin-week-title">Tren check-in seluruh pegawai tujuh hari</title><desc id="admin-week-desc">@foreach($week as $day){{ $day['date'] }}: {{ $day['hadir'] }} tepat waktu, {{ $day['terlambat'] }} terlambat. @endforeach</desc>
                    <text x="50" y="15" fill="#777d8b" font-size="11">Jumlah check-in</text>
                    @foreach($chartTicks as $tick) @php($y = 150 - ($tick / $chartMax * 100)) <line x1="55" y1="{{ $y }}" x2="565" y2="{{ $y }}" stroke="#e8eaf0"/><text x="45" y="{{ $y + 4 }}" text-anchor="end" fill="#777d8b" font-size="11">{{ $tick }}</text> @endforeach
                    @foreach(['hadir' => '#0ac213', 'terlambat' => '#bd475c'] as $status => $color)<polyline fill="none" stroke="{{ $color }}" stroke-width="2.5" points="@foreach($week as $i => $day){{ 60 + $i * 83 }},{{ 150 - ($day[$status] / $chartMax * 100) }} @endforeach"/>@foreach($week as $i => $day)<circle cx="{{ 60 + $i * 83 }}" cy="{{ 150 - ($day[$status] / $chartMax * 100) }}" r="3.5" fill="{{ $color }}"/>@endforeach @endforeach
                    @foreach($week as $i => $day)<text x="{{ 60 + $i * 83 }}" y="178" text-anchor="middle" fill="#777d8b" font-size="11">{{ substr($day['date'], 5) }}</text>@endforeach
                    <text x="310" y="210" text-anchor="middle" fill="#777d8b" font-size="11">Tanggal</text>
                </svg>
            </section>
            <section class="dashboard-panel"><h2>Status hari ini</h2>
                @foreach(['hadir' => 'Hadir tepat waktu', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'sakit' => 'Sakit'] as $status => $label)<div class="status-row"><span>{{ $label }}</span><strong>{{ $todaySummary[$status] }}</strong></div>@endforeach
            </section>
        </div>
        <section class="dashboard-panel"><div class="recent-head"><div><h2>Presensi terbaru</h2><p class="muted">Maksimal 10 catatan seluruh pegawai.</p></div><a href="{{ route('admin.attendances.index') }}">Lihat seluruh presensi →</a></div>
            <div class="recent-table"><table><thead><tr><th>Pegawai</th><th>Tanggal</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead><tbody>@forelse($recentAttendances as $attendance)<tr><td>{{ $attendance->user->name }}</td><td>{{ $attendance->attendance_date }}</td><td>{{ $attendance->check_in_time ?? '—' }}</td><td>{{ $attendance->check_out_time ?? ($attendance->check_in_time ? 'Belum check-out' : '—') }}</td><td><span class="status-pill {{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span></td></tr>@empty<tr><td colspan="5">Belum ada presensi pegawai.</td></tr>@endforelse</tbody></table></div>
        </section>
    </main>
</body>
</html>
