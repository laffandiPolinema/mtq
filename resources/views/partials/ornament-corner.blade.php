{{-- Bingkai sudut kanan atas: gelombang hijau, pita emas, mandala, dan lentera gantung seperti di proposal. --}}
<div class="ornament-corner {{ $class ?? '' }}" aria-hidden="true">
    <svg viewBox="0 0 420 300" xmlns="http://www.w3.org/2000/svg">
        <path d="M150 0 H420 V252 C396 176 340 122 270 99 C214 81 171 52 150 0 Z" fill="url(#g-green)"/>
        <path d="M150 0 H420 V252 C396 176 340 122 270 99 C214 81 171 52 150 0 Z" fill="url(#p-lattice-faint)"/>
        <path d="M120 0 C148 64 204 88 268 106 C342 128 398 184 420 270" fill="none" stroke="url(#g-gold)" stroke-width="7" stroke-linecap="round"/>
        <path d="M137 0 C162 52 211 74 271 93 C338 114 392 160 420 228" fill="none" stroke="url(#g-gold)" stroke-width="1.6" opacity=".8"/>

        <use href="#lantern" x="286" y="112" width="34" height="78" class="sway sway-1"/>
        <use href="#lantern" x="333" y="128" width="40" height="112" class="sway sway-2"/>
        <use href="#lantern" x="380" y="170" width="30" height="72" class="sway sway-3"/>

        <use href="#mandala" x="300" y="6" width="112" height="112"/>
        <use href="#mandala" x="226" y="46" width="58" height="58"/>
    </svg>
</div>
