USE broker_porez;

-- ==========================================
-- Insajderi: korisnici koji vide widget za povratne informacije
-- ==========================================
-- Korisnik ga sam uključuje i isključuje u Moj nalog (PUT /nalog/insajder).
ALTER TABLE korisnici
    ADD is_insajder TINYINT(1) NOT NULL DEFAULT 0;

-- ==========================================
-- Poruke podrške (widget insajdera) i njihovi prilozi
-- ==========================================
-- Poruka se upisuje pre slanja mejla na config('podrska.email'), pa ne propada ako SMTP zakaže.
-- Čuvaju se dok postoji nalog (BrisanjeNalogaService briše redove i fajlove).
CREATE TABLE poruke_podrske (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    naslov VARCHAR(150) NULL,
    poruka TEXT NOT NULL,
    stranica VARCHAR(500) NULL,                               -- URL sa kog je poruka poslata
    user_agent VARCHAR(500) NULL,
    poslato_at TIMESTAMP NULL DEFAULT NULL,                   -- NULL = mejl nije otišao (php artisan podrska:posalji-neposlate)
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY korisnik_kreirano (korisnik_id, kreirano_at),
    KEY neposlate (poslato_at),
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE prilozi_poruka_podrske (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poruka_id INT NOT NULL,
    korisnik_id INT NOT NULL,                                 -- za scoping i brisanje naloga
    naziv VARCHAR(255) NOT NULL,                              -- očišćeno originalno ime, ekstenzija po sadržaju
    putanja VARCHAR(255) NOT NULL,                            -- disk local: podrska/{poruka_id}/{uuid}.{ext}
    mime VARCHAR(100) NOT NULL,
    velicina INT UNSIGNED NOT NULL,                           -- bajtova
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poruka_id) REFERENCES poruke_podrske(id) ON DELETE CASCADE,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
