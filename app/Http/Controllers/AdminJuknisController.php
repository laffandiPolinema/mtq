<?php

namespace App\Http\Controllers;

use App\Services\JuknisDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminJuknisController extends Controller
{
    public function edit(JuknisDocument $juknis): View
    {
        return view('admin.juknis.edit', ['juknis' => $juknis]);
    }

    public function update(Request $request, JuknisDocument $juknis): RedirectResponse
    {
        $request->validate(['juknis' => JuknisDocument::rules()], JuknisDocument::messages());

        $juknis->replace($request->file('juknis'));

        return redirect()
            ->route('admin.juknis.edit')
            ->with('success', 'Juknis berhasil diganti. Halaman utama sekarang memakai file yang baru.');
    }

    public function destroy(JuknisDocument $juknis): RedirectResponse
    {
        $juknis->deleteUpload();

        return redirect()
            ->route('admin.juknis.edit')
            ->with('success', 'File unggahan dihapus. Halaman utama kembali memakai juknis bawaan.');
    }
}
