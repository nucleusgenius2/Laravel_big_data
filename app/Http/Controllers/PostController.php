<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DataCount;
use App\Models\Post;
use App\Traits\StructuredResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PostController
{
    use StructuredResponse;

    public function index(Request $request, Post $post): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'page' => 'required|integer|min:1',
            'created_at_from' => 'string|date',
            'created_at_to' => 'string|date',
            'name' => 'string|min:1|max:50',
            'date_fixed' => 'string|in:day,week,month,year',
            'rating' => 'string|min:1|max:50',
        ]);

        if ($validated->fails()) {
            $this->text = $validated->errors();
        } else {
            $data = $validated->valid();
            $offset = ($data['page'] - 1) * 10;

            if ( isset($data['name'])
                || isset($data['created_at_to'])
                || isset($data['created_at_from'])
                || isset($data['date_fixed'])
                || isset($data['rating'])
            ){
                $query = $post->filterCustom($data);
                $queryForCount = clone $query;
                $count = $queryForCount->count();

                $postList = $query::join('users', 'posts.author_id', '=', 'users.id')
                    ->select('posts.*', 'users.name as author_name')
                    ->orderBy('created_at', 'desc')->skip($offset)->take(10)->get();
            }
            else{
                $postList = Post::join('users', 'posts.author_id', '=', 'users.id')
                    ->select('posts.*', 'users.name as author_name')
                    ->orderBy('id', 'desc')->skip($offset)->take(10)->get();
                $count = DataCount::select('count')->where('type', 'posts_counts')->first();
            }

            $this->code = 200;
            $this->json['count'] = floor(( $count->count ?? $count ) / 10 );
            if (count($postList) > 0) {
                $this->status = 'success';
                $this->json['data'] = $postList;

            } else {
                $this->text = 'Запрашиваемой страницы не существует';
            }
        }

        return $this->responseJsonApi();
    }


    public function show(int $id): JsonResponse
    {
        $validated = Validator::make(['id' => $id], [
            'id' => 'required|integer|min:1',
        ]);

        if ($validated->fails()) {
            $this->text = $validated->errors();
        } else {
            $data = $validated->valid();

            $contentPostSingle = Cache::rememberForever('post_id_'.$data['id'], function () use ($data) {
                return Post::where('id', '=', $data['id'])->get();
            });

            if (count($contentPostSingle) > 0) {
                $this->status = 'success';
                $this->code = 200;
                $this->json = $contentPostSingle;
            } else {
                $this->text = 'Запрашиваемой новости не существует';
            }
        }

        return $this->responseJsonApi();
    }

}
