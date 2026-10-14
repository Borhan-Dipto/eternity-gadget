<?php

namespace App\Http\Controllers;

use App\Support\MediaUploadLogger;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    public function serve(string $path): Response|StreamedResponse
    {
        $path = ltrim(str_replace(['..', '\\'], ['', '/'], $path), '/');

        if ($path === '') {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            MediaUploadLogger::warning('storage.serve.missing', [
                'path' => $path,
                'message' => 'Browser requested /storage/ file but it does not exist on public disk',
            ]);

            abort(404);
        }

        return $disk->response($path);
    }
}
