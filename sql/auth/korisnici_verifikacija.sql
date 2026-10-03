USE broker_porez;

-- ==========================================
-- Verifikacija emaila i "zapamti me" za korisnike
-- ==========================================
ALTER TABLE korisnici
    ADD email_verifikovan_at TIMESTAMP NULL DEFAULT NULL,
    ADD remember_token VARCHAR(100) NULL DEFAULT NULL;

-- ==========================================
-- Jednokratni 6-cifreni kodovi za verifikaciju emaila
-- ==========================================
CREATE TABLE verifikacioni_kodovi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    kod_hash VARCHAR(255) NOT NULL,                  -- Kod se čuva samo kao heš
    broj_pokusaja TINYINT UNSIGNED NOT NULL DEFAULT 0, -- Broj neuspešnih unosa
    istice_at DATETIME NOT NULL,
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
