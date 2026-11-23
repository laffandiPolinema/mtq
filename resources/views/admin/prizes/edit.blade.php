@extends('layouts.app')

@section('title', 'Hadiah · Panel Panitia MTQ 2026')

@section('content')
    @php($items = $prizes->items())
    <section class="admin-section admin-form-section juknis-admin">
        @include('admin.partials.nav')

        @if (session('success')) <div class="notice notice-success">{{ session('success') }}</div> @endif

        <form class="panel admin-form" action="{{ route('admin.prizes.update') }}" method="POST">
            @csrf
            @method('PUT')
            <span class="section-kicker">Halaman utama</span>
            <h1 class="display-title">Bagian hadiah</h1>
            <p class="field-hint">Tampil sebagai bagian <strong>Dapatkan hadiah</strong> di halaman utama, tepat setelah tema acara. Baris yang nama hadiahnya dikosongkan tidak ditampilkan; jika semua dikosongkan, bagian ini disembunyikan.</p>

            <div class="field">
                <label for="heading">Judul <span>*</span></label>
                <input id="heading" name="heading" value="{{ old('heading', $prizes->heading()) }}" maxlength="80" required>
            </div>
            <div class="field">
                <label for="intro">Kalimat pengantar</label>
                <input id="intro" name="intro" value="{{ old('intro', $prizes->intro()) }}" maxlength="200">
            </div>

            <fieldset class="schedule-fields">
                <legend>Daftar hadiah (urutan sesuai tampilan)</legend>
                @for ($index = 0; $index < \App\Services\PrizeList::MAX_ITEMS; $index++)
                    @php($item = $items[$index] ?? ['icon' => 'trophy', 'title' => '', 'description' => ''])
                    <div class="prize-row">
                        <span class="prize-row-number">{{ $index + 1 }}</span>
                        <div class="field">
                            <label for="items-{{ $index }}-icon">Ikon</label>
                            <select id="items-{{ $index }}-icon" name="items[{{ $index }}][icon]">
                                @foreach (\App\Services\PrizeList::ICONS as $key => $icon)
                                    <option value="{{ $key }}" @selected(old("items.$index.icon", $item['icon']) === $key)>{{ $icon['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="items-{{ $index }}-title">Nama hadiah</label>
                            <input id="items-{{ $index }}-title" name="items[{{ $index }}][title]" value="{{ old("items.$index.title", $item['title']) }}" maxlength="60" placeholder="Mis. Tropi">
                        </div>
                        <div class="field">
                            <label for="items-{{ $index }}-description">Keterangan</label>
                            <input id="items-{{ $index }}-description" name="items[{{ $index }}][description]" value="{{ old("items.$index.description", $item['description']) }}" maxlength="120" placeholder="Mis. Untuk juara 1, 2, dan 3">
                        </div>
                    </div>
                @endfor
            </fieldset>

            <div>
                <button class="button button-primary" type="submit">Simpan hadiah</button>
            </div>
        </form>
    </section>
@endsection
