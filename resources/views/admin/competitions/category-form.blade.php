@extends('layouts.app')

@section('title', ($category->exists ? 'Ubah' : 'Tambah').' Kategori · Panel Panitia MTQ 2026')

@section('content')
    <section class="admin-section admin-form-section">
        @include('admin.partials.nav')
        <a class="text-link back-link" href="{{ route('admin.branches.index') }}">← Kembali ke cabang &amp; biaya</a>

        @if (session('success')) <div class="notice notice-success">{{ session('success') }}</div> @endif

        <form class="panel admin-form" action="{{ $category->exists ? route('admin.branches.categories.update', $category) : route('admin.branches.categories.store', $branch) }}" method="POST">
            @csrf
            @if ($category->exists) @method('PUT') @endif
            <span class="section-kicker">{{ $branch->name }}</span>
            <h1 class="display-title">{{ $category->exists ? 'Ubah kategori' : 'Tambah kategori' }}</h1>

            @if (($participants ?? 0) > 0)
                <div class="notice notice-info">Kategori ini sudah memiliki {{ $participants }} peserta. Perubahan biaya hanya berlaku untuk pendaftar baru.</div>
            @endif

            <div class="field">
                <label for="name">Golongan / kategori <span>*</span></label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" placeholder="Mis. Tilawah Anak / Umum" required maxlength="120">
                @error('name') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="description">Keterangan peserta <small>Opsional</small></label>
                <input id="description" name="description" value="{{ old('description', $category->description) }}" placeholder="Mis. SD/MI s.d. kelas 8 SMP/MTs" maxlength="255">
                @error('description') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <div class="field-grid">
                <div class="field">
                    <label for="fee">Biaya pendaftaran (Rp) <span>*</span></label>
                    <input id="fee" name="fee" type="number" min="1000" step="1000" value="{{ old('fee', $category->fee) }}" placeholder="35000" required>
                    <small class="field-hint">Kelipatan 1.000. Peserta mentransfer biaya ini + kode unik 3 digit.</small>
                    @error('fee') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="max_age">Batas usia maksimal <small>Opsional, tahun</small></label>
                    <input id="max_age" name="max_age" type="number" min="1" max="120" value="{{ old('max_age', $category->max_age) }}" placeholder="Mis. 41">
                    <small class="field-hint">Dihitung pada hari lomba. Kosongkan jika tidak ada batas.</small>
                    @error('max_age') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="sort_order">Urutan tampil <span>*</span></label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', $category->sort_order) }}" required>
                    @error('sort_order') <small class="field-error">{{ $message }}</small> @enderror
                </div>
            </div>
            <label class="toggle-field">
                <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $category->is_open))>
                <span><strong>Pendaftaran dibuka</strong><small>Jika tidak dicentang, kategori ini tidak dapat dipilih peserta.</small></span>
            </label>

            <button class="button button-primary" type="submit">Simpan kategori</button>
        </form>
    </section>
@endsection
