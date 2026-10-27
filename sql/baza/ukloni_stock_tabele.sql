USE broker_porez;

-- ==========================================
-- Uklanjanje stock Laravel tabela koje je napravio „php artisan migrate --force” iz ranijeg deploy-a
-- ==========================================
-- Aplikacija ih ne koristi: korisnici su u korisnici (ne users), reset lozinke u tokeni_reset_lozinke
-- (ne password_reset_tokens), a šema se ne vodi migracijama (sql/redosled.txt), pa ni migrations ne treba.
-- sessions i cache ostaju: prave ih sql/session i sql/cache i aplikacija ih koristi.
-- Na bazi bez ovih tabela fajl ne radi ništa.

DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS migrations;
