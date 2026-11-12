@extends('layouts.app')

@section('title', 'Cabang & Biaya · Panel Panitia MTQ 2026')

@section('content')
    <section class="admin-section">
        @include('admin.partials.nav')
        <div class="admin-heading">
            <div>
                <span class="section-kicker">Data master</span>
                <h1 class="display-title">Cabang &amp; biaya</h1>
                <p>Atur cabang lomba, golongan/kategori, biaya pendaftaran, dan buka-tutup pendaftaran. Perubahan langsung tampil di formulir pendaftaran.</p>
            </div>
            <a class="button button-primary" href="{{ route('admin.branches.create') }}">+ Tambah cabang</a>
        </div>

        @if (session('success')) <div class="notice notice-success">{{ session('success') }}</div> @endif

        <div class="notice notice-info master-note">
            Mengubah biaya hanya berlaku untuk pendaftar <strong>baru</strong>; peserta yang sudah mendaftar tetap memakai biaya saat mereka mendaftar.
            Cabang/kategori yang sudah memiliki peserta tidak dapat dihapus — gunakan <strong>tutup pendaftaran</strong>.
        </div>

        <div class="master-list">
            @forelse ($branches as $branch)
                @php($branchCounts = $participantCounts[$branch->code] ?? collect())
                <article class="panel master-branch">
                    <div class="master-branch-head">
                        <div>
                            <h2>{{ $branch->name }}</h2>
                            <p class="master-meta">
                                <span class="status-pill {{ $branch->is_open ? 'status-approved' : 'status-rejected' }}">{{ $branch->is_open ? 'Dibuka' : 'Ditutup' }}</span>
                                Urutan {{ $branch->sort_order }} · {{ number_format($branchCounts->sum()) }} peserta
                            </p>
                        </div>
                        <div class="heading-actions">
                            <a class="button button-outline button-compact" href="{{ route('admin.branches.edit', $branch) }}">Ubah cabang</a>
                            <a class="button button-primary button-compact" href="{{ route('admin.branches.categories.create', $branch) }}">+ Kategori</a>
                            @if ($branchCounts->sum() === 0)
                                <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" onsubmit="return confirm({{ Js::from("Hapus cabang {$branch->name} beserta semua kategorinya?") }})">
                                    @csrf @method('DELETE')
                                    <button class="button button-danger button-compact" type="submit">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="participant-table">
                            <thead><tr><th>Golongan / kategori</th><th>Biaya</th><th>Batas usia</th><th>Status</th><th>Peserta</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($branch->categories as $category)
                                    @php($count = (int) ($branchCounts[$category->code] ?? 0))
                                    <tr>
                                        <td><strong>{{ $category->name }}</strong><small>{{ $category->description ?: '—' }}</small></td>
                                        <td><strong class="amount">Rp {{ number_format($category->fee, 0, ',', '.') }}</strong></td>
                                        <td>{{ $category->max_age ? 'Maks. '.$category->max_age.' tahun' : '—' }}</td>
                                        <td><span class="status-pill {{ $category->is_open ? 'status-approved' : 'status-rejected' }}">{{ $category->is_open ? 'Dibuka' : 'Ditutup' }}</span></td>
                                        <td>{{ number_format($count) }}</td>
                                        <td><div class="row-actions">
                                            <a class="button button-small" href="{{ route('admin.branches.categories.edit', $category) }}">Ubah</a>
                                            @if ($count === 0)
                                                <form action="{{ route('admin.branches.categories.destroy', $category) }}" method="POST" onsubmit="return confirm({{ Js::from("Hapus kategori {$category->name}?") }})">
                                                    @csrf @method('DELETE')
                                                    <button class="button button-small button-danger" type="submit">Hapus</button>
                                                </form>
                                            @endif
                                        </div></td>
                                    </tr>
                                @empty
                                    <tr><td class="empty-state" colspan="6">Belum ada kategori. Cabang ini tidak tampil di formulir sampai memiliki minimal satu kategori yang dibuka.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <div class="panel empty-state">Belum ada cabang lomba.</div>
            @endforelse
        </div>
    </section>
@endsection
