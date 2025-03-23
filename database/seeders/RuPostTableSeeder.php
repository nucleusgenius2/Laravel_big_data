<?php

namespace Database\Seeders;

use App\Models\Post;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class RuPostTableSeeder extends Seeder
{
    public function run()
    {
        // Проверяем, есть ли уже пост с таким названием
        $postCount = Post::where('name', 'Зеленая миля (1999)')->first();

        if (!$postCount) {
            $faker = Faker::create();

            // Создаем массив с постами для вставки
            $posts = [
                [
                    'name' => "Зеленая миля (1999)",
                    'short_description' => 'Пол Эджкомб — начальник блока смертников в тюрьме «Холодная гора»,',
                    'full_description' => 'Пол Эджкомб — начальник блока смертников в тюрьме «Холодная гора», каждый из узников которого однажды проходит «зеленую милю» по пути к месту казни. Пол повидал много заключённых и надзирателей за время работы. Однако гигант Джон Коффи, обвинённый в страшном преступлении, стал одним из самых необычных обитателей блока.',
                    'category_id' => random_int(1, 10),
                    'author_id' => random_int(1, 10),
                    'rating' => random_int(1, 20),
                    'created_at' => $faker->dateTimeBetween('now'),
                ],
                [
                    'name' => "Побег из Шоушенка (1994)",
                    'short_description' => 'Бухгалтер Энди Дюфрейн обвинён в убийстве собственной жены и её любовника',
                    'full_description' => 'Бухгалтер Энди Дюфрейн обвинён в убийстве собственной жены и её любовника. Оказавшись в тюрьме под названием Шоушенк, он сталкивается с жестокостью и беззаконием, царящими по обе стороны решётки. Каждый, кто попадает в эти стены, становится их рабом до конца жизни. Но Энди, обладающий живым умом и доброй душой, находит подход как к заключённым, так и к охранникам, добиваясь их особого к себе расположения.',
                    'category_id' => random_int(1, 10),
                    'author_id' => random_int(1, 10),
                    'rating' => random_int(1, 20),
                    'created_at' => $faker->dateTimeBetween('now'),
                ]
            ];


            Post::insert($posts);
        }
    }

}
