<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\JsonResponse;
use \App\Traits\StructuredResponse;

class AuthorsController
{
    use StructuredResponse;

    public function index(): JsonResponse
    {
        $authors = Author::join('users', 'authors.user_id', '=', 'users.id')->distinct()->pluck('users.name', 'authors.user_id');

        $this->code = 200;
        if (count($authors ) > 0) {
            $this->status = 'success';
            $this->json['data'] = $authors ;

        } else {
            $this->text = 'Авторов не найдено';
        }

        return $this->responseJsonApi();
    }
}
