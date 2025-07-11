<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\GeminiEmbeddingService;
use Illuminate\Support\Str;
use Exception;
use App\Models\GeminiEmbedding;

class VectorizeGeminiEmbeddingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vectorize:gemini {feature?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan markdown files in storage/app/private, generate Gemini embeddings, and store in MongoDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $basePath = storage_path('app/private');
        $geminiService = new GeminiEmbeddingService();
        $featureFolders = array_filter(glob($basePath . '/*'), 'is_dir');
        $count = 0;
        $featureArg = $this->argument('feature');

        foreach ($featureFolders as $folderPath) {
            $featureName = basename($folderPath);
            if ($featureArg && $featureArg !== $featureName) {
                continue;
            }
            $alreadyExists = GeminiEmbedding::where('title', $featureName)
                ->where('document_type', 'feature-doc')
                ->exists();
            if ($alreadyExists) {
                $this->info("Already embedded, skipping: $featureName");
                continue;
            }
            $markdownFiles = glob($folderPath . '/*.md');
            if (empty($markdownFiles)) {
                $this->warn("No markdown file found in $featureName");
                continue;
            }
            $markdownPath = $markdownFiles[0];
            $markdownContent = file_get_contents($markdownPath);

            // Split content into paragraphs
            $paragraphs = preg_split('/\n\n+/', $markdownContent);
            $chunks = [];
            $currentChunk = '';
            $charLimit = 1000; // Conservative limit for Gemini API
            foreach ($paragraphs as $para) {
                $para = trim($para);
                if ($para === '') continue;
                if (strlen($currentChunk) + strlen($para) + 2 > $charLimit) {
                    if ($currentChunk !== '') {
                        $chunks[] = $currentChunk;
                        $currentChunk = '';
                    }
                }
                if ($currentChunk !== '') {
                    $currentChunk .= "\n\n";
                }
                $currentChunk .= $para;
            }
            if ($currentChunk !== '') {
                $chunks[] = $currentChunk;
            }
            $chunkCount = 0;
            foreach ($chunks as $i => $chunk) {
                if (trim($chunk) === '') continue;
                try {
                    $embedding = $geminiService->generateEmbedding($chunk, 'RETRIEVAL_DOCUMENT', $featureName . " [chunk $i]");
                    GeminiEmbedding::create([
                        'document_id' => \Illuminate\Support\Str::uuid()->toString(),
                        'vector_embedding' => $embedding,
                        'embedding_model' => 'gemini-embedding-exp-03-07',
                        'content' => $chunk, // Store the original text chunk
                        'metadata' => [
                            'feature' => $featureName,
                            'chunk_index' => $i,
                            'markdown_file' => $markdownPath,
                        ],
                        'file_path' => $markdownPath,
                        'document_type' => 'feature-doc-chunk',
                    ]);
                    $this->info("Embedded and stored: $featureName [chunk $i]");
                    $chunkCount++;
                } catch (Exception $e) {
                    $this->error("Failed for $featureName [chunk $i]: " . $e->getMessage());
                }
            }
            $count += $chunkCount;
        }
        $this->info("Done. Total embedded chunks: $count");
    }
}
