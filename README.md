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

Код монтируется в контейнеры, пересборка PHP-образа для правок не нужна.

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
