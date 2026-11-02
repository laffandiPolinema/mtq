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

document.addEventListener('change', (event) => {
    const input = event.target.closest('input[type="file"]');
    const label = input?.closest('label')?.querySelector('[data-file-label]');

    if (label && input.files.length > 0) {
        label.textContent = input.files[0].name;
    }
});
