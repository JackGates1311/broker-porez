USE broker_porez;
SET time_zone = '+00:00';

-- ==========================================
-- Istorija prihvatanja Uslova korišćenja i Politike privatnosti (dokaz pristanka, GDPR čl. 7(1))
-- Primenjuje se pre deploy-a koda koji u nju upisuje (Korisnik::prihvatiUslove).
-- korisnici.uslovi_verzija / uslovi_prihvaceni_at ostaju poslednje prihvatanje (njih čita aplikacija),
-- a ovde je svako prihvatanje novi red. Čuva se dok postoji nalog (BrisanjeNalogaService).
-- ==========================================
CREATE TABLE prihvatanja_uslova (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    verzija VARCHAR(20) NOT NULL,                             -- config('pravno.verzija') u trenutku prihvatanja
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY korisnik_kreirano (korisnik_id, kreirano_at),
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ranija prihvatanja: poznato je samo poslednje.
INSERT INTO prihvatanja_uslova (korisnik_id, verzija, kreirano_at)
SELECT id, uslovi_verzija, uslovi_prihvaceni_at
FROM korisnici
WHERE uslovi_verzija IS NOT NULL AND uslovi_prihvaceni_at IS NOT NULL;
