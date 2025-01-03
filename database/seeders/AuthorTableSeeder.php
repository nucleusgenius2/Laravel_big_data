<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $author = DB::table('authors')->exists();

        if (!$author) {
            $authors = [];

            for ($postId = 1; $postId <= 5000000; $postId++) {
                $authors[] = [
                    'user_id' => ($postId % 10) + 1,
                    'post_id' => $postId,
                ];

                // Вставляем порциями по 10,000 записей для оптимизации
                if ($postId % 10000 === 0) {
                    DB::table('authors')->insert($authors);
                    $authors = [];
                }
            }

            // Вставляем оставшиеся записи, если они есть
            if (!empty($authors)) {
                DB::table('authors')->insert($authors);
            }
        }
    }
}
