USE broker_porez;

-- ==========================================
-- Dopune za uvoz, dedupe i obračun poreza
-- ==========================================
ALTER TABLE transakcije
    MODIFY kurs DECIMAL(20, 10) NULL DEFAULT NULL,           -- NULL = kurs još nije poznat (ne sme tiho biti 1.0)
    ADD kurs_porez DECIMAL(20, 10) NULL DEFAULT NULL,        -- NBS kurs valute poreza po odbitku (dividende)
    ADD jedinstveni_kljuc CHAR(64) NOT NULL,                 -- sha256 za dedupe pri ponovnom uvozu istog CSV-a
    ADD UNIQUE KEY korisnik_kljuc (korisnik_id, jedinstveni_kljuc),
    ADD KEY korisnik_vreme (korisnik_id, vreme_utc);

-- ==========================================
-- Podaci o poreskom obvezniku (Deo 2 obrazaca PPDG-3R i PP OPO)
-- ==========================================
CREATE TABLE poreski_profil (
    korisnik_id INT PRIMARY KEY,
    jmbg CHAR(13) NULL DEFAULT NULL,
    ime_prezime VARCHAR(150) NULL DEFAULT NULL,
    adresa VARCHAR(255) NULL DEFAULT NULL,
    prebivaliste_sifra CHAR(3) NULL DEFAULT NULL,            -- Šifra opštine prebivališta
    telefon VARCHAR(30) NULL DEFAULT NULL,
    email VARCHAR(100) NULL DEFAULT NULL,
    zemlja_rezidentstva VARCHAR(3) NULL DEFAULT 'RS',
    azurirano_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
