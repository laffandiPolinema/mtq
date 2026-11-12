<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a4a2a">
    <title>@yield('title', 'MTQ Kota Malang 2026')</title>
    <meta name="description" content="Pendaftaran Musabaqah Tilawatil Qur’an Tingkat Kota Malang Tahun 2026.">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('partials.ornament-sprite')
    @include('partials.ornament-corner')
    @if (request()->routeIs('home'))
        @include('partials.ornament-corner', ['class' => 'ornament-corner-left'])
    @endif

    <header class="site-header">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40"><use href="#mandala" x="2" y="2" width="36" height="36"/></svg>
            </span>
            <span><strong>MTQ 2026</strong><small>Kota Malang</small></span>
        </a>
        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="{{ route('home') }}#cabang">Cabang lomba</a>
            <a href="{{ route('home') }}#biaya">Biaya</a>
            <a href="{{ route('status.form') }}">Cek pendaftaran</a>
            @auth
                <a class="nav-pill" href="{{ route('admin.participants.index') }}">Panel panitia</a>
            @else
                <a class="nav-pill" href="{{ route('login') }}">Login panitia</a>
            @endauth
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <dialog class="alert-dialog" id="alert-dialog" aria-labelledby="alert-dialog-title" @if ($errors->any()) data-open-on-load @endif>
        <div class="alert-dialog-icon" aria-hidden="true">!</div>
        <h2 id="alert-dialog-title">Periksa kembali isian Anda</h2>
        <ul data-alert-messages>
            @foreach (array_unique($errors->all()) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        <form method="dialog"><button class="button button-primary" type="submit" autofocus>Mengerti</button></form>
    </dialog>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <h2 class="display-title">Terima kasih!</h2>
                <p><strong>MTQ Tingkat Kota Malang 2026</strong><br>Diselenggarakan PHBI &amp; Ketakmiran Masjid Jami’ Blimbing</p>
                <p class="footer-meta">26–27 Desember 2026 · Mulai 07.30 WIB · Malang Creative Center (MCC) Lt. 2</p>
            </div>
            <div class="footer-contacts">
                <span class="section-kicker">Informasi kontak</span>
                <ul>
                    @foreach (config('mtq.contacts') as $contact)
                        <li>
                            <span class="contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1z" fill="currentColor"/></svg></span>
                            <span><strong>{{ $contact['name'] }}</strong><a href="https://wa.me/62{{ ltrim($contact['phone'], '0') }}" target="_blank" rel="noopener">{{ implode('.', str_split($contact['phone'], 3)) }}</a></span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="footer-address">
                <span class="section-kicker">Sekretariat</span>
                <p><span class="contact-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z" fill="currentColor"/></svg></span>{{ config('mtq.address') }}</p>
            </div>
            <p class="footer-credit">
                <img class="credit-logo" src="{{ asset('images/logo-jti-polinema.png') }}" alt="Logo Jurusan Teknologi Informasi POLINEMA" width="500" height="526">
                <span>Didukung oleh <strong>Jurusan Teknologi Informasi</strong><br>Politeknik Negeri Malang (POLINEMA)</span>
            </p>
        </div>
        <div class="frame-base" aria-hidden="true">
            <div class="base-lanterns">
                <svg class="lantern-lg" viewBox="8 22 24 68"><use href="#lantern-body"/></svg>
                <svg class="lantern-sm" viewBox="8 22 24 68"><use href="#lantern-body"/></svg>
            </div>
        </div>
    </footer>
</body>
</html>
