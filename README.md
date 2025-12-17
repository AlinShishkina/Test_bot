# API Data Collector Service

Laravel-сервис для сбора данных с API-эндпоинтов из GitHub-репозиториев и Postman-коллекций с сохранением в MySQL базу данных.

## Функциональность

- Импорт API-эндпоинтов из Postman-коллекций (JSON)
- Импорт API-эндпоинтов из GitHub-репозиториев (OpenAPI/Swagger спецификации)
- Автоматический сбор данных со всех активных эндпоинтов
- Сохранение ответов API в базу данных
- REST API для управления эндпоинтами и коллекциями
- CLI-команды для автоматизации

## Требования

- PHP 8.2+
- Composer
- MySQL 5.7+ или MariaDB 10.3+
- PHP расширения: mbstring, xml, curl, zip, mysql

## Установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/AlinShishkina/Test_bot.git
cd Test_bot
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Настройка окружения

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Настройка базы данных

Отредактируйте файл `.env` и укажите данные для подключения к MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=your_mysql_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Запуск миграций

```bash
php artisan migrate
```

### 6. Запуск сервера

```bash
php artisan serve
```

## Бесплатные хостинги MySQL

Для разработки и тестирования рекомендуются следующие бесплатные сервисы:

### 1. PlanetScale (Рекомендуется)
- Сайт: https://planetscale.com
- Бесплатный план: 5GB хранилища, 1 миллиард строк
- Особенности: MySQL-совместимый, serverless

### 2. Railway
- Сайт: https://railway.app
- Бесплатный план: $5 кредитов в месяц
- Особенности: простая настройка, MySQL 8.0

### 3. Aiven (Trial)
- Сайт: https://aiven.io
- Пробный период: 30 дней бесплатно
- Особенности: управляемый MySQL

### 4. FreeSQLDatabase
- Сайт: https://www.freesqldatabase.com
- Бесплатный план: 5MB
- Особенности: для тестирования

## Структура базы данных

### Таблицы

| Таблица | Описание |
|---------|----------|
| `api_endpoints` | API-эндпоинты для сбора данных |
| `api_responses` | Сохраненные ответы API |
| `postman_collections` | Импортированные Postman-коллекции |
| `collection_items` | Элементы коллекций (папки и запросы) |

### Схема таблицы `api_endpoints`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| name | varchar(255) | Название эндпоинта |
| method | varchar(10) | HTTP метод (GET, POST, PUT, PATCH, DELETE) |
| url | varchar(255) | URL эндпоинта |
| headers | json | HTTP заголовки |
| query_params | json | Query параметры |
| body | json | Тело запроса |
| source | varchar(255) | Источник (manual, github, postman) |
| source_reference | varchar(255) | Ссылка на источник |
| description | text | Описание |
| is_active | boolean | Активен ли эндпоинт |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### Схема таблицы `api_responses`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| api_endpoint_id | bigint | FK на api_endpoints |
| status_code | int | HTTP статус код |
| response_headers | json | Заголовки ответа |
| response_body | longtext | Тело ответа |
| response_time | float | Время ответа (секунды) |
| collected_at | timestamp | Время сбора данных |
| is_successful | boolean | Успешен ли запрос |
| error_message | text | Сообщение об ошибке |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### Схема таблицы `postman_collections`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| name | varchar(255) | Название коллекции |
| postman_id | varchar(255) | ID из Postman |
| description | text | Описание |
| schema_version | varchar(255) | Версия схемы |
| variables | json | Переменные коллекции |
| auth | json | Аутентификация |
| raw_data | json | Исходные данные JSON |
| source_url | varchar(255) | URL источника |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### Схема таблицы `collection_items`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| postman_collection_id | bigint | FK на postman_collections |
| api_endpoint_id | bigint | FK на api_endpoints |
| parent_id | bigint | FK на родительский элемент |
| name | varchar(255) | Название элемента |
| type | enum | Тип (folder, request) |
| order | int | Порядок сортировки |
| raw_data | json | Исходные данные |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

## API Endpoints

### Управление API-эндпоинтами

```
GET    /api/endpoints              - Список всех эндпоинтов
POST   /api/endpoints              - Создание эндпоинта
GET    /api/endpoints/{id}         - Получение эндпоинта
PUT    /api/endpoints/{id}         - Обновление эндпоинта
DELETE /api/endpoints/{id}         - Удаление эндпоинта
```

### Управление Postman-коллекциями

```
GET    /api/collections            - Список всех коллекций
POST   /api/collections            - Импорт коллекции (json или url)
GET    /api/collections/{id}       - Получение коллекции
PUT    /api/collections/{id}       - Обновление коллекции
DELETE /api/collections/{id}       - Удаление коллекции
```

### Сбор данных

```
POST   /api/collect/all            - Сбор со всех активных эндпоинтов
POST   /api/collect/endpoint/{id}  - Сбор с конкретного эндпоинта
POST   /api/collect/github         - Импорт из GitHub репозитория
GET    /api/responses              - Список ответов
GET    /api/stats                  - Статистика
```

## CLI Команды

### Импорт Postman-коллекции

```bash
# Из файла
php artisan postman:import --file=/path/to/collection.json

# Из URL
php artisan postman:import --url=https://example.com/collection.json

# С подробным выводом
php artisan postman:import --file=/path/to/collection.json --verbose
```

### Сбор данных с API

```bash
# Сбор со всех активных эндпоинтов
php artisan api:collect --all

# Сбор с конкретного эндпоинта
php artisan api:collect --endpoint=1

# С подробным выводом
php artisan api:collect --all --verbose
```

## Примеры использования

### Создание эндпоинта через API

```bash
curl -X POST http://localhost:8000/api/endpoints \
  -H "Content-Type: application/json" \
  -d '{
    "name": "JSONPlaceholder Posts",
    "method": "GET",
    "url": "https://jsonplaceholder.typicode.com/posts",
    "description": "Get all posts from JSONPlaceholder API"
  }'
```

### Импорт Postman-коллекции через API

```bash
curl -X POST http://localhost:8000/api/collections \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://www.getpostman.com/collections/your-collection-id"
  }'
```

### Сбор данных через API

```bash
# Сбор со всех активных эндпоинтов
curl -X POST http://localhost:8000/api/collect/all

# Получение статистики
curl http://localhost:8000/api/stats
```

### Импорт из GitHub через API

```bash
curl -X POST http://localhost:8000/api/collect/github \
  -H "Content-Type: application/json" \
  -d '{
    "owner": "owner-name",
    "repo": "repo-name",
    "file_path": "openapi.json"
  }'
```

## Структура проекта

```
app/
├── Console/Commands/
│   ├── CollectApiData.php        # Команда сбора данных
│   └── ImportPostmanCollection.php # Команда импорта коллекций
├── Http/Controllers/Api/
│   ├── ApiEndpointController.php  # CRUD для эндпоинтов
│   ├── DataCollectorController.php # Сбор данных
│   └── PostmanCollectionController.php # CRUD для коллекций
├── Models/
│   ├── ApiEndpoint.php           # Модель эндпоинта
│   ├── ApiResponse.php           # Модель ответа
│   ├── CollectionItem.php        # Модель элемента коллекции
│   └── PostmanCollection.php     # Модель коллекции
└── Services/
    ├── ApiCollectorService.php   # Сервис сбора данных
    ├── GitHubApiService.php      # Сервис работы с GitHub
    └── PostmanCollectionService.php # Сервис Postman-коллекций
```

## Тестирование

```bash
php artisan test
```

## Лицензия

MIT License
