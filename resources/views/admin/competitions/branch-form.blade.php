@extends('layouts.app')

@section('title', ($branch->exists ? 'Ubah' : 'Tambah').' Cabang · Panel Panitia MTQ 2026')

@section('content')
    <section class="admin-section admin-form-section">
        @include('admin.partials.nav')
        <a class="text-link back-link" href="{{ route('admin.branches.index') }}">← Kembali ke cabang &amp; biaya</a>

        <form class="panel admin-form" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" method="POST">
            @csrf
            @if ($branch->exists) @method('PUT') @endif
            <span class="section-kicker">{{ $branch->exists ? 'Ubah cabang' : 'Cabang baru' }}</span>
            <h1 class="display-title">{{ $branch->exists ? $branch->name : 'Tambah cabang' }}</h1>

            <div class="field">
                <label for="name">Nama cabang <span>*</span></label>
                <input id="name" name="name" value="{{ old('name', $branch->name) }}" placeholder="Mis. MKQ (Khattil Qur’an)" required maxlength="120">
                @error('name') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <div class="field">
                <label for="sort_order">Urutan tampil <span>*</span></label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', $branch->sort_order) }}" required>
                @error('sort_order') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <label class="toggle-field">
                <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $branch->is_open))>
                <span><strong>Pendaftaran dibuka</strong><small>Jika tidak dicentang, cabang tampil sebagai “ditutup” dan tidak dapat dipilih peserta.</small></span>
            </label>
            @if ($branch->exists)
                <p class="field-hint">Kode sistem: <code>{{ $branch->code }}</code> (tetap, tidak berubah walau nama diganti).</p>
            @endif

            <button class="button button-primary" type="submit">{{ $branch->exists ? 'Simpan perubahan' : 'Simpan & tambah kategori' }}</button>
        </form>
    </section>
@endsection
