#!/bin/bash

echo "========================================="
echo "  Setup - Payment API"
echo "========================================="

echo ""
echo "[1/6] Subindo containers Docker..."
docker compose up -d --build

echo ""
echo "[2/6] Aguardando MySQL ficar pronto..."
MAX_TRIES=30
COUNT=0
until docker compose exec -T db mysql -h 127.0.0.1 -u root -proot -e "SELECT 1" >/dev/null 2>&1; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "  ERRO: MySQL não respondeu após ${MAX_TRIES} tentativas."
        exit 1
    fi
    sleep 3
    echo "  Aguardando... ($COUNT/$MAX_TRIES)"
done
echo "  MySQL pronto!"

echo ""
echo "[3/6] Instalando dependências..."
docker compose exec app composer install --no-interaction

echo ""
echo "[4/6] Gerando chave da aplicação..."
docker compose exec app php artisan key:generate

echo ""
echo "[5/6] Rodando migrations e seeders..."
docker compose exec app php artisan migrate:fresh --seed

echo ""
echo "[6/6] Rodando testes..."
docker compose exec app php artisan test

echo ""
echo "========================================="
echo "  Setup concluído!"
echo ""
echo "  API:      http://localhost:8989/api"
echo "  Gateway1: http://localhost:3001"
echo "  Gateway2: http://localhost:3002"
echo "  MySQL:    localhost:3388"
echo "========================================="
