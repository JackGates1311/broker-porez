USE broker_porez;

-- 1. Osnovne tabele (bez stranih ključeva prema drugima)

CREATE TABLE `korisnici` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `korisnicko_ime` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_general_ci',
    `email` VARCHAR(100) NOT NULL COLLATE 'utf8mb4_general_ci',
    `lozinka_hash` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    `email_verifikovan_at` TIMESTAMP NULL DEFAULT NULL,
    `remember_token` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `korisnicko_ime` (`korisnicko_ime`) USING BTREE,
    UNIQUE INDEX `email` (`email`) USING BTREE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=13;

CREATE TABLE `imovina` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `isin` VARCHAR(12) NOT NULL COLLATE 'utf8mb4_general_ci',
    `simbol` VARCHAR(30) NOT NULL COLLATE 'utf8mb4_general_ci',
    `naziv` VARCHAR(150) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `isin` (`isin`) USING BTREE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=15;

CREATE TABLE `kursevi` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `datum` DATE NOT NULL,
    `valuta` VARCHAR(5) NOT NULL COLLATE 'utf8mb4_general_ci',
    `srednji_kurs` DECIMAL(20,10) NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `datum_valuta` (`datum`, `valuta`) USING BTREE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=591;

CREATE TABLE `cache` (
    `key` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `value` MEDIUMTEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `expiration` BIGINT(20) NOT NULL,
    PRIMARY KEY (`key`) USING BTREE,
    INDEX `cache_expiration_index` (`expiration`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE `cache_locks` (
    `key` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `owner` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `expiration` BIGINT(20) NOT NULL,
    PRIMARY KEY (`key`) USING BTREE,
    INDEX `cache_locks_expiration_index` (`expiration`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE `sessions` (
    `id` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `user_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
    `user_agent` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
    `payload` LONGTEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
    `last_activity` INT(11) NOT NULL,
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `sessions_user_id_index` (`user_id`) USING BTREE,
    INDEX `sessions_last_activity_index` (`last_activity`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB;


-- 2. Tabele sa zavisnostima (stranim ključevima)

CREATE TABLE `transakcije` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `korisnik_id` INT(11) NOT NULL,
    `broker_transakcija_id` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `imovina_id` INT(11) NULL DEFAULT NULL,
    `tip_akcije` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_general_ci',
    `tip` VARCHAR(20) NOT NULL COLLATE 'utf8mb4_general_ci',
    `izvor` VARCHAR(20) NOT NULL DEFAULT 'TRADING212' COLLATE 'utf8mb4_general_ci',
    `vreme_utc` DATETIME NOT NULL,
    `kolicina` DECIMAL(28,10) NULL DEFAULT '0.0000000000',
    `cena_po_akciji` DECIMAL(28,10) NULL DEFAULT '0.0000000000',
    `valuta_cene` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `kurs` DECIMAL(20,10) NULL DEFAULT NULL,
    `ukupno` DECIMAL(28,10) NOT NULL,
    `valuta_ukupno` VARCHAR(5) NOT NULL COLLATE 'utf8mb4_general_ci',
    `porez_po_odbitku` DECIMAL(28,10) NULL DEFAULT '0.0000000000',
    `valuta_poreza` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `provizija` DECIMAL(28,10) NULL DEFAULT '0.0000000000',
    `valuta_provizije` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `napomena` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    `kurs_porez` DECIMAL(20,10) NULL DEFAULT NULL,
    `jedinstveni_kljuc` CHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `korisnik_kljuc` (`korisnik_id`, `jedinstveni_kljuc`) USING BTREE,
    INDEX `imovina_id` (`imovina_id`) USING BTREE,
    INDEX `korisnik_vreme` (`korisnik_id`, `vreme_utc`) USING BTREE,
    INDEX `korisnik_tip` (`korisnik_id`, `tip`) USING BTREE,
    CONSTRAINT `fk_transakcije_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_transakcije_imovina` FOREIGN KEY (`imovina_id`) REFERENCES `imovina` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=744;

CREATE TABLE `alokacije_lotova_prodaje` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `korisnik_id` INT(11) NOT NULL,
    `transakcija_prodaje_id` BIGINT(20) NOT NULL,
    `poreski_lot_id` BIGINT(20) NOT NULL,
    `iskoriscena_kolicina` DECIMAL(28,10) NOT NULL,
    `nabavna_vrednost_rsd` DECIMAL(28,10) NOT NULL,
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `korisnik_id` (`korisnik_id`) USING BTREE,
    INDEX `transakcija_prodaje_id` (`transakcija_prodaje_id`) USING BTREE,
    INDEX `poreski_lot_id` (`poreski_lot_id`) USING BTREE,
    CONSTRAINT `fk_alokacije_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_alokacije_transakcija` FOREIGN KEY (`transakcija_prodaje_id`) REFERENCES `transakcije` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_alokacije_lot` FOREIGN KEY (`poreski_lot_id`) REFERENCES `poreski_lotovi` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=232;

CREATE TABLE `poreski_lotovi` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `korisnik_id` INT(11) NOT NULL,
    `transakcija_kupovine_id` BIGINT(20) NOT NULL,
    `imovina_id` INT(11) NOT NULL,
    `pocetna_kolicina` DECIMAL(28,10) NOT NULL,
    `preostala_kolicina` DECIMAL(28,10) NOT NULL,
    `nabavna_cena_po_jedinici` DECIMAL(28,10) NOT NULL,
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `korisnik_id` (`korisnik_id`) USING BTREE,
    INDEX `transakcija_kupovine_id` (`transakcija_kupovine_id`) USING BTREE,
    INDEX `imovina_id` (`imovina_id`) USING BTREE,
    CONSTRAINT `fk_lotovi_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_lotovi_transakcija` FOREIGN KEY (`transakcija_kupovine_id`) REFERENCES `transakcije` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_lotovi_imovina` FOREIGN KEY (`imovina_id`) REFERENCES `imovina` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=581;

CREATE TABLE `poreski_obaveznik` (
    `korisnik_id` INT(11) NOT NULL,
    `tip_obaveznika` TINYINT(4) NULL DEFAULT NULL,
    `pib` VARCHAR(13) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `ime_prezime` VARCHAR(150) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `adresa` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `prebivaliste` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `opstina_sifra` CHAR(3) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `telefon` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `email` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `jmbg_podnosioca` CHAR(13) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `zemlja_rezidentstva` VARCHAR(3) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `pib_punomocnika` VARCHAR(13) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `tekuci_racun` CHAR(18) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `broj_pasosa` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
    `azurirano_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
    PRIMARY KEY (`korisnik_id`) USING BTREE,
    CONSTRAINT `fk_obaveznik_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB;

CREATE TABLE `sabloni_uvoza` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `korisnik_id` INT(11) NOT NULL,
    `naziv` VARCHAR(100) NOT NULL COLLATE 'utf8mb4_general_ci',
    `podesavanja` LONGTEXT NOT NULL COLLATE 'utf8mb4_bin',
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `korisnik_naziv` (`korisnik_id`, `naziv`) USING BTREE,
    CONSTRAINT `fk_sabloni_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `podesavanja` CHECK (json_valid(`podesavanja`))
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=3;

CREATE TABLE `tokeni_reset_lozinke` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `korisnik_id` INT(11) NOT NULL,
    `token_hash` CHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',
    `istice_at` DATETIME NOT NULL,
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE INDEX `token_hash` (`token_hash`) USING BTREE,
    INDEX `korisnik_id` (`korisnik_id`) USING BTREE,
    CONSTRAINT `fk_tokeni_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=4;

CREATE TABLE `verifikacioni_kodovi` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `korisnik_id` INT(11) NOT NULL,
    `kod_hash` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
    `broj_pokusaja` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
    `istice_at` DATETIME NOT NULL,
    `kreirano_at` TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `korisnik_id` (`korisnik_id`) USING BTREE,
    CONSTRAINT `fk_verifikacioni_korisnik` FOREIGN KEY (`korisnik_id`) REFERENCES `korisnici` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=15;

-- ==========================================
-- Prijava preko Google-a: stabilni Google ID ("sub") povezanog naloga.
-- Lozinka i email ostaju kao i do sada, pa se Google korisnik uvek može
-- prijaviti i lozinkom (postavljenom preko "Zaboravljena lozinka?").
-- ==========================================
ALTER TABLE korisnici
    ADD google_id VARCHAR(255) NULL DEFAULT NULL,
    ADD UNIQUE INDEX google_id (google_id);
