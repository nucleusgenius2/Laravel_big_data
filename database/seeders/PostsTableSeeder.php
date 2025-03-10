<?php


namespace Database\Seeders;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Log;

class PostsTableSeeder extends Seeder
{
    public function run()
    {
        $post = Post::find(1);
        if (!$post) {
            $faker = Faker::create();

            $batchSize = 50;  // Устанавливаем размер пакета
            $totalRows = 5000000;
            $chunks = $totalRows / $batchSize;

            for ($i = 1; $i <= $chunks; $i++) {
                $data = [];
                $bulkData = '';  // Для хранения bulk данных

                for ($j = 0; $j < $batchSize; $j++) {
                    $postNumber = ($i - 1) * $batchSize + $j + 1;

                    $postData = [
                        'name' => "Post Name $postNumber",
                        'short_description' => $faker->text(200),
                        'full_description' => $faker->text(500),
                        'category_id' => random_int(1, 10),
                        'author_id' => random_int(1, 10),
                        'rating' => random_int(1, 20),
                        'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    ];

                    // Подготовка данных для Elasticsearch (форматирование даты)
                    $esPostData = $postData;
                    $esPostData['created_at'] = Carbon::parse($postData['created_at'])->format('Y-m-d H:i:s');

                    // Формирование bulk данных для добавления в Elasticsearch
                    $bulkData .= json_encode([
                            'index' => [
                                '_index' => 'posts',
                                '_id' => $postNumber,  // ID для каждого документа
                            ]
                        ]) . "\n";
                    $bulkData .= json_encode($esPostData) . "\n";
                }

                // Отправка bulk-запроса в Elasticsearch
                $response = Http::withBody($bulkData, 'application/x-ndjson')
                    ->post('http://localhost:9200/_bulk');

                if ($response->successful()) {
                    Log::info("Bulk request successful: {$response->body()}");
                } else {
                    Log::error("Bulk request failed: {$response->status()} - {$response->body()}");
                }

                echo "выполнена часть $i из $chunks\n";
            }
        }
    }
}
