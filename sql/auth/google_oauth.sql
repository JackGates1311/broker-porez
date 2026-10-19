USE broker_porez;

-- ==========================================
-- Prijava preko Google-a: stabilni Google ID ("sub") povezanog naloga.
-- Lozinka i email ostaju kao i do sada, pa se Google korisnik uvek može
-- prijaviti i lozinkom (postavljenom preko "Zaboravljena lozinka?").
-- ==========================================
ALTER TABLE korisnici
    ADD google_id VARCHAR(255) NULL DEFAULT NULL,
    ADD UNIQUE INDEX google_id (google_id);
