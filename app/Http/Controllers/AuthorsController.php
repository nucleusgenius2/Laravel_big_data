<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\DataCount;
use Illuminate\Http\JsonResponse;
use \App\Traits\StructuredResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthorsController
{
    use StructuredResponse;

    public int $perPageFrontend = 10;

    public function index(Request $request): JsonResponse
    {
        $validated = Validator::make($request->all(), [
            'page' => 'required|integer|min:1',
        ]);

        if ($validated->fails()) {
            $this->text = $validated->errors();
        } else {
            $data = $validated->valid();

            $offset = ($data['page'] - 1) * $this->perPageFrontend;
            $authors = Author::join('users', 'authors.user_id', '=', 'users.id')
                ->select('authors.user_id', 'users.name')
                ->skip($offset)
                ->take($this->perPageFrontend)
                ->get();
            $this->code = 200;

            $count = DataCount::select('count')->where('type', 'authors_counts')->first();
            $this->json['count'] = floor(( $count->count ?? $count ) / $this->perPageFrontend);

            if (count($authors) > 0) {
                $this->status = 'success';
                $this->json['data'] = $authors;

            } else {
                $this->text = 'Авторов не найдено';
            }
        }

        return $this->responseJsonApi();
    }
}
