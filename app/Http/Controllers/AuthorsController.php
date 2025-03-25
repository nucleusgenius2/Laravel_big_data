<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageRequest;
use App\Services\AuthorsService;
use Illuminate\Http\JsonResponse;
use \App\Traits\StructuredResponse;

class AuthorsController
{
    use StructuredResponse;

    public int $perPageFrontend = 10;

    protected AuthorsService $service;

    public function __construct(AuthorsService $service)
    {
        $this->service = $service;
    }

    /**
     * Пагинированный список авторов
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(PageRequest $request): JsonResponse
    {
        $data = $request->validated();

        $dataObjectDTO = $this->service->getAuthors(
            page: $data['page'],
            perPage: $this->perPageFrontend,
        );

        $this->status = 'success';
        $this->code = 200;
        $this->json = $dataObjectDTO->data;

        return $this->responseJsonApi();
    }
}
