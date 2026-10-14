USE broker_porez;

-- ==========================================
-- Šifra opštine prebivališta (polje 2.4 obrasca PP OPO)
-- Primenjuje se posle poreski_obaveznik.sql.
-- 2.4 PP OPO traži trocifrenu šifru opštine iz Priloga 3 Pravilnika o uslovima i
-- načinu vođenja računa za uplatu javnih prihoda (config/opstine.php); tekst
-- prebivališta (2.4 PPDG-3R) ostaje u koloni prebivaliste.
-- NULL na nivou baze; obaveznost proverava aplikacija, pa postojeći korisnici
-- jednom dopunjuju profil.
-- ==========================================
ALTER TABLE poreski_obaveznik
    ADD opstina_sifra CHAR(3) NULL DEFAULT NULL AFTER prebivaliste;
