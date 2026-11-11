<header class="dashboard-head"><div><h1>Dashboard Presensi Pegawai </h1><p>Ringkasan presensi pribadi Anda · Asia/Jakarta</p></div><span class="date-chip">{{ $today->format('d M Y') }}</span></header>
<div class="personal-identity"><h2>{{ auth()->user()->name }}</h2><p class="muted">{{ auth()->user()->email }} · {{ ucfirst(auth()->user()->role) }}</p></div>
<p class="muted">Bulan berjalan: {{ $today->copy()->startOfMonth()->format('d M Y') }} – {{ $today->format('d M Y') }}</p>
<div class="metric-grid">
@foreach(['hadir' => 'Hadir tepat waktu', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'sakit' => 'Sakit'] as $status => $label)
<article class="metric"><p>{{ $label }}</p><strong>{{ $summary[$status] }}</strong><small>Catatan bulan berjalan</small></article>
@endforeach
</div>
<div class="dashboard-columns">
<section class="dashboard-panel"><h2>Presensi tujuh hari terakhir</h2><p class="muted">{{ $week->first()['date'] }} – {{ $week->last()['date'] }} · Jumlah catatan per hari</p>
<div class="chart-key"><span>● Hadir tepat waktu</span><span>● Terlambat</span></div>
<svg class="chart" viewBox="0 0 600 190" role="img" aria-labelledby="week-title week-desc"><title id="week-title">Tren check-in pribadi tujuh hari</title><desc id="week-desc">@foreach($week as $day){{ $day['date'] }}: {{ $day['status'] ?? 'Belum tercatat' }}. @endforeach</desc>
@foreach([35,135] as $y)<line x1="35" y1="{{ $y }}" x2="565" y2="{{ $y }}" stroke="#e8eaf0"/>@endforeach
<text x="12" y="40" fill="#777d8b" font-size="12">1</text><text x="12" y="140" fill="#777d8b" font-size="12">0</text>
@foreach(['hadir' => '#0ac213', 'terlambat' => '#bd475c'] as $status => $color)
<polyline fill="none" stroke="{{ $color }}" stroke-width="2.5" points="@foreach($week as $i => $day){{ 45 + $i * 85 }},{{ $day['status'] === $status ? 35 : 135 }} @endforeach"/>
@foreach($week as $i => $day)<circle cx="{{ 45 + $i * 85 }}" cy="{{ $day['status'] === $status ? 35 : 135 }}" r="4" fill="{{ $color }}"/>@endforeach
@endforeach
@foreach($week as $i => $day)<text x="{{ 45 + $i * 85 }}" y="170" text-anchor="middle" fill="#777d8b" font-size="11">{{ substr($day['date'], 5) }}</text>@endforeach
</svg><p class="muted">Nilai 0 berarti tidak ada check-in pada kategori tersebut; bukan penetapan alpa. Izin/sakit tersedia pada rincian presensi.</p></section>
<section class="dashboard-panel"><h2>Ketepatan waktu bulan ini</h2><div class="rate-ring" style="--rate:{{ $onTime ?? 0 }}"><span>{{ $onTime === null ? '—' : $onTime.'%' }}</span></div><p class="muted">{{ $onTime === null ? 'Belum ada data check-in bulan ini.' : 'Hadir tepat waktu ÷ seluruh check-in (hadir + terlambat).' }}</p><div class="status-row"><span>Check-in tercatat</span><strong>{{ $summary['hadir'] + $summary['terlambat'] }}</strong></div><div class="status-row"><span>Izin dan sakit</span><strong>{{ $summary['izin'] + $summary['sakit'] }}</strong></div></section>
</div>
