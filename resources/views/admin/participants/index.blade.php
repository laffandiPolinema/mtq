@extends('layouts.app')

@section('title', 'Kelola Peserta · MTQ Kota Malang 2026')

@section('content')
    <section class="admin-section">
        @include('admin.partials.nav')
        <div class="admin-heading">
            <div><span class="section-kicker">Panel panitia</span><h1 class="display-title">Data peserta</h1><p>Kelola pendaftaran, cek bukti pembayaran, dan verifikasi calon peserta MTQ 2026.</p></div>
            <div class="heading-actions">
                <a class="button button-primary" href="{{ route('admin.participants.export', request()->only(['search', 'branch', 'payment_status', 'status'])) }}">⬇ Download laporan (Excel)</a>
                <form action="{{ route('admin.logout') }}" method="POST">@csrf<button class="button button-outline" type="submit">Keluar</button></form>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><span>Total pendaftar</span><strong>{{ number_format($counts['total']) }}</strong></div>
            <a class="stat-card stat-card-alert" href="{{ route('admin.participants.index', ['payment_status' => 'waiting']) }}"><span>Bukti perlu dicek</span><strong>{{ number_format($counts['payment_waiting']) }}</strong></a>
            <div class="stat-card"><span>Pembayaran lunas</span><strong>{{ number_format($counts['payment_verified']) }}</strong></div>
            <div class="stat-card"><span>Data terverifikasi</span><strong>{{ number_format($counts['approved']) }}</strong></div>
            <div class="stat-card stat-card-wide"><span>Dana pendaftaran terkonfirmasi</span><strong>Rp {{ number_format($counts['verified_amount'], 0, ',', '.') }}</strong></div>
        </div>

        @if (session('success')) <div class="notice notice-success">{{ session('success') }}</div> @endif

        <form class="admin-filters" action="{{ route('admin.participants.index') }}" method="GET">
            <div class="field search-field"><label for="search">Cari peserta</label><input id="search" name="search" value="{{ request('search') }}" placeholder="Nama, kode pendaftaran, atau nominal transfer (mis. 35.123)"></div>
            <div class="field"><label for="branch-filter">Cabang</label><select id="branch-filter" name="branch"><option value="">Semua cabang</option>@foreach ($branches as $key => $label)<option value="{{ $key }}" @selected(request('branch') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="payment-filter">Pembayaran</label><select id="payment-filter" name="payment_status"><option value="">Semua</option>@foreach ($paymentStatuses as $key => $label)<option value="{{ $key }}" @selected(request('payment_status') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="status-filter">Status data</label><select id="status-filter" name="status"><option value="">Semua status</option>@foreach ($statuses as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
            <button class="button button-primary filter-button" type="submit">Terapkan</button>
        </form>

        @if (array_filter(request()->only(['search', 'branch', 'payment_status', 'status'])) !== [])
            <p class="filter-hint">Laporan yang diunduh mengikuti filter yang sedang aktif. <a class="text-link" href="{{ route('admin.participants.index') }}">Hapus filter</a> untuk mengunduh semua peserta.</p>
        @endif

        <div class="table-wrap">
            <table class="participant-table">
                <thead><tr><th>Peserta</th><th>Cabang</th><th>Kecamatan</th><th>Nominal transfer</th><th>Pembayaran</th><th>Status data</th><th></th></tr></thead>
                <tbody>
                    @forelse ($participants as $participant)
                        <tr>
                            <td><strong>{{ $participant->full_name }}</strong><small>{{ $participant->registration_code }} · {{ $participant->created_at->format('d/m/Y') }}</small></td>
                            <td class="cell-branch">{{ $participant->branchLabel() }}<small>{{ $participant->categoryLabel() ?? '—' }}</small></td>
                            <td>{{ $participant->district }}</td>
                            <td><strong class="amount">{{ substr($participant->formattedPaymentAmount(), 0, -3) }}<em>{{ substr($participant->formattedPaymentAmount(), -3) }}</em></strong></td>
                            <td><span class="status-pill payment-{{ $participant->payment_status }}">{{ $participant->paymentStatusLabel() }}</span></td>
                            <td><span class="status-pill status-{{ $participant->status }}">{{ $statuses[$participant->status] }}</span></td>
                            <td><a class="button button-small" href="{{ route('admin.participants.show', $participant) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="7">Belum ada peserta yang sesuai dengan pencarian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $participants->links('partials.pagination') }}</div>
    </section>
@endsection
