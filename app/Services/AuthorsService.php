<?php

namespace App\Services;

use App\DTO\DataArrayDTO;
use App\Models\Author;
use App\Models\DataCount;
use App\Models\Post;

class AuthorsService
{
    public function getAuthors(int $page, int $perPage): DataArrayDTO
    {
        $offset = ($page - 1) * $perPage;

        $authors = Author::join('users', 'authors.user_id', '=', 'users.id')
            ->select('authors.user_id', 'users.name')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $count = DataCount::select('count')->where('type', 'authors_counts')->first();

        $returnData = [
            'count' => floor(( $count->count ?? $count ) / $perPage),
            'data' => $authors
        ];

        return new DataArrayDTO(status: true, data: $returnData);
    }
}
