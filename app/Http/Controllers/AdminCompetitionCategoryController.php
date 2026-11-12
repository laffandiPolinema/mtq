<?php

namespace App\Http\Controllers;

use App\Models\CompetitionBranch;
use App\Models\CompetitionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCompetitionCategoryController extends Controller
{
    public function create(CompetitionBranch $branch): View
    {
        return view('admin.competitions.category-form', [
            'branch' => $branch,
            'category' => new CompetitionCategory([
                'is_open' => true,
                'sort_order' => $branch->categories()->max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(Request $request, CompetitionBranch $branch): RedirectResponse
    {
        $category = $branch->categories()->create($this->validated($request));

        return redirect()->route('admin.branches.index')->with('success', "Kategori {$category->name} ditambahkan ke {$branch->name}.");
    }

    public function edit(CompetitionCategory $category): View
    {
        return view('admin.competitions.category-form', [
            'branch' => $category->branch,
            'category' => $category,
            'participants' => $category->participantsCount(),
        ]);
    }

    public function update(Request $request, CompetitionCategory $category): RedirectResponse
    {
        $category->update($this->validated($request));

        return redirect()->route('admin.branches.index')->with('success', "Kategori {$category->name} diperbarui.");
    }

    public function destroy(CompetitionCategory $category): RedirectResponse
    {
        $participants = $category->participantsCount();

        if ($participants > 0) {
            return back()->withErrors([
                'category' => "Kategori {$category->name} tidak dapat dihapus karena sudah memiliki {$participants} peserta. Tutup pendaftarannya saja.",
            ]);
        }

        $category->delete();

        return redirect()->route('admin.branches.index')->with('success', "Kategori {$category->name} dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            // Kelipatan 1.000 agar tiga digit terakhir nominal transfer tetap sama dengan kode unik.
            'fee' => ['required', 'integer', 'min:1000', 'max:10000000', 'multiple_of:1000'],
            'max_age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ], [
            'name.required' => 'Nama kategori/golongan wajib diisi.',
            'fee.required' => 'Biaya pendaftaran wajib diisi.',
            'fee.integer' => 'Biaya pendaftaran ditulis angka saja, tanpa titik (mis. 35000).',
            'fee.min' => 'Biaya pendaftaran minimal Rp1.000.',
            'fee.multiple_of' => 'Biaya pendaftaran harus kelipatan Rp1.000 (mis. 35000), karena 3 digit terakhir nominal transfer dipakai untuk kode unik peserta.',
            'max_age.integer' => 'Batas usia ditulis angka tahun, mis. 41.',
            'sort_order.required' => 'Urutan tampil wajib diisi.',
        ]);

        return [...$validated, 'is_open' => $request->boolean('is_open')];
    }
}
