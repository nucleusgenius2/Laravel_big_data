<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait FilterHandler
{
    public function filterOrmBuilder($filters = [], $fixeTime = false): Builder
    {
        $tableName = $this->getTable();
        $query = $this;

        foreach ($filters as $field => $value) {
            //поиск по фрагменту значения
            if (isset($this->whereSearch) && in_array($field, $this->whereSearch)) {
                //$query = $query->where($tableName . '.' . $field, 'LIKE', '%'.$value . '%');
                $query = $query->whereRaw("MATCH({$tableName}.{$field}) AGAINST(? IN BOOLEAN MODE)", ["*$value*"]);

            }
            //строгий поиск по точному совпадению
            if (isset($this->whereStrong) && in_array($field, $this->whereStrong)) {
                $query = $query->where($tableName . '.' . $field, '=',  $value );
            }

            //строгий поиск по in
            if (isset($this->whereIn) && in_array($field, $this->whereStrong)) {
                $query = $query->whereIn($tableName . '.' . $field, '=',  $value );
            }

            //поиск в интервале дат
            if (isset($this->intervalSearch) && in_array($field, $this->intervalSearch)) {
                if (str_ends_with($field, 'from')){
                    $field = str_replace('_from', '', $field);
                    $query = $query->where($tableName . '.' . $field, '>=', Carbon::parse($value));
                }
                if (str_ends_with($field, 'to')){
                    $field = str_replace('_to', '', $field);

                    if ( $fixeTime ) {
                        $value .=' 23:59:59';
                    }

                    $query = $query->where($tableName . '.' . $field, '<=', Carbon::parse($value));
                }
            }

            if (isset($this->dateFixed) && in_array($field, $this->dateFixed)) {
                if ($value == 'day') {
                //фильтр – данные за последний день
                    $query = $query->where($tableName . '.' . 'created_at', '>=', Carbon::yesterday());
                } else if ($value == 'week') {
                //фильтр – данные за последнюю неделю
                    $query = $query->where($tableName . '.' . 'created_at', '>=', now()->subDays(7));
                } else if ($value == 'month') {
                //фильтр – данные за этот месяц (с 1 числа этого месяца)
                    $query = $query->where($tableName . '.' . 'created_at', '>=', Carbon::now()->startOfMonth());
                } else if ($value == 'year') {
                //фильтр – данные за этот год
                    $query = $query->where($tableName . '.' . 'created_at', '>=', Carbon::now()->startOfYear());
                }
            }
        }

        return $query;
    }

    public function filterElasticBuilder($filters = [], $page = 1, $perPage = 10, $fixeTime = false): array
    {
        $query = ["bool" => ["must" => []]];

        foreach ($filters as $field => $value) {
            // Поиск по тексту
            if (isset($this->whereSearch) && in_array($field, $this->whereSearch)) {
                $query["bool"]["must"][] = [
                    "match" => [
                        $field => [
                            "query" => $value,
                            "operator" => "and"
                        ]
                    ]
                ];
            }
            // Точное соответствие
            log::info('проверка '.$field);
            if (isset($this->whereStrong) && in_array($field, $this->whereStrong)) {
                log::info('принято '.$field);
                $query["bool"]["must"][] = [
                    "term" => [$field => $value]
                ];
            }

            // Фильтрация по нескольким значениям (аналог WHERE IN)
            if (isset($this->whereIn) && in_array($field, $this->whereIn)) {
                $query["bool"]["must"][] = [
                    "terms" => [
                        $field => is_array($value) ? $value : [$value]
                    ]
                ];
            }

            // Интервал дат
            if (isset($this->intervalSearch) && in_array($field, $this->intervalSearch)) {
                if (str_ends_with($field, 'from')) {
                    $field = str_replace('_from', '', $field);
                    $query["bool"]["must"][] = [
                        "range" => [$field => ["gte" => Carbon::parse($value)->format('Y-m-d H:i:s')]]
                    ];
                }
                if (str_ends_with($field, 'to')) {
                    $field = str_replace('_to', '', $field);
                    if ($fixeTime) {
                        $value .= ' 23:59:59';
                    }
                    $query["bool"]["must"][] = [
                        "range" => [$field => ["lte" => Carbon::parse($value)->format('Y-m-d H:i:s')]]
                    ];
                }
            }
        }

        // Параметры пагинации
        $from = ($page - 1) * $perPage;

        return [
            "_source" => ["id"], // Запрашиваем только id постов
            "query" => $query,
            "size" => $perPage,
            "from" => $from,
            "track_total_hits" => true
        ];
    }
}
