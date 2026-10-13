# php-smarty-blog

Тестовое задание: простой блог на чистом PHP 8.1+, Smarty и MySQL, без фреймворков.

Полное условие — в [TASK.md](TASK.md).

## Окружение

Traefik в этот репозиторий не входит. И локально, и на проде используется уже запущенный Traefik: проект подключается к его docker-сети через labels.

### Local

Нужен запущенный Traefik в сети `traefik` (имя можно сменить в `.env.local`).

```bash
cp .env.local.example .env.local
make up
```

Сайт: [http://php-smarty-blog.localhost](http://php-smarty-blog.localhost)

На пустом томе MySQL схема и сид применяются сами. Если том уже был создан раньше:

```bash
make db-seed
```

Код монтируется в контейнеры, пересборка PHP-образа для правок не нужна.

Стили пишутся в `assets/scss`. Скомпилированный CSS лежит в `public/css/app.css`. После правок SCSS (`make css` собирает через одноразовый контейнер Node, в PHP-образ Node не входит):

```bash
make css
```

```bash
make logs
make down
```

### Prod

```bash
cp .env.prod.example .env.prod
# пароли MySQL и TRAEFIK_NETWORK / TRAEFIK_CERT_RESOLVER под вашу инфраструктуру
make up-prod
```

Сайт: [https://cyprus-shop.online](https://cyprus-shop.online)

Код копируется в образ при сборке. HTTPS и сертификат выдаёт внешний Traefik.
