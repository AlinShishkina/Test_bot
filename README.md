# Wildberries API Data Collector

Laravel-сервис для сбора данных с тестового API Wildberries и сохранения их в MySQL базу данных.

## Описание

Сервис собирает данные с API по следующим эндпоинтам:
- **Продажи (Sales)** - `/api/sales`
- **Заказы (Orders)** - `/api/orders`
- **Склады (Stocks)** - `/api/stocks`
- **Доходы (Incomes)** - `/api/incomes`

Все данные сохраняются в структурированные MySQL таблицы с поддержкой пагинации.

## Технологии

- PHP 8.2+
- Laravel 12
- MySQL 8.0+
- Docker / Docker Compose

## Быстрый старт с Docker

### 1. Клонирование репозитория

```bash
git clone https://github.com/AlinShishkina/Test_bot.git
cd Test_bot
```

### 2. Настройка окружения

```bash
cp .env.example .env
```

Отредактируйте `.env` файл для Docker:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=wildberries
DB_USERNAME=wb_user
DB_PASSWORD=wb_password
```

### 3. Запуск контейнеров

```bash
docker-compose up -d
```

### 4. Установка зависимостей и миграции

```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### 5. Сбор данных

```bash
docker-compose exec app php artisan wb:collect --all
```

## Локальная установка (без Docker)

### 1. Требования

- PHP 8.2+
- Composer
- MySQL 5.7+ или MariaDB 10.3+
- PHP расширения: pdo_mysql, mbstring, xml, curl, zip

### 2. Установка

```bash
git clone https://github.com/AlinShishkina/Test_bot.git
cd Test_bot
composer install
cp .env.example .env
php artisan key:generate
```

### 3. Настройка базы данных

Отредактируйте файл `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=your_mysql_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Миграции

```bash
php artisan migrate
```

### 5. Запуск

```bash
php artisan serve
```

## Конфигурация API

API настраивается через переменные окружения в `.env`:

```env
# Wildberries API configuration
WILDBERRIES_API_URL=http://109.73.206.144:6969
WILDBERRIES_API_KEY=E6kUTYrYwZq2tN4QEtyzsbEBk3ie
WILDBERRIES_API_LIMIT=500
```

## CLI Команды

### Сбор всех данных

```bash
php artisan wb:collect --all
```

### Сбор конкретной сущности

```bash
# Продажи
php artisan wb:collect --entity=sales

# Заказы
php artisan wb:collect --entity=orders

# Склады (только текущий день)
php artisan wb:collect --entity=stocks

# Доходы
php artisan wb:collect --entity=incomes
```

### С указанием периода

```bash
php artisan wb:collect --all --date-from=2024-01-01 --date-to=2024-12-31
```

### С подробным выводом

```bash
php artisan wb:collect --all --verbose
```

## Структура базы данных

### Таблицы сущностей

| Таблица | Описание |
|---------|----------|
| `sales` | Продажи |
| `orders` | Заказы |
| `stocks` | Остатки на складах |
| `incomes` | Доходы |

### Схема таблицы `sales`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| g_number | varchar(50) | Номер заказа |
| date | date | Дата продажи |
| last_change_date | date | Дата последнего изменения |
| supplier_article | varchar(100) | Артикул поставщика |
| tech_size | varchar(100) | Технический размер |
| barcode | bigint | Штрихкод |
| total_price | decimal(15,2) | Общая цена |
| discount_percent | int | Процент скидки |
| is_supply | boolean | Поставка |
| is_realization | boolean | Реализация |
| promo_code_discount | decimal(15,2) | Скидка по промокоду |
| warehouse_name | varchar(255) | Название склада |
| country_name | varchar(100) | Страна |
| oblast_okrug_name | varchar(255) | Область/округ |
| region_name | varchar(255) | Регион |
| income_id | bigint | ID дохода |
| sale_id | varchar(50) | ID продажи (уникальный) |
| odid | varchar(50) | ODID |
| spp | decimal(10,2) | СПП |
| for_pay | decimal(15,2) | К выплате |
| finished_price | decimal(15,2) | Финальная цена |
| price_with_disc | decimal(15,2) | Цена со скидкой |
| nm_id | bigint | Номенклатура ID |
| subject | varchar(255) | Предмет |
| category | varchar(255) | Категория |
| brand | varchar(255) | Бренд |
| is_storno | boolean | Сторно |

### Схема таблицы `orders`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| g_number | varchar(50) | Номер заказа |
| date | datetime | Дата и время заказа |
| last_change_date | date | Дата последнего изменения |
| supplier_article | varchar(100) | Артикул поставщика |
| tech_size | varchar(100) | Технический размер |
| barcode | bigint | Штрихкод |
| total_price | decimal(15,2) | Общая цена |
| discount_percent | int | Процент скидки |
| warehouse_name | varchar(255) | Название склада |
| oblast | varchar(255) | Область |
| income_id | bigint | ID дохода |
| odid | varchar(50) | ODID |
| nm_id | bigint | Номенклатура ID |
| subject | varchar(255) | Предмет |
| category | varchar(255) | Категория |
| brand | varchar(255) | Бренд |
| is_cancel | boolean | Отменён |
| cancel_dt | datetime | Дата отмены |

### Схема таблицы `stocks`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| date | date | Дата |
| last_change_date | date | Дата последнего изменения |
| supplier_article | varchar(100) | Артикул поставщика |
| tech_size | varchar(100) | Технический размер |
| barcode | bigint | Штрихкод |
| quantity | int | Количество |
| is_supply | boolean | Поставка |
| is_realization | boolean | Реализация |
| quantity_full | int | Полное количество |
| warehouse_name | varchar(255) | Название склада |
| in_way_to_client | int | В пути к клиенту |
| in_way_from_client | int | В пути от клиента |
| nm_id | bigint | Номенклатура ID |
| subject | varchar(255) | Предмет |
| category | varchar(255) | Категория |
| brand | varchar(255) | Бренд |
| sc_code | bigint | SC код |
| price | decimal(15,2) | Цена |
| discount | int | Скидка |

### Схема таблицы `incomes`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint | Первичный ключ |
| income_id | bigint | ID дохода (из API) |
| number | varchar(100) | Номер |
| date | date | Дата |
| last_change_date | date | Дата последнего изменения |
| supplier_article | varchar(100) | Артикул поставщика |
| tech_size | varchar(100) | Технический размер |
| barcode | bigint | Штрихкод |
| quantity | int | Количество |
| total_price | decimal(15,2) | Общая цена |
| date_close | date | Дата закрытия |
| warehouse_name | varchar(255) | Название склада |
| nm_id | bigint | Номенклатура ID |

## API Endpoints

### API для сбора данных Wildberries

Дополнительно к CLI командам, доступны API эндпоинты для интеграции:

```
POST /api/collect/all              - Сбор со всех эндпоинтов
POST /api/collect/endpoint/{id}    - Сбор с конкретного эндпоинта
GET  /api/responses                - Список сохранённых ответов
GET  /api/stats                    - Статистика
```

### Управление API-эндпоинтами (legacy)

```
GET    /api/endpoints              - Список всех эндпоинтов
POST   /api/endpoints              - Создание эндпоинта
GET    /api/endpoints/{id}         - Получение эндпоинта
PUT    /api/endpoints/{id}         - Обновление эндпоинта
DELETE /api/endpoints/{id}         - Удаление эндпоинта
```

## Бесплатные MySQL хостинги

Для развёртывания базы данных рекомендуются:

### PlanetScale (Рекомендуется)
- Сайт: https://planetscale.com
- Бесплатный план: 5GB хранилища
- Особенности: MySQL-совместимый, serverless

### Railway
- Сайт: https://railway.app
- Бесплатный план: $5 кредитов в месяц
- Особенности: простая настройка, MySQL 8.0

### db4free.net
- Сайт: https://www.db4free.net
- Бесплатный план: тестовая база данных
- Особенности: для разработки и тестирования

### FreeSQLDatabase
- Сайт: https://www.freesqldatabase.com
- Бесплатный план: 5MB
- Особенности: для тестирования

## Структура проекта

```
app/
├── Console/Commands/
│   ├── CollectApiData.php           # Команда сбора данных (legacy)
│   ├── CollectWildberriesData.php   # Команда сбора данных WB
│   └── ImportPostmanCollection.php  # Импорт Postman коллекций
├── Http/Controllers/Api/
│   ├── ApiEndpointController.php    # CRUD для эндпоинтов
│   ├── DataCollectorController.php  # Сбор данных
│   └── PostmanCollectionController.php
├── Models/
│   ├── Sale.php                     # Модель продаж
│   ├── Order.php                    # Модель заказов
│   ├── Stock.php                    # Модель остатков
│   ├── Income.php                   # Модель доходов
│   └── ...
└── Services/
    ├── WildberriesApiService.php    # Сервис WB API
    ├── ApiCollectorService.php      # Generic API collector
    └── ...

database/migrations/
├── 2025_12_17_090000_create_sales_table.php
├── 2025_12_17_090001_create_orders_table.php
├── 2025_12_17_090002_create_stocks_table.php
└── 2025_12_17_090003_create_incomes_table.php

docker/
└── nginx/
    └── nginx.conf

docker-compose.yml
Dockerfile
```

## API Источники

### GitHub репозиторий
- https://github.com/cy322666/wb-api/blob/master/README.md

### Postman коллекция
- https://www.postman.com/cy322666/workspace/app-api-test/overview

### Параметры API

- **Хост**: `109.73.206.144:6969`
- **Ключ авторизации**: `E6kUTYrYwZq2tN4QEtyzsbEBk3ie`
- **Формат даты**: `Y-m-d`
- **Формат даты и времени**: `Y-m-d H:i:s`
- **Лимит по умолчанию**: 500 записей
- **Пагинация**: параметр `page`

## Тестирование

```bash
php artisan test
```

## Лицензия

MIT License
