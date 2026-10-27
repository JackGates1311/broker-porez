USE broker_porez;

-- ==========================================
-- Pitanje o zadovoljstvu posle izvoza obrasca (samo insajderi)
-- ==========================================
-- Pre ovog trenutka se ne pita (posle odgovora +90 dana, posle „Ne sada” +7 dana, config/podrska.php).
-- NULL = „Ne pitaj više”. Postojeći korisnici dobijaju trenutno vreme, pa se pitaju na prvom sledećem izvozu.
-- Aplikacija čita vreme kao UTC, a CURRENT_TIMESTAMP je u zoni sesije (npr. CET), pa se postojeći redovi pune
-- sa UTC_TIMESTAMP(); nove korisnike upisuje aplikacija (Korisnik::booted). sql/baza/vremenska_zona_utc.sql,
-- koji ide posle ovog fajla, ove vrednosti tretira kao upisane iz aplikacije.
ALTER TABLE korisnici
    ADD zadovoljstvo_od TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE korisnici SET zadovoljstvo_od = UTC_TIMESTAMP();

-- Svaki odgovor je novi red (promena zadovoljstva kroz vreme); „Ne sada” i „Ne pitaj više” ne prave red.
-- Čuvaju se dok postoji nalog (BrisanjeNalogaService).
CREATE TABLE ocene_zadovoljstva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    ocena TINYINT UNSIGNED NOT NULL,                          -- 1–5
    komentar TEXT NULL,
    povod VARCHAR(20) NOT NULL,                               -- OcenaZadovoljstva::POVODI: ppdg-3r-pdf, ppdg-3r-xml, pp-opo-pdf, pp-opo-xml
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY kreirano (kreirano_at),
    KEY korisnik_kreirano (korisnik_id, kreirano_at),
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
