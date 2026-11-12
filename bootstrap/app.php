<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // File melebihi batas server. Session belum berjalan di tahap ini, jadi popup ditampilkan langsung.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $errors = (new ViewErrorBag)->put('default', new MessageBag([
                'upload' => 'Ukuran file yang diunggah terlalu besar. Pasfoto maksimal 2 MB, identitas diri dan bukti transfer maksimal 5 MB.',
            ]));

            return response()->view('errors.upload-too-large', ['errors' => $errors], 413);
        });
    })->create();
