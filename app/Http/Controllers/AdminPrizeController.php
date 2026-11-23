<?php

namespace App\Http\Controllers;

use App\Services\PrizeList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminPrizeController extends Controller
{
    public function edit(PrizeList $prizes): View
    {
        return view('admin.prizes.edit', ['prizes' => $prizes]);
    }

    /**
     * Judul, pengantar, dan daftar hadiah di halaman utama.
     */
    public function update(Request $request, PrizeList $prizes): RedirectResponse
    {
        $validated = $request->validate([
            'heading' => ['required', 'string', 'max:80'],
            'intro' => ['nullable', 'string', 'max:200'],
            'items' => ['nullable', 'array', 'max:'.PrizeList::MAX_ITEMS],
            'items.*.icon' => ['nullable', Rule::in(array_keys(PrizeList::ICONS))],
            'items.*.title' => ['nullable', 'string', 'max:60'],
            'items.*.description' => ['nullable', 'string', 'max:120'],
        ], [
            'heading.required' => 'Judul bagian hadiah wajib diisi.',
            'items.*.icon.in' => 'Ikon hadiah tidak valid.',
            'max' => 'Isian terlalu panjang.',
        ]);

        $prizes->update($validated);

        return redirect()
            ->route('admin.prizes.edit')
            ->with('success', 'Bagian hadiah di halaman utama berhasil diperbarui.');
    }
}
