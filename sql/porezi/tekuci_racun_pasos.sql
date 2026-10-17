USE broker_porez;

-- ==========================================
-- Tekući račun (polje 3.2 obrasca PP OPO) i broj pasoša (polje 2.9 PP OPO)
-- Primenjuje se posle opstina_prebivalista.sql.
-- tekuci_racun: 18 cifara bez crtica (npr. 265000000036005146), kako ga traži
-- šema PPOPO-Prijava.xsd (TekuciRacunTip); obavezan je, ali to proverava
-- aplikacija, pa postojeći korisnici jednom dopunjuju profil.
-- broj_pasosa: opciono.
-- ==========================================
ALTER TABLE poreski_obaveznik
    ADD tekuci_racun CHAR(18) NULL DEFAULT NULL AFTER pib_punomocnika,
    ADD broj_pasosa VARCHAR(30) NULL DEFAULT NULL AFTER tekuci_racun;
