USE broker_porez;

-- ==========================================
-- Obaveštenje mejlom o novoj verziji Uslova korišćenja i Politike privatnosti
-- Primenjuje se posle privatnost.sql.
-- Verzija o kojoj je korisnik obavešten, da php artisan pravno:obavesti
-- ne šalje isti mejl dvaput i da može da nastavi posle prekida ili dnevnog limita.
-- ==========================================
ALTER TABLE korisnici
    ADD uslovi_obavesten_verzija VARCHAR(20) NULL DEFAULT NULL;
