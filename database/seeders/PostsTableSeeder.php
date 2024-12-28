<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PostsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $batchSize = 1000; // Количество строк на один запрос вставки
        $totalRows = 5000000; // Всего строк
        $chunks = $totalRows / $batchSize;

        for ($i = 1; $i <= $chunks; $i++) {
            $data = [];

            for ($j = 0; $j < $batchSize; $j++) {
                $postNumber = ($i - 1) * $batchSize + $j + 1;

                $data[] = [
                    'name' => "Post Name $postNumber",
                    'short_description' => $faker->text(200),
                    'full_description' => $faker->text(500),
                    'category_id' => random_int(1, 10),
                    'author_id' => random_int(1, 10),
                    'views' => random_int(0, 100000),
                    'rating' => random_int(1, 20),
                    'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                ];
            }

            DB::table('posts')->insert($data);
            echo "Inserted chunk $i of $chunks\n";
        }
    }
}
