<nav class="admin-tabs" aria-label="Menu panitia">
    <a href="{{ route('admin.participants.index') }}" @class(['active' => request()->routeIs('admin.participants.*')])>Data peserta</a>
    <a href="{{ route('admin.branches.index') }}" @class(['active' => request()->routeIs('admin.branches.*')])>Cabang &amp; biaya</a>
    <a href="{{ route('admin.juknis.edit') }}" @class(['active' => request()->routeIs('admin.juknis.*')])>Juknis</a>
    <a href="{{ route('admin.schedule.edit') }}" @class(['active' => request()->routeIs('admin.schedule.*')])>Jadwal acara</a>
    <a href="{{ route('admin.prizes.edit') }}" @class(['active' => request()->routeIs('admin.prizes.*')])>Hadiah</a>
</nav>
