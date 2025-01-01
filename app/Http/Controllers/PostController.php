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

               // $postList = $query->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $data['page']);
                $postList = $query->orderBy('id', 'desc')->skip($offset)->take(10)->get();
                $count = $query->count();
                log::info($count);
            }
            else{
                $postList = Post::orderBy('id', 'desc')->skip($offset)->take(10)->get();
                $count = DataCount::select('count')->where('type', 'posts_counts')->first();
            }

            $this->code = 200;

            if (count($postList) > 0) {
                $this->status = 'success';
                $this->json['data'] = $postList;
                $this->json['count'] =  $count->count ?? $count;
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


    public function store(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'content' => 'nullable|string',
            'short_description' => 'nullable|string|max:300',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
            'img' => 'nullable|image|mimes:png,jpg,jpeg',
            'category_id' => 'nullable|int',
            'author' => 'required|string|max:100',
        ]);

        if ($validated->fails()) {
            $this->text = $validated->errors();
        } else {
            $data = $validated->valid();

                $arraySavePost = [
                    'name' => $data['name'],
                    'content' => $data['content'] ?? '',
                    'short_description' => $data['short_description'] ?? '',
                    'seo_title' => $data['seo_title'] ?? '',
                    'seo_description' => $data['seo_description'] ?? '',
                    'img' => $imgUpload['img'] ?? '',
                    'category_id' => $data['category_id'] ?? 0,
                    'author' => $data['author']
                ];

                $post = Post::create($arraySavePost);

                if ($post) {
                    $this->status = 'success';
                    $this->code = 200;
                    $this->text = 'Запись создана';
                    $this->json = $post->id;
                } else {
                    $this->text = 'Запись не была создана';
                }
        }

        return $this->responseJsonApi();
    }

    public function update(Request $request): JsonResponse

    {
        $validated = Validator::make($request->all(), [
            'id' => 'required|int',
            'name' => 'required|string|min:3|max:255',
            'content' => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:300',
            'category_id' => 'nullable|int',
        ]);


        if ($validated->fails()) {
            $this->text = $validated->errors();
        } else {
            $data = $validated->valid();


            if ($validated->fails()) {
                $this->text = $validated->errors();
            } else {
                $arraySavePost = Post::where('id', '=', $data['id'])->update([
                    'name' => $data['name'],
                    'content' => $data['content'] ?? '',
                    'short_description' => $data['short_description'] ?? '',
                    'seo_title' => $data['seo_title'] ?? '',
                    'seo_description' => $data['seo_description'] ?? '',
                    'img' => $imgUpload['img'] ?? '',
                    'category_id' => $data['category_id'] ?? 0,
                ]);

                if ($arraySavePost) {
                    $this->status = 'success';
                    $this->code = 200;
                    $this->text = 'Запись создана';
                }
                else {
                    $this->text = 'Запрашиваемой страницы не существует';
                }
            }
        }

        return $this->responseJsonApi();
    }


    public function destroy(int $id): JsonResponse
    {
        $validated = Validator::make(['id' => $id], [
            'id' => 'required|integer|min:1',
        ]);

        if ($validated->fails()) {
            $this->text = $validated->errors();
        } else {
            $data = $validated->valid();

            $post = Post::where('id', '=', $data['id'])->delete();

            if ($post) {
                $this->status = 'success';
                $this->code = 200;
            } else {
                $this->text = 'Запрашиваемого ресурса не существует';
            }
        }

        return $this->responseJsonApi();
    }
}
