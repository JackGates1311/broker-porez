USE broker_porez;

-- ==========================================
-- Sesija baze u UTC-u (jednokratna ispravka postojećih vremena)
-- ==========================================
-- Aplikacija radi u UTC-u (app.timezone), a od sada i konekcija baze: config/database.php postavlja
-- time_zone = '+00:00'. Ranije je sesija bila u zoni servera (@@global.time_zone, lokalno SYSTEM = CET/CEST),
-- pa je MariaDB UTC vreme koje šalje Laravel tumačio kao lokalno: posle prelaska na UTC ta vremena bi se
-- čitala 1 h (zimsko) ili 2 h (letnje vreme) ranije. Ovaj fajl ih vraća na ono što je aplikacija upisala.
--
-- Kolone koje puni DEFAULT CURRENT_TIMESTAMP (transakcije, poreski_lotovi, alokacije_lotova_prodaje:
-- kreirano_at bez vrednosti iz aplikacije) već su tačne i ne diraju se. DATETIME kolone (transakcije.vreme_utc,
-- istice_at) MariaDB ne pretvara po zoni, pa se ni one ne diraju.
--
-- Pokrenuti TAČNO JEDNOM, odmah posle deploy-a izmene config/database.php. Drugo pokretanje se zaustavlja na
-- CREATE TABLE ispod (tabela već postoji). Ako je server već u UTC-u (@@global.time_zone = '+00:00'), izmene
-- su bez efekta.
--
-- Ubuduće: ručni SQL koji upisuje vreme počinje sa SET time_zone = '+00:00';

CREATE TABLE vremenska_zona_utc (
    primenjeno_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    zona_pre VARCHAR(64) NOT NULL                             -- @@global.time_zone kojom je ispravljeno
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET @zona = @@global.time_zone;
SET time_zone = '+00:00';

START TRANSACTION;

INSERT INTO vremenska_zona_utc (primenjeno_at, zona_pre) VALUES (UTC_TIMESTAMP(), @zona);

-- Upisuje aplikacija (Eloquent kreirano_at/azurirano_at, now()).
UPDATE korisnici SET
    kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona),
    email_verifikovan_at = CONVERT_TZ(email_verifikovan_at, '+00:00', @zona),
    uslovi_prihvaceni_at = CONVERT_TZ(uslovi_prihvaceni_at, '+00:00', @zona),
    zadovoljstvo_od = CONVERT_TZ(zadovoljstvo_od, '+00:00', @zona);

UPDATE imovina SET kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona);
UPDATE sabloni_uvoza SET kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona);
UPDATE poreski_obaveznik SET azurirano_at = CONVERT_TZ(azurirano_at, '+00:00', @zona);
UPDATE obrisani_nalozi SET obrisano_at = CONVERT_TZ(obrisano_at, '+00:00', @zona);
UPDATE verifikacioni_kodovi SET kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona);
UPDATE tokeni_reset_lozinke SET kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona);
UPDATE poruke_podrske SET
    kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona),
    poslato_at = CONVERT_TZ(poslato_at, '+00:00', @zona);
UPDATE prilozi_poruka_podrske SET kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona);
UPDATE ocene_zadovoljstva SET kreirano_at = CONVERT_TZ(kreirano_at, '+00:00', @zona);

COMMIT;

-- Provera: vremena treba da budu ona koja je aplikacija upisala (UTC).
SELECT id, korisnicko_ime, kreirano_at, email_verifikovan_at, zadovoljstvo_od FROM korisnici ORDER BY id;
