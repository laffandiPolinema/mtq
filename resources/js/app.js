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
