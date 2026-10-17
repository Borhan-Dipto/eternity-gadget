<?php

namespace App\Http\Controllers;

use App\Support\Thumbnail;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * First request for a small copy of a photo: make it, save it, send it.
 * After that the saved file is linked directly (see Thumbnail::url), so this runs once per photo/size.
 */
class ThumbnailController extends Controller
{
    public function show(int $width, string $path): BinaryFileResponse|RedirectResponse
    {
        $thumb = Thumbnail::make($path, $width);

        if (! $thumb) {
            return redirect()->to(media_url($path));
        }

        return response()->file($thumb, ['Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
