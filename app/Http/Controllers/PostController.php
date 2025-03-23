<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PostSearchRequest;
use App\Models\Post;
use App\Services\PostService;
use App\Traits\StructuredResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PostController
{
    use StructuredResponse;

    public int $perPageFrontend = 10;
    public int $paginationLimit = 200;

    protected PostService $service;

    public function __construct(PostService $service)
    {
        $this->service = $service;
    }

    public function index(PostSearchRequest $request, Post $post): JsonResponse
    {
        $data = $request->validated();

        $dataObjectDTO = $this->service->getPosts(
            data: $data,
            modelPost: $post,
            perPage: $this->perPageFrontend,
            paginationLimit: $this->paginationLimit
        );

        $this->status = 'success';
        $this->code = 200;
        $this->json = $dataObjectDTO->data;

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
