<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Secure image upload with server-side validation and optimized variants (§37).
 * Never trusts the client filename; generates safe unique names and re-encodes
 * to WebP where the GD library supports it.
 */
class ImageUploadService
{
    private array $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    private array $allowedExt  = ['jpg', 'jpeg', 'png', 'webp'];
    private int $maxBytes      = 6_000_000; // 6 MB

    private array $sizes = [
        'large'     => 1200,
        'medium'    => 700,
        'thumbnail' => 300,
    ];

    /**
     * Validate and store an uploaded product image, returning the public path
     * of the primary (large) image. Throws on any validation failure.
     *
     * @return string relative public path (e.g. uploads/products/xxxx-large.webp)
     */
    public function storeProductImage(UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw new \RuntimeException('Upload failed: ' . $file->getErrorString());
        }
        if ($file->getSize() > $this->maxBytes) {
            throw new \RuntimeException('Image is too large (max 6 MB).');
        }
        if (! in_array($file->getMimeType(), $this->allowedMime, true)) {
            throw new \RuntimeException('Only JPG, PNG or WebP images are allowed.');
        }
        if (! in_array(strtolower($file->getExtension()), $this->allowedExt, true)) {
            throw new \RuntimeException('Unsupported file extension.');
        }
        $info = @getimagesize($file->getTempName());
        if ($info === false) {
            throw new \RuntimeException('That file is not a valid image.');
        }

        $dir = FCPATH . 'uploads/products';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $base = bin2hex(random_bytes(8)) . '-' . time(); // safe unique name
        $src  = $this->createImageResource($file->getTempName(), $info['mime']);
        if (! $src) {
            // Fallback: store the original safely without re-encoding.
            $ext  = strtolower($file->getExtension());
            $name = $base . '.' . $ext;
            $file->move($dir, $name);

            return 'uploads/products/' . $name;
        }

        $primary = '';
        foreach ($this->sizes as $label => $maxW) {
            $resized = $this->resize($src, $maxW);
            $path    = $dir . '/' . $base . '-' . $label . '.webp';
            if (function_exists('imagewebp')) {
                imagewebp($resized, $path, 82);
            } else {
                $path = $dir . '/' . $base . '-' . $label . '.jpg';
                imagejpeg($resized, $path, 85);
            }
            imagedestroy($resized);
            if ($label === 'large') {
                $primary = 'uploads/products/' . basename($path);
            }
        }
        imagedestroy($src);

        return $primary;
    }

    private function createImageResource(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default      => false,
        };
    }

    private function resize($src, int $maxW)
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= $maxW) {
            return $this->cloneResource($src, $w, $h);
        }
        $nw  = $maxW;
        $nh  = (int) round($h * ($maxW / $w));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private function cloneResource($src, int $w, int $h)
    {
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

        return $dst;
    }
}
