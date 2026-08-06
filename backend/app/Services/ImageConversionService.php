<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Converte imagens (jpg/png/webp/heic/heif) para WebP, corrigindo a rotacao
 * EXIF de fotos de celular (GD nao aplica isso sozinho ao decodificar o
 * JPEG). HEIC/HEIF (padrao de foto do iPhone) passa antes pelo heif-convert
 * (libheif) pra virar PNG, ja que o GD nao le esse formato.
 */
class ImageConversionService
{
    private const QUALITY = 82;

    public function toWebp(string $sourcePath, ?string $mime = null): string
    {
        $mime ??= mime_content_type($sourcePath) ?: '';
        $applyExifOrientation = in_array($mime, ['image/jpeg', 'image/jpg'], true);
        $tmpDecoded = null;

        if (in_array($mime, ['image/heic', 'image/heif'], true)) {
            $tmpDecoded = $this->decodeHeif($sourcePath);
            $sourcePath = $tmpDecoded;
            $mime = 'image/png';
        }

        try {
            $image = match ($mime) {
                'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
                'image/png' => imagecreatefrompng($sourcePath),
                'image/webp' => imagecreatefromwebp($sourcePath),
                default => throw new RuntimeException("Formato de imagem nao suportado: {$mime}"),
            };

            if ($image === false) {
                throw new RuntimeException('Nao foi possivel ler a imagem enviada (arquivo corrompido?).');
            }

            if ($applyExifOrientation) {
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
        } finally {
            if ($tmpDecoded !== null) {
                @unlink($tmpDecoded);
            }
        }

        if ($data === false || $data === '') {
            throw new RuntimeException('Falha ao converter a imagem para WebP.');
        }

        return $data;
    }

    private function decodeHeif(string $sourcePath): string
    {
        $tmpPath = sys_get_temp_dir().'/heic_'.bin2hex(random_bytes(10)).'.png';

        $process = new Process(['/usr/bin/heif-convert', $sourcePath, $tmpPath]);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($tmpPath)) {
            throw new RuntimeException('Nao foi possivel converter a imagem HEIC/HEIF enviada.');
        }

        return $tmpPath;
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
