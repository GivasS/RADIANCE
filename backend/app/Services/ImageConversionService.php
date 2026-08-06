<?php

namespace App\Services;

use RuntimeException;

/**
 * Converte imagens (jpg/png/webp) para WebP, corrigindo a rotacao EXIF de
 * fotos de celular (GD nao aplica isso sozinho ao decodificar o JPEG).
 */
class ImageConversionService
{
    private const QUALITY = 82;

    public function toWebp(string $sourcePath, ?string $mime = null): string
    {
        $mime ??= mime_content_type($sourcePath) ?: '';

        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => throw new RuntimeException("Formato de imagem nao suportado: {$mime}"),
        };

        if ($image === false) {
            throw new RuntimeException('Nao foi possivel ler a imagem enviada (arquivo corrompido?).');
        }

        if (in_array($mime, ['image/jpeg', 'image/jpg'], true)) {
            $image = $this->fixOrientation($image, $sourcePath);
        }

        // Preserva transparencia (PNG/WebP com alpha).
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::QUALITY);
        $data = ob_get_clean();
        imagedestroy($image);

        if ($data === false || $data === '') {
            throw new RuntimeException('Falha ao converter a imagem para WebP.');
        }

        return $data;
    }

    private function fixOrientation(\GdImage $image, string $sourcePath): \GdImage
    {
        $exif = @exif_read_data($sourcePath);
        $orientation = $exif['Orientation'] ?? 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
