@php
    $juknisDocument = app(\App\Services\JuknisDocument::class);
    $juknisPdf = $juknisDocument->url();
    $juknisDownload = route('juknis.show', ['v' => $juknisDocument->updatedAt()?->timestamp, 'unduh' => 1]);
@endphp

<section class="content-section juknis-section" id="juknis">
    <div class="juknis-head">
        <div class="juknis-intro">
            <span class="section-kicker">Petunjuk teknis</span>
            <h2 class="display-title">Juknis<br>perlombaan</h2>
            <p>Baca ketentuan peserta, aturan lomba, dan kriteria penilaian tiap cabang di dokumen juknis sebelum mendaftar.</p>
        </div>

        <div class="juknis-doc">
            <span class="juknis-doc-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zm8 1.5V8h4.5zM8 12h8v1.6H8zm0 3.2h8v1.6H8zm0 3.2h5v1.6H8z" fill="currentColor"/></svg>
            </span>
            <div class="juknis-doc-text">
                <strong>Juknis Perlombaan MTQ Kota Malang 2026</strong>
                @if ($juknisDocument->exists())
                    <small>PDF · {{ $juknisDocument->sizeLabel() }} · diperbarui {{ $juknisDocument->updatedAt()?->translatedFormat('j F Y') }}</small>
                @else
                    <small>Dokumen juknis belum tersedia.</small>
                @endif
            </div>
            @if ($juknisDocument->exists())
                <div class="juknis-doc-actions">
                    <a class="button button-primary button-compact" href="{{ $juknisPdf }}" target="_blank" rel="noopener">
                        <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-5 0-9 4.5-10 7 1 2.5 5 7 10 7s9-4.5 10-7c-1-2.5-5-7-10-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" fill="currentColor"/></svg>
                        Buka di tab baru
                    </a>
                    <a class="button button-outline button-compact" href="{{ $juknisDownload }}">
                        <svg class="button-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 3h2v9.2l3.3-3.3 1.4 1.4L12 16 6.3 10.3l1.4-1.4 3.3 3.3zM5 18h14v2H5z" fill="currentColor"/></svg>
                        Unduh PDF
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if ($juknisDocument->exists())
        <div class="juknis-preview">
            <iframe src="{{ $juknisPdf }}#view=FitH" title="Pratinjau juknis perlombaan" loading="lazy"></iframe>
            <p class="juknis-preview-fallback">Pratinjau PDF tidak tersedia di perangkat ini. Gunakan tombol <strong>Buka di tab baru</strong> atau <strong>Unduh PDF</strong>.</p>
        </div>
    @endif
</section>
