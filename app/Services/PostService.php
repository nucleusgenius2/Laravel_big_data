<?php

namespace App\Services;

use App\DTO\DataArrayDTO;
use App\Models\DataCount;
use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostService
{
    public function getPosts(array $data, Post $modelPost, int $perPage, int $paginationLimit): DataArrayDTO
    {
        $offset = ($data['page'] - 1) * $perPage;
        $offsetPagination = ($data['page'] - 1) * $paginationLimit;

        //если в запросе есть поиск ищем через эластик серч
        if (isset($data['name'])) {

            $query = $modelPost->filterElasticBuilder(filters: $data, page: $data['page'], perPage: $perPage);

            log::info(json_encode($query));
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('http://localhost:9200/posts/_search', $query);

            // Получаем JSON-ответ
            $data = $response->json();
            log::info($data );

            // Извлекаем общее количество найденных записей
            $count = $data['hits']['total']['value'] ?? 0;
            log::info('$count'.$count);

            // Извлекаем все ID постов в массив
            $idsRow = collect($data['hits']['hits'])->pluck('_source.id');

            if ($idsRow->isEmpty()) {
                return new DataArrayDTO(status: true, data: []);
            }

            $postList = $modelPost->whereIn('posts.id', $idsRow)
                ->join('users', 'posts.author_id', '=', 'users.id')
                ->select('posts.*', 'users.name as author_name')
                ->orderByRaw("FIELD(posts.id, " . implode(',', $idsRow->toArray()) . ")")
                ->get();

            log::info('массив');
            log::info($idsRow);
            log::info( $postList);
        }
        //ветка поиска через реляционную базу
        else {
            //ветка фильтра
            if (
                isset($data['created_at_to'])
                || isset($data['created_at_from'])
                || isset($data['date_fixed'])
                || isset($data['rating'])
                || isset($data['authors'])
            )
            {
                $query = $modelPost->filterOrmBuilder(filters: $data);

               // if (isset($data['authors'])) {
                    //$query = $query->where('posts.author_id', $data['authors']);
                //}

                $queryForCount = clone $query;
                $queryForIds = clone $query;

                //получаем id нужных строк из индекса
                $idsRow = $queryForIds->select('id')
                    ->select('id')
                    ->orderBy('posts.created_at', 'desc')
                    ->skip($offset)
                    ->take($perPage)
                    ->pluck('id');

                if ($idsRow->isEmpty()) {
                    return new DataArrayDTO(status: true, data: []);
                }


                //получаем общее количество записей удовлетворяющих условию фильтра, за вычетом пагинации
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
            //без фильтра
            else{
                $postList = Post::join('users', 'posts.author_id', '=', 'users.id')
                    ->select('posts.*', 'users.name as author_name')
                    ->orderBy('id', 'desc')
                    ->skip($offset)
                    ->take($perPage)
                    ->get();

                $count = DataCount::select('count')->where('type', 'posts_counts')->first();
            }


        }

        $returnData = [
            'count' => floor(( $count->count ?? $count ) / $perPage),
            'data' => $postList
        ];

        return new DataArrayDTO(status: true, data: $returnData);
    }
}
