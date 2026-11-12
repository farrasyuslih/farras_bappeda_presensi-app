<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pegawai | Sistem Presensi</title>
</head>
<body>
    @include('partials.navigation')
    <main class="container">
        <header class="dashboard-head"><div><h1>Daftar Pegawai</h1><p>Akun ber-role pegawai yang dapat dipantau melalui sistem presensi.</p></div><a class="date-chip" href="{{ route('admin.dashboard') }}">Kembali ke dashboard</a></header>
        <section class="dashboard-panel"><div class="recent-table"><table><thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Terdaftar</th></tr></thead><tbody>
            @forelse ($employees as $employee)<tr><td>{{ $employee->id }}</td><td>{{ $employee->name }}</td><td>{{ $employee->email }}</td><td>{{ ucfirst($employee->role) }}</td><td>{{ $employee->created_at->format('d M Y H:i') }}</td></tr>
            @empty<tr><td colspan="5">Belum ada akun pegawai.</td></tr>@endforelse
        </tbody></table></div>
        @if ($employees->hasPages())
            <nav class="pagination" aria-label="Navigasi halaman pegawai">
                <form method="GET" action="{{ route('admin.employees.index') }}">
                    <label for="pagination-page">Halaman <input id="pagination-page" aria-label="Nomor halaman"
                            type="number" name="page" min="1" max="{{ $employees->lastPage() }}"
                            value="{{ $employees->currentPage() }}"></label>
                    <span>dari {{ $employees->lastPage() }}</span>
                    <button type="submit" hidden>Buka halaman</button>
                </form>
                <div class="pagination-actions">
                    @foreach ([['Pertama', '«', $employees->url(1), $employees->onFirstPage(), null], ['Sebelumnya', '‹', $employees->previousPageUrl(), $employees->onFirstPage(), 'prev']] as [$label, $icon, $url, $disabled, $relation])
                        @if ($disabled)
                            <span class="page-link page-arrow" aria-disabled="true" aria-label="{{ $label }}">{{ $icon }}</span>
                        @else
                            <a class="page-link page-arrow" href="{{ $url }}" aria-label="{{ $label }}" title="{{ $label }}" @if ($relation) rel="{{ $relation }}" @endif>{{ $icon }}</a>
                        @endif
                    @endforeach
                    @foreach (array_filter(Illuminate\Pagination\UrlWindow::make($employees->onEachSide(1))) as $pages)
                        @if (!$loop->first) <span class="page-ellipsis" aria-hidden="true">…</span> @endif
                        @if (is_array($pages))
                            @foreach ($pages as $page => $url)
                                @if ($page === $employees->currentPage())
                                    <span class="page-link" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>
                                @else
                                    <a class="page-link" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                    @foreach ([['Berikutnya', '›', $employees->nextPageUrl(), !$employees->hasMorePages(), 'next'], ['Terakhir', '»', $employees->url($employees->lastPage()), !$employees->hasMorePages(), null]] as [$label, $icon, $url, $disabled, $relation])
                        @if ($disabled)
                            <span class="page-link page-arrow" aria-disabled="true" aria-label="{{ $label }}">{{ $icon }}</span>
                        @else
                            <a class="page-link page-arrow" href="{{ $url }}" aria-label="{{ $label }}" title="{{ $label }}" @if ($relation) rel="{{ $relation }}" @endif>{{ $icon }}</a>
                        @endif
                    @endforeach
                </div>
            </nav>
        @endif
        </section>
    </main>
</body>
</html>
