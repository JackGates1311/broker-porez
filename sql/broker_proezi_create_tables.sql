USE broker_porez;

-- ==========================================
-- 1. KORISNICI (Multi-user izolacija)
-- ==========================================
CREATE TABLE korisnici (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnicko_ime VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    lozinka_hash VARCHAR(255) NOT NULL,
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 2. ŠIFARNIK IMOVINE (Globalno sidro po ISIN-u)
-- ==========================================
CREATE TABLE imovina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isin VARCHAR(12) NOT NULL UNIQUE,          -- Jedinstveni svetski identifikator (npr. US4592001014)
    simbol VARCHAR(30) NOT NULL,               -- Poslednji poznati Ticker iz CSV-a
    naziv VARCHAR(150) DEFAULT NULL,           -- Naziv kompanije
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 3. ISTORIJA KURSEVA (Zvanični srednji kurs NBS)
-- ==========================================
CREATE TABLE kursevi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum DATE NOT NULL,
    valuta VARCHAR(5) NOT NULL,                -- Oznaka valute (npr. USD, EUR)
    srednji_kurs DECIMAL(20, 10) NOT NULL,     -- Zvanični srednji kurs NBS
    UNIQUE KEY datum_valuta (datum, valuta)    -- Sprečava duplikate kurseva za isti dan
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 4. GLAVNA KNJIGA TRANSAKCIJA (Sirovi podaci + kurs)
-- ==========================================
CREATE TABLE transakcije (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,                  -- Povezivanje sa konkretnim korisnikom
    broker_transakcija_id VARCHAR(100) DEFAULT NULL, -- Generički ID od brokera (future-proof)
    imovina_id INT DEFAULT NULL,               -- NULL za depozite i provizije koje nisu vezane za akciju
    tip_akcije VARCHAR(50) NOT NULL,           -- Action iz CSV-a: 'Deposit', 'Market buy', 'Dividend', itd.
    vreme_utc DATETIME NOT NULL,               -- Time (UTC)
    kolicina DECIMAL(28, 10) DEFAULT 0.0000000000, -- No. of shares
    cena_po_akciji DECIMAL(28, 10) DEFAULT 0.0000000000, -- Price / share
    valuta_cene VARCHAR(5) DEFAULT NULL,       -- Currency (Price / share)
    kurs DECIMAL(20, 10) DEFAULT 1.0000000000, -- Srednji kurs NBS
    ukupno DECIMAL(28, 10) NOT NULL,           -- Total
    valuta_ukupno VARCHAR(5) NOT NULL,         -- Currency (Total)
    porez_po_odbitku DECIMAL(28, 10) DEFAULT 0.0000000000, -- Withholding tax (za dividende)
    valuta_poreza VARCHAR(5) DEFAULT NULL,
    provizija DECIMAL(28, 10) DEFAULT 0.0000000000, -- Charge amount / Deposit fee
    valuta_provizije VARCHAR(5) DEFAULT NULL,
    napomena TEXT DEFAULT NULL,                -- Notes
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE,
    FOREIGN KEY (imovina_id) REFERENCES imovina(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 5. SKLADIŠTE PORESKIH LOTOVA (FIFO magazin)
-- ==========================================
CREATE TABLE poreski_lotovi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,                  -- Izolacija FIFO lotova po korisniku
    transakcija_kupovine_id BIGINT NOT NULL,   -- Veza sa transakcijom tipa kupovine
    imovina_id INT NOT NULL,
    pocetna_kolicina DECIMAL(28, 10) NOT NULL,
    preostala_kolicina DECIMAL(28, 10) NOT NULL, -- Smanjuje se kroz FIFO algoritam
    nabavna_cena_po_jedinici DECIMAL(28, 10) NOT NULL, -- Nabavna cena u originalnoj valuti
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE,
    FOREIGN KEY (transakcija_kupovine_id) REFERENCES transakcije(id) ON DELETE CASCADE,
    FOREIGN KEY (imovina_id) REFERENCES imovina(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================
-- 6. PIVOT TABELA ALOKACIJA (Za prodaje i PPDG-3R)
-- ==========================================
CREATE TABLE alokacije_lotova_prodaje (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,                  -- Izolacija alokacija po korisniku
    transakcija_prodaje_id BIGINT NOT NULL,    -- Veza sa transakcijom prodaje
    poreski_lot_id BIGINT NOT NULL,            -- Iz kog istorijskog lota kupovine se povlači
    iskoriscena_kolicina DECIMAL(28, 10) NOT NULL, -- Količina uzeta iz tog lota (rešava frakcije)
    nabavna_vrednost_rsd DECIMAL(28, 10) NOT NULL, -- Srazmerna nabavna vrednost u RSD (ključno za PPDG-3R)
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE,
    FOREIGN KEY (transakcija_prodaje_id) REFERENCES transakcije(id) ON DELETE CASCADE,
    FOREIGN KEY (poreski_lot_id) REFERENCES poreski_lotovi(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

