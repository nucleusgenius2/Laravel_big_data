Имитация большой таблицы с новостями (5 миллионов строк). Различные подходы к оптимизации работы фильтров с данной таблицей (без кеша).

## Используемый стек

- PHP 8.1
- MySQL 8.0
- Laravel 10
- Vue 3 composition api
- Vue router
- Axios

## Установка
- создайте свой файл env
- composer install
- npm install
- php artisan migrate
- php artisan db:seed (создаст 5 миллионов новостей и авторов)
- npm run build

## Возможности фильтрации
- Поиск по словам, применимый к названию новости с любым вхождением аналогичный 'LIKE', '%'.$value.'%', но через полнотекстовый индекс
- поиск по интервалам дат
- поиск по рейтингу
- поиск по автору
- комбинации выше указанных фильтров

## Стратегии оптимизации:

- учет количества новостей в отдельной таблице, чтобы избежать тяжелого запроса count без фильтров
- полнотекстовый индекс для поиска по названиям новостей
- колоночные индексы для всех колонок, по которым может быть поиск
- несколько комбинаций составных индексов с правильным методом сортировки desc

## Индексы для таблицы posts:
- Полнотекстовый: CREATE FULLTEXT INDEX idx_fulltext_name ON posts(name);
- B-TREE: CREATE INDEX idx_rating ON posts (rating);
- B-TREE: CREATE INDEX idx_created_at ON posts (created_at)
- B-TREE: CREATE INDEX idx_rating_created_at_desc ON posts(rating, created_at DESC);
- B-TREE: CREATE INDEX idx_author_id_rating_created_at ON posts (author_id, rating, created_at DESC);
