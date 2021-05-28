# Redmine sync

Redmine sync service - migrate issues from one server to another.

## requirements

node <=14 (node-sass)

## install

```bash
composer install
php bin/console doctrine:migrations:migrate
yarn install
yarn build
```

## docker

```bash
docker volume create --name=postgres_redminesync
make up
docker-compose exec php bin/console doctrine:database:create
docker-compose exec php bin/console doctrine:schema:create
docker-compose run encore yarn install
make encore_dev
```
Server is accessible on 127.0.0.1