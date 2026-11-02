{{-- Ornamen bersama (gradien, pola kisi, mandala, lentera) yang dipakai ulang lewat <use>. --}}
<svg class="svg-sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="g-green" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#0d5c34"/>
            <stop offset=".55" stop-color="#0a4a2a"/>
            <stop offset="1" stop-color="#06331d"/>
        </linearGradient>
        <linearGradient id="g-gold" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#a8781c"/>
            <stop offset=".35" stop-color="#f6dc85"/>
            <stop offset=".6" stop-color="#d4a63a"/>
            <stop offset="1" stop-color="#fbe7a1"/>
        </linearGradient>
        <linearGradient id="g-gold-v" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fbe7a1"/>
            <stop offset=".5" stop-color="#d4a63a"/>
            <stop offset="1" stop-color="#9a6b14"/>
        </linearGradient>
        <radialGradient id="g-glow" cx=".5" cy=".55" r=".6">
            <stop offset="0" stop-color="#fffbe6"/>
            <stop offset=".5" stop-color="#ffe08a"/>
            <stop offset="1" stop-color="#e9a83a"/>
        </radialGradient>
        <linearGradient id="g-petal" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#16804a"/>
            <stop offset="1" stop-color="#073d22"/>
        </linearGradient>

        <pattern id="p-lattice" width="26" height="26" patternUnits="userSpaceOnUse">
            <rect width="26" height="26" fill="#0b4d3e"/>
            <path d="M13 2 L24 13 L13 24 L2 13 Z" fill="none" stroke="#3d9a7c" stroke-width="1.4"/>
            <rect x="5.5" y="5.5" width="15" height="15" fill="none" stroke="#3d9a7c" stroke-width="1.4"/>
            <circle cx="13" cy="13" r="2.4" fill="#5fb898"/>
        </pattern>
        <pattern id="p-lattice-faint" width="34" height="34" patternUnits="userSpaceOnUse">
            <path d="M17 3 L31 17 L17 31 L3 17 Z" fill="none" stroke="#ffffff" stroke-opacity=".09" stroke-width="1"/>
            <rect x="7" y="7" width="20" height="20" fill="none" stroke="#ffffff" stroke-opacity=".09" stroke-width="1"/>
        </pattern>

        <symbol id="mandala" viewBox="-50 -50 100 100">
            @foreach (range(0, 15) as $i)
                <path transform="rotate({{ $i * 22.5 }})" d="M0-48 C9-36 10-20 0-9 C-10-20-9-36 0-48Z" fill="url(#g-petal)" stroke="#e2bb57" stroke-width="1.3"/>
            @endforeach
            @foreach (range(0, 7) as $i)
                <path transform="rotate({{ $i * 45 + 22.5 }})" d="M0-33 C8-24 8-13 0-5 C-8-13-8-24 0-33Z" fill="#0e6b3a" stroke="#f1d27a" stroke-width="1.2"/>
            @endforeach
            <circle r="11" fill="#0a4a2a" stroke="#f1d27a" stroke-width="1.6"/>
            <circle r="5" fill="url(#g-gold-v)"/>
        </symbol>

        <g id="lantern-body">
            <path d="M14 34 Q20 24 26 34 Z" fill="url(#g-gold-v)"/>
            <rect x="11" y="34" width="18" height="4" rx="1" fill="url(#g-gold)"/>
            <path d="M11 38 H29 L27.5 70 H12.5 Z" fill="url(#g-glow)" stroke="#b9871f" stroke-width="1.4"/>
            <path d="M16.5 38 L16 70 M20 38 V70 M23.5 38 L24 70" stroke="#b9871f" stroke-width=".9" opacity=".75"/>
            <path d="M11 47 H29 M11.6 60 H28.4" stroke="#b9871f" stroke-width="1"/>
            <rect x="11" y="70" width="18" height="4" rx="1" fill="url(#g-gold)"/>
            <path d="M14 74 H26 L20 84 Z" fill="url(#g-gold-v)"/>
            <circle cx="20" cy="86" r="1.8" fill="#d4a63a"/>
        </g>
        <symbol id="lantern" viewBox="0 0 40 92">
            <path d="M20 0 V25" stroke="#c9972c" stroke-width="1.2"/>
            <circle cx="20" cy="26" r="2.2" fill="none" stroke="#c9972c" stroke-width="1.2"/>
            <use href="#lantern-body"/>
        </symbol>
    </defs>
</svg>
