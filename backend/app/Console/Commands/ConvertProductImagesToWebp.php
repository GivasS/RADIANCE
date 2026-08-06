<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Services\ImageConversionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Converte pra WebP as imagens de produto ja armazenadas antes dessa
 * conversao existir (upload novo ja salva direto em WebP).
 */
class ConvertProductImagesToWebp extends Command
{
    protected $signature = 'products:convert-images-to-webp {--dry-run : So mostra o que seria feito, sem alterar nada}';

    protected $description = 'Converte imagens de produto existentes (jpg/png) para WebP';

    public function handle(ImageConversionService $converter): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $images = ProductImage::query()->where('path', 'not like', '%.webp')->get();

        if ($images->isEmpty()) {
            $this->info('Nenhuma imagem pra converter - tudo ja esta em WebP.');

            return self::SUCCESS;
        }

        $this->info("{$images->count()} imagem(ns) pra converter.".($dryRun ? ' (dry-run, nada sera alterado)' : ''));

        $converted = 0;
        $failed = 0;

        foreach ($images as $image) {
            $oldPath = $image->path;

            if (! Storage::disk('public')->exists($oldPath)) {
                $this->warn("  [pulado] arquivo nao encontrado: {$oldPath}");
                $failed++;

                continue;
            }

            try {
                $absolutePath = Storage::disk('public')->path($oldPath);
                $webpData = $converter->toWebp($absolutePath);

                $newPath = 'products/'.$image->product_id.'/'.Str::random(40).'.webp';

                if ($dryRun) {
                    $this->line("  {$oldPath} -> {$newPath}");
                } else {
                    Storage::disk('public')->put($newPath, $webpData);
                    $image->update(['path' => $newPath]);
                    Storage::disk('public')->delete($oldPath);
                }

                $converted++;
            } catch (Throwable $e) {
                $this->error("  [falhou] {$oldPath}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Concluido: {$converted} convertida(s), {$failed} falha(s)/pulada(s).");

        return self::SUCCESS;
    }
}
