Имитация большой таблицы с новостями (5 миллионов строк). Различные подходы к оптимизации работы фильтров с данной таблицей (без кеша).

## Используемый стек

- PHP 8.1
- MySQL 8.0
- Laravel 10
- Elasticsearch 8.17.3
- Vue 3 composition api
- Vue router
- Axios

## Установка
- создайте свой файл env
- composer install
- npm install
- php artisan migrate
- php artisan db:seed (создаст 5 миллионов новостей и авторов к новостям)
- npm run build

## Возможности фильтрации
- Поиск по словам, применимый к названию новости с минимальным вхождением в 3 символа, с пониманием семантики русского языка ("зеленый", найдет "зеленый","зеленое"...)
- поиск по интервалам дат
- поиск по рейтингу
- поиск по автору
- комбинации выше указанных фильтров
- пагинация правильно рендерится при любом запросе

## Стратегии оптимизации:

- вынос всего запросов с поиском в Elasticsearch, (увы полнотекстовый индекс плохо комбинируется с другими параметрами поиска, поэтому было решено запросы с поиском делать полностью через эластик, несмотря на удобство получения любого вхождения %value%). Эластик возвращает данные в виде id новостей.
- запросы выполняющиеся через mysql построены по схеме -> сначала получаем id из индекса -> только потом по id уже получаем содержимое новостей, поскольку первый запрос работает с покрывающим индексом это быстро.
- учет количества новостей в отдельной таблице, чтобы избежать тяжелого запроса count без фильтров
- колоночные B-TREE индексы для всех колонок, по которым может быть поиск
- несколько комбинаций составных индексов с правильным методом сортировки desc

## Индексы для таблицы posts:
- Полнотекстовый: CREATE FULLTEXT INDEX idx_fulltext_name ON posts(name);
- B-TREE: CREATE INDEX idx_rating ON posts (rating);
- B-TREE: CREATE INDEX idx_created_at ON posts (created_at)
- B-TREE: CREATE INDEX idx_rating_created_at_desc ON posts(rating, created_at DESC);
- B-TREE: CREATE INDEX idx_author_created ON posts (author_id, created_at);
- B-TREE: CREATE INDEX idx_author_id_rating_created_at ON posts (author_id, rating, created_at DESC);

## Индекс для Elasticsearch posts
```json
{
  "settings": {
    "number_of_shards": 2,
    "number_of_replicas": 0,
    "analysis": {
      "tokenizer": {
        "ngram_tokenizer": {
          "type": "ngram",
          "min_gram": 3,
          "max_gram": 4,
          "token_chars": ["letter", "digit"]
        }
      },
      "analyzer": {
        "ngram_analyzer": {
          "type": "custom",
          "tokenizer": "ngram_tokenizer",
          "filter": ["lowercase"]
        },
        "standard_analyzer": {
          "type": "custom",
          "tokenizer": "standard",
          "filter": ["lowercase"]
        },
        "russian_analyzer": {
          "type": "custom",
          "tokenizer": "standard",
          "filter": ["lowercase", "russian_stop", "russian_stemmer"]
        }
      },
      "filter": {
        "russian_stop": {
          "type": "stop",
          "stopwords": "russian"
        },
        "russian_stemmer": {
          "type": "snowball",
          "language": "Russian"
        }
      }
    }
  },
  "mappings": {
    "properties": {
      "id": { "type": "long" },
      "name": {
        "type": "text",
        "fields": {
          "ngram": {
            "type": "text",
            "analyzer": "ngram_analyzer",
            "search_analyzer": "standard_analyzer"
          },
          "full": {
            "type": "text",
            "analyzer": "russian_analyzer"
          }
        }
      },
      "short_description": {
        "type": "text",
        "fields": {
          "ngram": {
            "type": "text",
            "analyzer": "ngram_analyzer",
            "search_analyzer": "standard_analyzer"
          },
          "full": {
            "type": "text",
            "analyzer": "russian_analyzer"
          }
        }
      },
      "full_description": { "type": "text", "analyzer": "standard_analyzer" },
      "category_id": { "type": "long" },
      "author_id": { "type": "long" },
      "rating": { "type": "byte" },
      "created_at": {
        "type": "date",
        "format": "yyyy-MM-dd HH:mm:ss||yyyy-MM-dd'T'HH:mm:ss.SSSZ"
      }
    }
  }
}

