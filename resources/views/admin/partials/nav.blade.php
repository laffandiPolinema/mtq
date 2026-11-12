<nav class="admin-tabs" aria-label="Menu panitia">
    <a href="{{ route('admin.participants.index') }}" @class(['active' => request()->routeIs('admin.participants.*')])>Data peserta</a>
    <a href="{{ route('admin.branches.index') }}" @class(['active' => request()->routeIs('admin.branches.*')])>Cabang &amp; biaya</a>
</nav>
