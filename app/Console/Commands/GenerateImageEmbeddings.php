<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

#[Signature('app:generate-image-embeddings {file}')]
#[Description('Command description')]
class GenerateImageEmbeddings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get file patch from optional argument
        $file = $this->argument('file');
        $path = storage_path("app/{$file}");

        // check if file exists
        if (!File::exists($path)) {
            $this->error("File {$file} not found");
            return;
        }

        // get file extension
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        // read the csv file
        $rows = LazyCollection::make(function () use ($path) {
            $handle = fopen($path, 'r');

            // read the header
            $header = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                yield array_combine($header, $data);
            }

            fclose($handle);
        });

        $this->info("Processing images...");

        $rows->each(function ($row) {
            $this->comment("Encoding image {$row['image_path']}: {$row['description']}");
            $embedding = $this->getEmbedding($row['image_path'], $row['description']);
            logger()->info("Embedding for image {$row['image_path']}", $embedding);
        });
    }

    private function getEmbedding(string $imagePath, string $description): array|null
    {
        $path = storage_path("app/{$imagePath}");

        if (! File::exists($path)) {
            $this->error("Image {$imagePath} not found");
            return null;
        }

        $imageContents = file_get_contents($path);
        $base64Image = base64_encode($imageContents);
        $mimeType = File::mimeType($path);

        $response = Http::timeout(60)->withHeaders([
            'Authorization' => 'Bearer '.config('services.openrouter.key'),
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/embeddings', [
            'model' => 'google/gemini-embedding-2-preview',
            'input' => [
                [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => "task: clustering | query: {$description}",
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$base64Image}",
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        if ($response->successful()) {
            return $response->json('data.0.embedding');
        }

        $this->error("Failed to get embedding for image {$response->body()}");
        return null;
    }
}
