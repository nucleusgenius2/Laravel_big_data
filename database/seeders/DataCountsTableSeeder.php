<?php

namespace Database\Seeders;

use App\Models\DataCount;
use Illuminate\Database\Seeder;

class DataCountsTableSeeder extends Seeder
{

    public function run()
    {
       $postCount = DataCount::where('type', 'posts_counts')->first();
       if (!$postCount){
           DataCount::create([
               'type' => 'posts_counts',
               'count' => 5000000
              ]
           );
       }
    }

}
