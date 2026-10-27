#!/usr/bin/env bash
# Testovi nad MariaDB-om iste verzije kao produkcija (10.11), odvojeno od lokalne baze broker_porez.
# Koristi samo docker (bez compose-a).
#
#   docker/test/testovi.sh                          # podigne bazu i pokrene ceo paket u kontejneru
#   docker/test/testovi.sh --filter=PodrskaTest     # argumenti idu u php artisan test
#   docker/test/testovi.sh baza                     # samo baza na 127.0.0.1:13306, pa: php artisan test (sa hosta)
#   docker/test/testovi.sh stop                     # ugasi bazu
set -euo pipefail

KOREN="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
MREZA=brokerporez-test
BAZA=brokerporez-test-baza
IMAGE=brokerporez-test

pokreni_bazu() {
    docker network inspect "$MREZA" >/dev/null 2>&1 || docker network create "$MREZA" >/dev/null

    if [ -z "$(docker ps -q -f name="^${BAZA}$")" ]; then
        docker rm -f "$BAZA" >/dev/null 2>&1 || true
        # Podaci u memoriji: šema se ionako pravi iznova pri svakom pokretanju testova.
        docker run -d --name "$BAZA" --network "$MREZA" --network-alias baza \
            -e MARIADB_ROOT_PASSWORD=test -p 127.0.0.1:13306:3306 --tmpfs /var/lib/mysql \
            mariadb:10.11 >/dev/null
    fi

    for _ in $(seq 1 60); do
        docker exec "$BAZA" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1 && return 0
        sleep 1
    done

    echo "MariaDB se nije podigla za 60 s (docker logs $BAZA)." >&2
    exit 1
}

case "${1:-}" in
    stop)
        docker rm -f "$BAZA" >/dev/null 2>&1 || true
        docker network rm "$MREZA" >/dev/null 2>&1 || true
        ;;
    baza)
        pokreni_bazu
        echo "Test baza radi na 127.0.0.1:13306 (root/test). Testovi sa hosta: php artisan test"
        ;;
    *)
        pokreni_bazu
        docker build -q -t "$IMAGE" "$KOREN/docker/test" >/dev/null
        docker run --rm --network "$MREZA" -e DB_HOST=baza -e DB_PORT=3306 \
            -v "$KOREN":/app "$IMAGE" php artisan test "$@"
        ;;
esac
