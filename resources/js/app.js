document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');

    if (!button) {
        return;
    }

    const originalLabel = button.textContent;

    try {
        await navigator.clipboard.writeText(button.dataset.copy);
        button.textContent = 'Tersalin ✓';
    } catch {
        button.textContent = button.dataset.copy;
    }

    setTimeout(() => {
        button.textContent = originalLabel;
    }, 1800);
});

const alertDialog = document.getElementById('alert-dialog');

/**
 * Tampilkan pesan kesalahan dalam popup.
 */
function showAlert(messages) {
    if (!alertDialog) {
        window.alert(messages.join('\n'));
        return;
    }

    const list = alertDialog.querySelector('[data-alert-messages]');
    list.replaceChildren(...messages.map((message) => {
        const item = document.createElement('li');
        item.textContent = message;
        return item;
    }));
    alertDialog.showModal();
}

if (alertDialog?.hasAttribute('data-open-on-load')) {
    alertDialog.showModal();
}

/**
 * Periksa jenis dan ukuran file langsung saat dipilih, sebelum diunggah.
 */
function fileProblem(input, file) {
    const accepted = (input.accept || '').split(',').map((type) => type.trim()).filter(Boolean);
    const label = input.dataset.label || 'File';
    const maxMb = Number(input.dataset.maxMb || 0);

    if (accepted.length > 0 && !accepted.includes(file.type)) {
        const allowed = accepted.length === 1 && accepted[0] === 'application/pdf'
            ? 'file PDF'
            : (accepted.includes('application/pdf') ? 'JPG, PNG, WEBP, atau PDF' : 'JPG, PNG, atau WEBP');
        return `${label}: jenis file "${file.name}" tidak didukung. Gunakan ${allowed}.`;
    }

    if (maxMb > 0 && file.size > maxMb * 1024 * 1024) {
        const sizeMb = (file.size / 1024 / 1024).toLocaleString('id-ID', { maximumFractionDigits: 1 });
        return `${label}: ukuran file ${sizeMb} MB melebihi batas ${maxMb} MB.`;
    }

    return null;
}

document.addEventListener('change', (event) => {
    const input = event.target.closest('input[type="file"]');
    const label = input?.closest('label')?.querySelector('[data-file-label]');

    if (!input || input.files.length === 0) {
        return;
    }

    if (label) {
        label.dataset.defaultText ??= label.textContent;
    }

    const problem = fileProblem(input, input.files[0]);

    if (problem) {
        input.value = '';
        showAlert([problem]);
    }

    if (label) {
        label.textContent = problem ? label.dataset.defaultText : input.files[0].name;
    }
});

/**
 * Tab juknis per cabang. Tanpa JavaScript semua bagian tetap tampil berurutan.
 */
const juknisTabs = [...document.querySelectorAll('[data-juknis-tab]')];

function openJuknisTab(tab, focus = false) {
    juknisTabs.forEach((other) => {
        const selected = other === tab;
        other.setAttribute('aria-selected', String(selected));
        other.tabIndex = selected ? 0 : -1;
        document.getElementById(other.getAttribute('aria-controls')).hidden = !selected;
    });

    if (focus) {
        tab.focus();
    }
}

if (juknisTabs.length > 0) {
    // Tautan langsung ke cabang tertentu, mis. /#juknis-mtq.
    const linkedTab = juknisTabs.find((tab) => `#${tab.getAttribute('aria-controls')}` === window.location.hash);
    openJuknisTab(linkedTab ?? juknisTabs[0]);

    juknisTabs.forEach((tab, index) => {
        tab.addEventListener('click', () => openJuknisTab(tab));
        tab.addEventListener('keydown', (event) => {
            const step = { ArrowRight: 1, ArrowLeft: -1 }[event.key];

            if (step) {
                event.preventDefault();
                openJuknisTab(juknisTabs[(index + step + juknisTabs.length) % juknisTabs.length], true);
            }
        });
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-juknis-open]');

        if (link) {
            openJuknisTab(document.getElementById(`tab-${link.dataset.juknisOpen}`), true);
        }
    });
}
