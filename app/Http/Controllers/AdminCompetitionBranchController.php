<?php

namespace App\Http\Controllers;

use App\Models\CompetitionBranch;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCompetitionBranchController extends Controller
{
    public function index(): View
    {
        $participantCounts = Participant::query()
            ->selectRaw('branch, category, count(*) as total')
            ->groupBy('branch', 'category')
            ->get()
            ->groupBy('branch')
            ->map(fn ($rows) => $rows->pluck('total', 'category'));

        return view('admin.competitions.index', [
            'branches' => CompetitionBranch::with('categories')->orderBy('sort_order')->orderBy('id')->get(),
            'participantCounts' => $participantCounts,
        ]);
    }

    public function create(): View
    {
        return view('admin.competitions.branch-form', [
            'branch' => new CompetitionBranch(['is_open' => true, 'sort_order' => CompetitionBranch::max('sort_order') + 1]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = CompetitionBranch::create($this->validated($request));

        return redirect()
            ->route('admin.branches.categories.create', $branch)
            ->with('success', "Cabang {$branch->name} ditambahkan. Lanjutkan dengan menambah kategori dan biayanya.");
    }

    public function edit(CompetitionBranch $branch): View
    {
        return view('admin.competitions.branch-form', ['branch' => $branch]);
    }

    public function update(Request $request, CompetitionBranch $branch): RedirectResponse
    {
        $branch->update($this->validated($request));

        return redirect()->route('admin.branches.index')->with('success', "Cabang {$branch->name} diperbarui.");
    }

    public function destroy(CompetitionBranch $branch): RedirectResponse
    {
        $participants = $branch->participantsCount();

        if ($participants > 0) {
            return back()->withErrors([
                'branch' => "Cabang {$branch->name} tidak dapat dihapus karena sudah memiliki {$participants} peserta. Tutup pendaftarannya saja.",
            ]);
        }

        $branch->delete();

        return redirect()->route('admin.branches.index')->with('success', "Cabang {$branch->name} dihapus.");
    }

    /**
     * @return array{name: string, is_open: bool, sort_order: int}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ], [
            'name.required' => 'Nama cabang wajib diisi.',
            'sort_order.required' => 'Urutan tampil wajib diisi.',
        ]);

        return [...$validated, 'is_open' => $request->boolean('is_open')];
    }
}
