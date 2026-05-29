<?php

namespace App\Helpers;

use App\Support\PublicStorageUrl;

class ImageHelper
{
    public static function getImageUrl($path, $thumbnail = false)
    {
        if (!$path) {
            return null;
        }

        if ($thumbnail) {
            return PublicStorageUrl::from('thumbnails/' . ltrim($path, '/'));
        }

        return PublicStorageUrl::from($path);
    }
}
