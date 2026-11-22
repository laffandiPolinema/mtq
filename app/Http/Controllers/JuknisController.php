<?php

namespace App\Http\Controllers;

use App\Services\JuknisDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JuknisController extends Controller
{
    /**
     * Juknis terbaru untuk dibaca di browser, atau diunduh dengan ?unduh=1.
     */
    public function show(Request $request, JuknisDocument $juknis): BinaryFileResponse
    {
        return $juknis->response(download: $request->boolean('unduh'));
    }
}
