<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\VectorEmbeddingService;
use Illuminate\Support\Str;
use Exception;
use App\Models\DocumentVector;

class VectorizeDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vectorize:docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan feature docs in storage/app/private, vectorize markdown, and store in MongoDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $basePath = storage_path('app/private');
        $vectorService = new VectorEmbeddingService();
        $featureFolders = array_filter(glob($basePath . '/*'), 'is_dir');
        $count = 0;

        foreach ($featureFolders as $folderPath) {
            $featureName = basename($folderPath);
            // Check if already vectorized
            $alreadyExists = DocumentVector::where('title', $featureName)
                ->where('document_type', 'feature-doc')
                ->exists();
            if ($alreadyExists) {
                $this->info("Already vectorized, skipping: $featureName");
                continue;
            }
            $markdownFiles = glob($folderPath . '/*.md');
            if (empty($markdownFiles)) {
                $this->warn("No markdown file found in $featureName");
                continue;
            }
            $markdownPath = $markdownFiles[0];
            $markdownContent = file_get_contents($markdownPath);

            // Optionally, collect image info
            $images = [];
            foreach (glob($folderPath . '/*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE) as $imgPath) {
                $images[] = [
                    'filename' => basename($imgPath),
                    'path' => $imgPath,
                ];
            }

            $metadata = [
                'feature' => $featureName,
                'markdown_file' => $markdownPath,
                'images' => $images,
            ];

            try {
                $vectorService->storeDocumentVector([
                    'document_id' => Str::uuid()->toString(),
                    'title' => $featureName,
                    'content' => $markdownContent,
                    'document_type' => 'feature-doc',
                    'metadata' => $metadata,
                    'file_path' => $markdownPath,
                ]);
                $this->info("Vectorized and stored: $featureName");
                $count++;
            } catch (Exception $e) {
                $this->error("Failed for $featureName: " . $e->getMessage());
            }
        }

        $this->info("Done. Total vectorized: $count");
    }
}
