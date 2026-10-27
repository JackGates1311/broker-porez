<?php

/**
 * Pravi test bazu iz pravih sql/ fajlova, redom iz sql/redosled.txt, pre svakog pokretanja testova
 * (Tests\TestCase, pri prvom testu koji koristi bazu). Testovi tako rade nad istom šemom kao produkcija.
 *
 * Odbija da radi van APP_ENV=testing, nad bazom čije se ime ne završava na _test i na serveru koji ima pravu bazu
 * broker_porez (lokalni server, produkcija preko tunela), jer prvo briše test bazu.
 */

namespace Tests\Baza;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use RuntimeException;

final class PripremaBaze
{
    /** Ime baze u USE naredbama sql/ fajlova; zamenjuje se imenom test baze. */
    private const PRODUKCIJSKA_BAZA = 'broker_porez';

    private static bool $pripremljena = false;

    /**
     * Jednom po pokretanju PHPUnit-a, pre prvog testa koji koristi bazu.
     */
    public static function jednom(string $koren): void
    {
        if (! self::$pripremljena) {
            self::pripremi($koren);
            self::$pripremljena = true;
        }
    }

    /**
     * @return list<string> putanje fajlova koji se primenjuju, relativno od korena projekta
     */
    public static function redosled(string $koren): array
    {
        $fajlovi = [];

        foreach (file($koren.'/sql/redosled.txt', FILE_IGNORE_NEW_LINES) ?: [] as $red) {
            $red = trim($red);

            if ($red !== '' && ! str_starts_with($red, '#') && ! str_starts_with($red, '!')) {
                $fajlovi[] = $red;
            }
        }

        return $fajlovi;
    }

    /**
     * @return list<string> fajlovi označeni sa ! (postoje, a ne primenjuju se)
     */
    public static function izostavljeni(string $koren): array
    {
        $fajlovi = [];

        foreach (file($koren.'/sql/redosled.txt', FILE_IGNORE_NEW_LINES) ?: [] as $red) {
            if (str_starts_with(trim($red), '!')) {
                $fajlovi[] = ltrim(trim($red), '!');
            }
        }

        return $fajlovi;
    }

    public static function pripremi(string $koren): void
    {
        $baza = (string) getenv('DB_DATABASE');

        if (getenv('APP_ENV') !== 'testing' || ! str_ends_with($baza, '_test') || ! preg_match('/^\w+$/', $baza)) {
            throw new RuntimeException("Test baza mora da se zove *_test i da radi uz APP_ENV=testing (sada: „{$baza}”).");
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $veza = new mysqli(
                (string) getenv('DB_HOST'),
                (string) getenv('DB_USERNAME'),
                (string) getenv('DB_PASSWORD'),
                '',
                (int) getenv('DB_PORT'),
            );
        } catch (mysqli_sql_exception $greska) {
            throw new RuntimeException(
                'Test baza (MariaDB) nije dostupna na '.getenv('DB_HOST').':'.getenv('DB_PORT').': '.$greska->getMessage()
                .PHP_EOL.'Pokrenite je sa: docker/test/testovi.sh baza',
            );
        }

        $veza->set_charset('utf8mb4');

        // Test server je prazan kontejner; server sa pravom bazom (lokalni, produkcija preko SSH tunela) se ne dira.
        if ($veza->query("SHOW DATABASES LIKE '".self::PRODUKCIJSKA_BAZA."'")->num_rows > 0) {
            $veza->close();

            throw new RuntimeException(
                'Server '.getenv('DB_HOST').':'.getenv('DB_PORT').' ima bazu '.self::PRODUKCIJSKA_BAZA
                .', pa nije test server. Testovi rade samo nad kontejnerom iz docker/test/testovi.sh.',
            );
        }

        $veza->query("DROP DATABASE IF EXISTS `{$baza}`");
        $veza->query("CREATE DATABASE `{$baza}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $veza->select_db($baza);
        // Kao config/database.php: vremena u UTC-u.
        $veza->query("SET time_zone = '+00:00'");

        foreach (self::redosled($koren) as $fajl) {
            $sql = file_get_contents($koren.'/'.$fajl);

            if ($sql === false) {
                throw new RuntimeException("Nema fajla {$fajl} iz sql/redosled.txt.");
            }

            $sql = preg_replace('/^\s*USE\s+`?'.self::PRODUKCIJSKA_BAZA.'`?\s*;/mi', "USE `{$baza}`;", $sql);

            try {
                $veza->multi_query($sql);

                // Svaki rezultat mora da se pročita da bi se stiglo do greške u kasnijoj naredbi.
                do {
                    $rezultat = $veza->store_result();

                    if ($rezultat instanceof mysqli_result) {
                        $rezultat->free();
                    }
                } while ($veza->more_results() && $veza->next_result());
            } catch (mysqli_sql_exception $greska) {
                throw new RuntimeException("Greška u {$fajl}: {$greska->getMessage()}", previous: $greska);
            }
        }

        $veza->close();
    }
}
