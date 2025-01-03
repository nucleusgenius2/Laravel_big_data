<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\DataCount;
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
        $author = Author::exists();
        if (!$author) {
            Author::insert([
                [
                    'user_id' => '1',
                ],
                [
                    'user_id' => '2',
                ],
                [
                    'user_id' => '3',
                ],
                [
                    'user_id' => '4',
                ],
                [
                    'user_id' => '5',
                ],
                [
                    'user_id' => '6',
                ],
                [
                    'user_id' => '7',
                ],
                [
                    'user_id' => '8',
                ],
                [
                    'user_id' => '9',
                ],
                [
                    'user_id' => '10',
                ],
            ]);
        }
    }
}
