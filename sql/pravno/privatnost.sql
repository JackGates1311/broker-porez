USE broker_porez;

-- ==========================================
-- Privatnost: prihvatanje uslova, šifrovana polja obaveznika, anonimna statistika brisanja
-- Primenjuje se posle tekuci_racun_pasos.sql i izvori_uvoza.sql.
-- ==========================================

-- Verzija Uslova korišćenja i Politike privatnosti koju je korisnik prihvatio
-- (config('pravno.verzija')). Nova verzija traži ponovno prihvatanje.
ALTER TABLE korisnici
    ADD uslovi_prihvaceni_at TIMESTAMP NULL DEFAULT NULL,
    ADD uslovi_verzija VARCHAR(20) NULL DEFAULT NULL;

-- JMBG/PIB, JMBG podnosioca, tekući račun i broj pasoša čuvaju se šifrovano
-- (Laravel 'encrypted' cast, ključ APP_KEY), pa šifrat ne staje u CHAR(13)/CHAR(18).
-- Posle ovog fajla jednom pokrenuti: php artisan obaveznik:sifruj
ALTER TABLE poreski_obaveznik
    MODIFY pib TEXT NULL DEFAULT NULL,
    MODIFY jmbg_podnosioca TEXT NULL DEFAULT NULL,
    MODIFY tekuci_racun TEXT NULL DEFAULT NULL,
    MODIFY broj_pasosa TEXT NULL DEFAULT NULL;

-- Jedan red po obrisanom nalogu, bez ikakve veze sa korisnikom (nema korisnik_id ni email-a),
-- pa nije podatak o ličnosti. Svi podaci naloga se brišu fizički.
CREATE TABLE obrisani_nalozi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registrovan_mesec CHAR(7) NOT NULL,                   -- npr. 2026-10
    nacin_prijave ENUM('lozinka', 'google') NOT NULL,
    obrisano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
