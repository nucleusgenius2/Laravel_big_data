<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait FilterHandler
{
    public function filterOrmBuilder(array $filters = [], $fixeTime = false): Builder
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


    /**
     * Билдер запросов в эластик через классический индекс
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param bool $fixeTime
     * @return array
     */
    public function filterElasticBuilder(array $filters = [], int $page = 1, int $perPage = 10, bool $fixeTime = false): array
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
            if (isset($this->whereStrong) && in_array($field, $this->whereStrong)) {
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

                // Фильтрация по фиксированным датам (day, week, month, year)
                if (isset($this->dateFixed) && in_array($field, $this->dateFixed)) {
                    $dateField = 'created_at';
                    if ($value == 'day') {
                        // Данные за последний день
                        $query["bool"]["must"][] = [
                            "range" => [
                                $dateField => [
                                    "gte" => Carbon::yesterday()->format('Y-m-d H:i:s')
                                ]
                            ]
                        ];
                    } else if ($value == 'week') {
                        // Данные за последнюю неделю
                        $query["bool"]["must"][] = [
                            "range" => [
                                $dateField => [
                                    "gte" => now()->subDays(7)->format('Y-m-d H:i:s')
                                ]
                            ]
                        ];
                    } else if ($value == 'month') {
                        // Данные за текущий месяц (с 1 числа)
                        $query["bool"]["must"][] = [
                            "range" => [
                                $dateField => [
                                    "gte" => Carbon::now()->startOfMonth()->format('Y-m-d H:i:s')
                                ]
                            ]
                        ];
                    } else if ($value == 'year') {
                        // Данные за текущий год
                        $query["bool"]["must"][] = [
                            "range" => [
                                $dateField => [
                                    "gte" => Carbon::now()->startOfYear()->format('Y-m-d H:i:s')
                                ]
                            ]
                        ];
                    }
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


    /**
     * Билдер запросов в эластик через индекс сочетающий поиск по словам, а также подсказки
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param bool $fixeTime
     * @return array
     */
    public function filterElasticSuggesterBuilder(array $filters = [], int $page = 1, int $perPage = 10, bool $fixeTime = false): array
    {
        $query = ["bool" => ["should" => []]];

        foreach ($filters as $field => $value) {
            // Поиск по тексту (целое слово и фрагменты)
            if (isset($this->whereSearch) && in_array($field, $this->whereSearch)) {
                // Поиск по фрагментам слов
                $query["bool"]["should"][] = [
                    "wildcard" => [
                        $field . ".ngram" => [
                            "value" => $value . "*"
                        ]
                    ]
                ];
                // Поиск по целому слову
                $query["bool"]["should"][] = [
                    "match" => [
                        $field . ".full" => [
                            "query" => $value,
                            "operator" => "and"
                        ]
                    ]
                ];
            }

            // Точное соответствие
            if (isset($this->whereStrong) && in_array($field, $this->whereStrong)) {
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

            // Фильтрация по фиксированным датам (day, week, month, year)
            if (isset($this->dateFixed) && in_array($field, $this->dateFixed)) {
                $dateField = 'created_at';
                if ($value == 'day') {
                    // Данные за последний день
                    $query["bool"]["must"][] = [
                        "range" => [
                            $dateField => [
                                "gte" => Carbon::yesterday()->format('Y-m-d H:i:s')
                            ]
                        ]
                    ];
                } else if ($value == 'week') {
                    // Данные за последнюю неделю
                    $query["bool"]["must"][] = [
                        "range" => [
                            $dateField => [
                                "gte" => now()->subDays(7)->format('Y-m-d H:i:s')
                            ]
                        ]
                    ];
                } else if ($value == 'month') {
                    // Данные за текущий месяц (с 1 числа)
                    $query["bool"]["must"][] = [
                        "range" => [
                            $dateField => [
                                "gte" => Carbon::now()->startOfMonth()->format('Y-m-d H:i:s')
                            ]
                        ]
                    ];
                } else if ($value == 'year') {
                    // Данные за текущий год
                    $query["bool"]["must"][] = [
                        "range" => [
                            $dateField => [
                                "gte" => Carbon::now()->startOfYear()->format('Y-m-d H:i:s')
                            ]
                        ]
                    ];
                }
            }
        }


        $from = ($page - 1) * $perPage;

        return [
            "_source" => ["id"], // Запрашиваем только id постов из эластика
            "query" => $query,
            "size" => $perPage,
            "from" => $from,
            "track_total_hits" => true
        ];
    }
}
