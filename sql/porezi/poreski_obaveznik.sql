USE broker_porez;

-- ==========================================
-- Poreski profil -> poreski obaveznik (Deo 2 PPDG-3R, polja 2.1-2.10)
-- Primenjuje se posle porezi_dopune.sql.
-- Kolone su NULL na nivou baze; obavezna polja proverava aplikacija.
-- ==========================================
RENAME TABLE poreski_profil TO poreski_obaveznik;

ALTER TABLE poreski_obaveznik
    ADD tip_obaveznika TINYINT NULL DEFAULT NULL AFTER korisnik_id,            -- 2.1: 1..4
    CHANGE jmbg pib VARCHAR(13) NULL DEFAULT NULL,                             -- 2.2 JMBG/EBS/PIB
    CHANGE prebivaliste_sifra prebivaliste VARCHAR(255) NULL DEFAULT NULL,     -- 2.4 prebivalište/boravište/sedište/opština
    ADD jmbg_podnosioca CHAR(13) NULL DEFAULT NULL AFTER email,                -- 2.8
    MODIFY zemlja_rezidentstva VARCHAR(3) NULL DEFAULT NULL,                   -- 2.9 (samo nerezidenti)
    ADD pib_punomocnika VARCHAR(13) NULL DEFAULT NULL AFTER zemlja_rezidentstva; -- 2.10 (opciono)

-- Do sada se JMBG podnosioca uzimao iz JMBG-a obaveznika.
UPDATE poreski_obaveznik SET jmbg_podnosioca = pib WHERE jmbg_podnosioca IS NULL AND CHAR_LENGTH(pib) = 13;

-- Ranije podrazumevano 'RS' nije podatak korisnika. Tip obaveznika još nije izabran,
-- pa svi ponovo popunjavaju formu (2.9 se unosi samo za nerezidente).
UPDATE poreski_obaveznik SET zemlja_rezidentstva = NULL;
