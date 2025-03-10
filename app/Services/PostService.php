<?php

namespace App\Services;

use App\DTO\DataArrayDTO;
use App\Models\DataCount;
use App\Models\Post;
use Illuminate\Support\Facades\Log;

class PostService
{
    public function getPosts(array $data, Post $modelPost, int $perPage, int $paginationLimit): DataArrayDTO
    {
        $offset = ($data['page'] - 1) * $perPage;
        $offsetPagination = ($data['page'] - 1) * $paginationLimit;

        if ( isset($data['name'])
            || isset($data['created_at_to'])
            || isset($data['created_at_from'])
            || isset($data['date_fixed'])
            || isset($data['rating'])
            || isset($data['authors'])
        ){
            $query = $modelPost->filterCustom($data);

            if (isset($data['authors']) ){
                $query = $query->where('posts.author_id', $data['authors']);
            }

            $queryForCount = clone $query;
            $queryForIds = clone $query;

            //получаем id нужных строк из индекса
            $idsRow = $queryForIds->select('id');
            //сортировка слишком дорогая при поиске
            log::info('1111');
            if(isset($data['name1']) ){
                $search = $data['name'];
                $idsSearch = $modelPost::select('id')
                    ->whereRaw("MATCH(posts.name) AGAINST(? IN BOOLEAN MODE)", ["*$search*"])
                    ->skip($offset)
                    ->take($perPage) //острожно, без лимита падает база при поиске
                    ->pluck('id');
                log::info( $idsSearch);
            }
            if (isset($idsSearch) && $idsSearch->isNotEmpty()) {
                $idsRow = $idsRow->whereIn('id', $idsSearch);
            }
            $idsRow = $idsRow
                ->orderBy('posts.created_at', 'desc')
                ->skip($offset)
                ->take($perPage)
                ->pluck('id');

            if ($idsRow->isEmpty()) {
                return new DataArrayDTO(status: true, data: []);
            }

            //извлечение данных из таблицы по id
            /*
            $query = $modelPost->whereIn('posts.id', $idsRow)
                ->join('users', 'posts.author_id', '=', 'users.id')
                ->select('posts.*', 'users.name as author_name');
             ->orderBy('posts.created_at', 'desc');
            */

            //получаем общее количество записей удовлетворяющих условию фильтра
            $count = $queryForCount
                ->select('id')
                ->take($paginationLimit)
                ->skip($offsetPagination)
                ->get()
                ->count();

            $postList = $modelPost->whereIn('posts.id', $idsRow)
                ->join('users', 'posts.author_id', '=', 'users.id')
                ->select('posts.*', 'users.name as author_name')
                ->orderByRaw("FIELD(posts.id, " . implode(',', $idsRow->toArray()) . ")")
                ->get();


        }
        else{
            $postList = Post::join('users', 'posts.author_id', '=', 'users.id')
                ->select('posts.*', 'users.name as author_name')
                ->orderBy('id', 'desc')
                ->skip($offset)
                ->take($perPage)
                ->get();

            $count = DataCount::select('count')->where('type', 'posts_counts')->first();
        }

        $returnData = [
            'count' => floor(( $count->count ?? $count ) / $perPage),
            'data' => $postList
        ];

        return new DataArrayDTO(status: true, data: $returnData);
    }
}
