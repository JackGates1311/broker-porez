USE broker_porez;

-- ==========================================
-- Jednokratni tokeni za resetovanje lozinke (link iz mejla)
-- ==========================================
CREATE TABLE tokeni_reset_lozinke (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,             -- sha256 tokena iz linka; sam token se ne čuva
    istice_at DATETIME NOT NULL,
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
