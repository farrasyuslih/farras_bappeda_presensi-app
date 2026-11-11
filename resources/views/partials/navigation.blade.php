<link rel="stylesheet" href="{{ asset('css/workspace.css') }}">
<button class="menu-toggle" type="button" aria-controls="workspace-nav" aria-expanded="false">☰ Menu</button>
<button class="nav-backdrop" type="button" aria-label="Tutup menu" tabindex="-1"></button>
<aside class="workspace-nav" id="workspace-nav" aria-label="Navigasi utama">
    <a class="workspace-brand" href="{{ route('dashboard') }}"><span class="brand-mark">P</span> Presensi<span class="brand-sub">BAPPEDA DKI Jakarta</span></a>
    <p class="nav-caption">{{ auth()->user()->isAdmin() ? 'MENU ADMIN' : 'MENU PEGAWAI' }}</p>
    <nav>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>◫ <span>Dashboard Admin</span></a>
            <a href="{{ route('admin.attendances.index') }}" @if(request()->routeIs('admin.attendances.*')) aria-current="page" @endif>▤ <span>Presensi Pegawai</span></a>
        @else
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>◫ <span>Dashboard</span></a>
            <a href="{{ route('dashboard') }}#presensi-hari-ini">◷ <span>Presensi Saya</span></a>
            <a href="{{ route('attendances.index') }}" @if(request()->routeIs('attendances.*')) aria-current="page" @endif>▤ <span>Laporan Presensi</span></a>
        @endif
    </nav>
    <div class="nav-profile"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
    </div>
</aside>
<script>
(() => {
    const toggle = document.querySelector('.menu-toggle');
    const nav = document.getElementById('workspace-nav');
    const close = () => { document.body.classList.remove('nav-open'); toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', () => { const open = document.body.classList.toggle('nav-open'); toggle.setAttribute('aria-expanded', String(open)); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && document.body.classList.contains('nav-open')) { close(); toggle.focus(); } });
    document.addEventListener('click', event => { if (!nav.contains(event.target) && !toggle.contains(event.target)) close(); });
    const mobile = window.matchMedia('(max-width:760px)');
    const sync = () => { if (!mobile.matches) close(); };
    mobile.addEventListener('change', sync);
    document.addEventListener('keydown', event => {
        if (event.key !== 'Tab' || !mobile.matches || !document.body.classList.contains('nav-open')) return;
        const items = [toggle, ...nav.querySelectorAll('a, button')];
        const first = items[0], last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', close));
})();
</script>
