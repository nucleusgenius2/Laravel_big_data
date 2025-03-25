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

    protected PostService $service;

    public function __construct(PostService $service)
    {
        $this->service = $service;
    }

    /**
     * Пагинированный список новостей
     * @param PostSearchRequest $request
     * @param Post $post
     * @return JsonResponse
     */
    public function index(PostSearchRequest $request, Post $post): JsonResponse
    {
        $data = $request->validated();

        $dataObjectDTO = $this->service->getPosts(
            data: $data,
            modelPost: $post,
            perPage: $this->perPageFrontend,
        );

        $this->status = 'success';
        $this->code = 200;
        $this->json = $dataObjectDTO->data;

        return $this->responseJsonApi();
    }


    /**
     * Возвращает конкретную новость
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        if ($id > 0) {
            $dataObjectDTO = $this->service->getPost(id: $id);

            if ($dataObjectDTO->status) {
                $this->status = 'success';
                $this->code = 200;
                $this->json  = $dataObjectDTO->data;
            } else {
                $this->text = $dataObjectDTO->error;
                $this->code = $dataObjectDTO->code;
            }
        }

        return $this->responseJsonApi();
    }

}
