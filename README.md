# BeFlow

## Rodando com Docker

O projeto foi preparado para subir com `PHP 8.3 + Apache` e `PostgreSQL` via Docker Compose.

### Subir os containers

```bash
docker compose up -d --build
```

Aplicacao:

```text
http://localhost:8080
```

Banco PostgreSQL local:

```text
host=localhost
port=5434
database=beflow_local
user=admin
password=admin
```

### Criar estrutura do banco

```bash
docker compose exec app php migrate.php
```

### Popular dados iniciais

```bash
docker compose exec app php seed.php
```

### Rodar smoke test

```bash
docker compose exec app php tests/smoke.php
```

### Derrubar os containers

```bash
docker compose down
```

Para remover tambem o volume do banco:

```bash
docker compose down -v
```

## Variaveis relevantes

O `docker-compose.yml` ja injeta as variaveis necessarias para o ambiente Docker.

- `APP_BASE_URL`: deixa vazio para servir o sistema na raiz do container.
- `DB_DRIVER=pgsql`
- `DB_HOST=db`
- `DB_PORT=5432`
- `DB_NAME=beflow_local`
- `DB_USER=admin`
- `DB_PASS=admin`

Se quiser rodar em subpasta, defina `APP_BASE_URL` com algo como `/beFlow`.
