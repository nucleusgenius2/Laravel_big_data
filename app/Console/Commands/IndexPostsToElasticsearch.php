<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IndexPostsToElasticsearch extends Command
{
    protected $signature = 'elasticsearch:index-posts {--from=0}';
    protected $description = 'Индексирует посты в Elasticsearch';

    public function handle()
    {
        $fromId = (int) $this->option('from'); // ID, с которого начать
        $batchSize = 200;  // Размер пачки
        $maxBulkSize = 10 * 1024 * 1024; // 10MB - максимальный размер запроса
        $bulkData = '';

        $this->info("Начинаем индексирование постов в Elasticsearch с ID: $fromId");

        Post::where('id', '>=', $fromId)
            ->orderBy('id')
            ->chunk($batchSize, function ($posts) use (&$bulkData, $maxBulkSize) {
                foreach ($posts as $post) {
                    $indexData = json_encode([
                            'index' => [
                                '_index' => 'posts',
                                '_id' => $post->id,
                            ]
                        ]) . "\n";

                    $postData = json_encode([
                            'id' => $post->id,
                            'name' => $post->name,
                            'short_description' => $post->short_description,
                            'full_description' => $post->full_description,
                            'category_id' => $post->category_id,
                            'author_id' => $post->author_id,
                            'rating' => $post->rating,
                            'created_at' => Carbon::parse($post->created_at)->format('Y-m-d H:i:s'),
                        ]) . "\n";

                    // Проверяем, не превышает ли размер `$bulkData` 10MB
                    if (strlen($bulkData) + strlen($indexData) + strlen($postData) > $maxBulkSize) {
                        $this->sendToElasticsearch($bulkData);
                        $bulkData = ''; // Сбрасываем буфер
                    }

                    $bulkData .= $indexData . $postData;

                    Log::info("Записан пост в Elasticsearch: ID {$post->id}");
                }

                // Отправляем оставшиеся данные после обработки пачки
                if (!empty($bulkData)) {
                    $this->sendToElasticsearch($bulkData);
                    $bulkData = ''; // Сбрасываем буфер
                }

                usleep(80000);
            });

        $this->info("Индексирование завершено.");
    }

    private function sendToElasticsearch($bulkData)
    {


        $response = Http::withBody($bulkData, 'application/x-ndjson')
            ->post(config('elasticsearch.elastic_search_url').'/_bulk');

        if ($response->successful()) {
            Log::info("Bulk-запрос выполнен успешно.");
        } else {
            Log::error("Ошибка Elasticsearch: " . $response->status() . " - " . $response->body());
        }
    }
}
