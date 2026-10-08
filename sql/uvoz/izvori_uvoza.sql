USE broker_porez;

-- ==========================================
-- Tip i izvor transakcije (uvoz iz više brokera)
-- ==========================================
-- tip_akcije ostaje sirov tekst brokera (za prikaz); obračun čita normalizovan tip.
ALTER TABLE transakcije
    ADD tip VARCHAR(20) NULL DEFAULT NULL AFTER tip_akcije,         -- TipTransakcije: KUPOVINA/PRODAJA/DIVIDENDA/DEPOZIT/OSTALO
    ADD izvor VARCHAR(20) NOT NULL DEFAULT 'TRADING212' AFTER tip;  -- IzvorUvoza: TRADING212/IBKR/RUCNI

-- Postojeći redovi su iz Trading 212 izvoda: ista pravila kao TipTransakcije::izAkcije().
UPDATE transakcije SET tip = CASE
    WHEN LOWER(tip_akcije) LIKE '% buy' THEN 'KUPOVINA'
    WHEN LOWER(tip_akcije) LIKE '% sell' THEN 'PRODAJA'
    WHEN LOWER(tip_akcije) LIKE 'dividend%' THEN 'DIVIDENDA'
    WHEN LOWER(tip_akcije) = 'deposit' THEN 'DEPOZIT'
    ELSE 'OSTALO'
END;

ALTER TABLE transakcije
    MODIFY tip VARCHAR(20) NOT NULL,
    ADD KEY korisnik_tip (korisnik_id, tip);

-- ==========================================
-- Šabloni ručnog uvoza CSV-a (mapiranje kolona i akcija po korisniku)
-- ==========================================
CREATE TABLE sabloni_uvoza (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    naziv VARCHAR(100) NOT NULL,
    podesavanja JSON NOT NULL,                                       -- PodesavanjaUvoza::uNiz()
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY korisnik_naziv (korisnik_id, naziv),
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
