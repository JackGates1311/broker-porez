# Uvoz iz više izvora i čarobnjak za ručno mapiranje CSV-a

Datum: 2026-10-08 · Grana: `f_layout_fix` (ili nova grana za ovu funkcionalnost)

## Cilj

Tab **Uvoz i transakcije** danas zna samo za Trading 212 izvod. Treba ga pripremiti za više brokera:

- **Trading 212**: radi kao danas.
- **Interactive Brokers (IBKR)**: vidljiv u izboru, ali onemogućen („uskoro”); parser dolazi u sledećoj iteraciji.
- **Drugi broker (CSV)**: čarobnjak u više koraka, po uzoru na Oracle SQL Developer „Import Data”, u kome korisnik sam mapira kolone i vrednosti akcija; mapiranje može da sačuva kao imenovani šablon.

Usput: dugmad u kartici „Srednji kurs NBS” postaju `btn-primary` (umesto `btn-outline-primary`).

Uspeh: transakcije uvezene iz ručnog CSV-a ulaze u FIFO, izveštaje i obrasce isto kao T212 redovi; ponovni uvoz istog fajla ne pravi duplikate; postojeći testovi nad Excel fixture-ima i dalje prolaze.

## Ograničenja

- Baza je **MariaDB**. Claude nad njom radi samo read-only upite. Izmene šeme se isporučuju kao `sql/uvoz/izvori_uvoza.sql`, a korisnik ih primenjuje ručno.
- Kod, rute, UI i komentari su na srpskom (latinica). Novac se računa preko `App\Support\Decimal` (bcmath), nikad preko float-a.
- Upiti nad tabelama po korisniku se filtriraju po `korisnik_id`.
- Frontend je Blade + Bootstrap 5, a ponašanje se dodaje kroz `data-*` atribute (`resources/js/dashboard.ts`). Stranica mora da radi i bez JS-a.

## Arhitektura

```
app/Services/Uvoz/
  IzvorUvoza.php           enum string-backed: TRADING212, IBKR, RUCNI; naziv(), dostupan() (IBKR → false)
  ParserIzvoda.php         interface: parsiraj(string $putanja, string $naziv): list<UvezeniRed>; greske(): list<string>
  UvezeniRed.php           + public IzvorUvoza $izvor (tip već postoji)
  Trading212CsvParser.php  implements ParserIzvoda; izvor = TRADING212; tip = TipTransakcije::izAkcije()
  UvozService.php          (preimenovan Trading212UvozService) uvezi(Korisnik, ParserIzvoda, list<UploadedFile|string putanja>)
                           dedupe po jedinstveni_kljuc, upsert imovine po ISIN-u, insert sa tip + izvor,
                           PrimenaKurseva::preuzmiIPrimeni(); vraća UvozRezime
  Rucni/
    PodesavanjaUvoza.php   readonly DTO + izNiza()/uNiz()/sa() (JSON u bazi i sesiji); tab, razmak i „bez” idu kroz formu kao kodovi (kodZnaka/znakIzKoda), jer TrimStrings briše razmak
    PoljeUvoza.php         enum ciljnih polja (vreme, akcija, isin, simbol, naziv, kolicina, cena, valuta_cene,
                           ukupno, valuta_ukupno, porez, valuta_poreza, provizija, valuta_provizije, napomena, broker_id)
                           sa naziv(), obavezno(), predlozi zaglavlja za automatsko mapiranje
    CsvCitac.php           čita sirove redove (separator, kodna strana, red zaglavlja); detektujSeparator(); pregled(n)
    RucniCsvParser.php     implements ParserIzvoda; konstruiše se iz PodesavanjaUvoza
    PredlogMapiranja.php   predlog kolona po zaglavlju i predlog tipa po ključnim rečima akcije
    CarobnjakUvoza.php     stanje u sesiji (uvoz_rucni): uuid fajla, originalni naziv, PodesavanjaUvoza, id šablona;
                           čuvanje/brisanje privremenog fajla; čišćenje fajlova starijih od 24 h
app/Models/SablonUvoza.php  Eloquent model (tabela sabloni_uvoza, podesavanja → PodesavanjaUvoza, CREATED_AT = kreirano_at, UPDATED_AT = null)
```

### PodesavanjaUvoza

| Ključ | Vrednosti |
|---|---|
| `separator` | `,` `;` `\t` `\|` |
| `kodna_strana` | `UTF-8`, `Windows-1250` (konverzija u UTF-8 pre parsiranja; BOM se skida) |
| `red_zaglavlja` | ceo broj ≥ 1 (redovi pre zaglavlja se preskaču) |
| `decimalni_separator` | `.` ili `,` |
| `separator_hiljada` | ``, `.`, `,`, razmak (mora se razlikovati od decimalnog) |
| `format_datuma` | jedan od ponuđenih: `Y-m-d H:i:s`, `Y-m-d H:i`, `Y-m-d`, `d.m.Y H:i:s`, `d.m.Y H:i`, `d.m.Y`, `m/d/Y H:i:s`, `m/d/Y`, `d/m/Y`, `ISO 8601` |
| `vremenska_zona` | `UTC`, `Europe/Belgrade`, `America/New_York`, `Europe/London` |
| `kolone` | `PoljeUvoza` → `{kolona: "<naziv zaglavlja>"}` ili `{konstanta: "<vrednost>"}` |
| `akcije` | `"<sirova vrednost>"` → `TipTransakcije` value |

Kolone se pamte **po nazivu zaglavlja**, ne po indeksu, pa šablon preživi promenu redosleda kolona.

### RucniCsvParser

Za svaki red:

1. Brojeve normalizuje (uklanja separator hiljada, decimalni pretvara u `.`), pa ih prosleđuje u `Decimal::izTeksta()`.
2. Vreme parsira po `format_datuma` u `vremenska_zona` i pretvara u UTC. Ako format nema vreme, uzima se 00:00 u toj zoni.
3. Tip čita iz mapiranja `akcije`. Vrednost koja nije mapirana je greška reda.
4. Proverava obavezna polja po tipu:
   - Kupovina/Prodaja: ISIN, količina > 0, cena;
   - Dividenda: ISIN, ukupno;
   - Depozit: ukupno.
5. Količinu pretvara u apsolutnu vrednost, jer neki brokeri prodaju beleže kao negativnu količinu.
6. Računa `jedinstveni_kljuc = sha256('RUCNI|' + akcija + vreme + isin + količina + cena + ukupno)` (kanonski brojevi preko `Decimal::kanonski`).

Red sa greškom se preskače i ide u `greske()` kao „{fajl}, red {N}: {razlog}”.

## Šema: `sql/uvoz/izvori_uvoza.sql` (MariaDB, primenjuje korisnik)

```sql
USE broker_porez;

ALTER TABLE transakcije
    ADD tip VARCHAR(20) NULL DEFAULT NULL AFTER tip_akcije,      -- TipTransakcije: KUPOVINA/PRODAJA/DIVIDENDA/DEPOZIT/OSTALO
    ADD izvor VARCHAR(20) NOT NULL DEFAULT 'TRADING212' AFTER tip; -- IzvorUvoza: TRADING212/IBKR/RUCNI

UPDATE transakcije SET tip = CASE
    WHEN LOWER(tip_akcije) LIKE '% buy' THEN 'KUPOVINA'
    WHEN LOWER(tip_akcije) LIKE '% sell' THEN 'PRODAJA'
    WHEN LOWER(tip_akcije) LIKE 'dividend%' THEN 'DIVIDENDA'
    WHEN LOWER(tip_akcije) = 'deposit' THEN 'DEPOZIT'
    ELSE 'OSTALO' END;

ALTER TABLE transakcije
    MODIFY tip VARCHAR(20) NOT NULL,
    ADD KEY korisnik_tip (korisnik_id, tip);

CREATE TABLE sabloni_uvoza (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL,
    naziv VARCHAR(100) NOT NULL,
    podesavanja JSON NOT NULL,                                     -- PodesavanjaUvoza::uNiz()
    kreirano_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY korisnik_naziv (korisnik_id, naziv),
    FOREIGN KEY (korisnik_id) REFERENCES korisnici(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

U CLAUDE.md se ovaj fajl dodaje na kraj liste redosleda primene, a sekcija o bazi pominje da je to MariaDB.

## Klasifikacija transakcija prelazi na kolonu `tip`

- `Transakcija::tip()` čita `TipTransakcije::from($this->tip)`. Scope koji je koristio `sqlObrasci()` prelazi na `where('tip', $tip->value)`.
- `SqlIzraz::jeTipa()` → `"{$kolona} = '<value>'"`, sa podrazumevanom kolonom `transakcije.tip`.
- `KapitalnaDobitTabela`: opcije enumeracije koriste `transakcije.tip = ?`.
- `TipTransakcije`: `sqlObrasci()` se uklanja, a `sqlUslov($kolona)` postaje `["{$kolona} = ?", [$this->value]]`. `izAkcije()` ostaje kao T212 klasifikator (javlja se samo u `Trading212CsvParser`). `TabelaTest` se prilagođava.
- `TransakcijeTabela` dobija kolonu **Izvor** (enumeracija nad `transakcije.izvor`). Kolona „Akcija” i dalje prikazuje sirov `tip_akcije`.

## UI

### Tab Uvoz (`resources/views/porezi/uvoz.blade.php`)

Leva kartica **„Uvoz izvoda”**:

- GET forma sa `data-auto-submit` i `<select name="izvor">`:
  - Trading 212;
  - Interactive Brokers (uskoro), `disabled`;
  - Drugi broker (CSV).
- Bez JS-a se forma šalje dugmetom „Prikaži” u `<noscript>` (isti obrazac kao izbor perioda).
- Izabrani izvor dolazi iz `?izvor=`, a podrazumevan je `trading212`. Nepoznata ili nedostupna vrednost se tretira kao `trading212`.
- Sadržaj po izvoru:
  - **Trading 212**: postojeća forma (`POST /uvoz/trading212`).
  - **Drugi broker**: `<select name="sablon_id">` („Novi šablon” + sačuvani), polje za CSV (jedan fajl) i **Nastavi** (`POST /uvoz/rucni`). Ispod je lista sačuvanih šablona sa dugmetom **Obriši** (`DELETE /uvoz/sabloni/{sablon}`, `data-potvrda`).

Desna kartica „Srednji kurs NBS”: oba dugmeta postaju `btn btn-primary btn-sm`.

### Čarobnjak (`resources/views/porezi/uvoz-rucni/*.blade.php`, extends `layouts.knjiga`)

Zajednički deo je partial `_koraci.blade.php`: traka koraka 1–5 (završeni koraci su linkovi) i dugmad **Nazad** i **Odustani**. Odustani je `POST /uvoz/rucni/odustani`: briše fajl i sesiju i vraća korisnika na `/uvoz?izvor=rucni`.

| Korak | Ruta | Sadržaj |
|---|---|---|
| 1 Fajl | `POST /uvoz/rucni` | Upload (`mimes:csv,txt`, max 5120 KB). Čuva se u `storage/app/private/uvoz/{uuid}.csv`. Sa šablonom ide na korak 5, bez šablona na korak 2 sa auto-detektovanim separatorom. |
| 2 Format | `GET/POST /uvoz/rucni/format` | Separator, kodna strana, red zaglavlja, decimalni separator i separator hiljada. Pregled prvih 10 sirovih redova; promena polja osvežava pregled (`data-auto-submit` na GET formi), a „Dalje” čuva izbor. |
| 3 Kolone | `GET/POST /uvoz/rucni/kolone` | Za svako `PoljeUvoza`: `<select>` (— / kolone iz zaglavlja / „Konstanta…”) i input za konstantu (`data-prikazi-za`). Pored polja je primer iz prvog reda. Format datuma i vremenska zona su ovde. Predlog dolazi iz `PredlogMapiranja`; nedostajuće kolone šablona su označene. |
| 4 Akcije | `GET/POST /uvoz/rucni/akcije` | Različite vrednosti mapirane kolone akcije, sa brojem pojavljivanja. Za svaku `<select>` tipa, sa predlogom po ključnim rečima: buy/bought/kupovina → Kupovina; sell/sold/prodaja → Prodaja; div → Dividenda; deposit/uplata → Depozit; ostalo → Ostalo. |
| 5 Pregled | `GET /uvoz/rucni/pregled`, `POST /uvoz/rucni/uvezi` | Ceo fajl prolazi kroz `RucniCsvParser`. Prikazuju se broj redova po tipu, prvih 20 normalizovanih redova (datum po Beogradu, tip, ISIN, količina, cena, valuta, ukupno) i greške po redu. Tu su i opcija „Sačuvaj kao šablon” + naziv (ili „Ažuriraj šablon {naziv}”) i dugme **Uvezi transakcije**. |

Posle uvoza: `UvozService` upisuje redove, privremeni fajl i sesija se brišu, a korisnik se vraća na `/uvoz?izvor=rucni` sa `status` = `UvozRezime::poruka()` i `greske_uvoza`, kao kod T212.

Svaka GET ruta koraka bez važeće sesije ili fajla vraća na `/uvoz?izvor=rucni` sa greškom „Sesija uvoza je istekla, izaberite fajl ponovo.” Korak čiji preduslovi nisu ispunjeni (npr. korak 4 bez mapirane akcije) vraća na prvi nepotpun korak.

Rute idu u grupu sa `auth`, `verifikovan` i `obaveznik`, pored postojećih `uvoz.*`. Kontroler je `App\Http\Controllers\Porezi\UvozRucniController`, a FormRequest-ovi su `UvozRucniFajlRequest`, `UvozRucniFormatRequest`, `UvozRucniKoloneRequest`, `UvozRucniAkcijeRequest` i `UvozRucniUveziRequest`. Nove poruke validacije idu u `lang/sr/validation.php`.

## Greške i bezbednost

- Loši redovi se preskaču i prijavljuju po broju reda; uvoz ostalih se nastavlja.
- Nepotpuno mapiranje ili fajl bez dovoljno kolona: korak ne prolazi, poruke dolaze iz FormRequest-a.
- Privremeni fajl ima UUID ime, a putanja dolazi samo iz sesije. Fajlovi stariji od 24 h se brišu pri koraku 1.
- Šablon se uvek dohvata kao `$korisnik->sabloniUvoza()->findOrFail($id)`, pa tuđi šablon daje 404.
- Sav sadržaj fajla se prikazuje kroz `{{ }}`.

## Testovi

- `tests/Unit/RucniCsvParserTest.php`:
  - `,` / `;` / tab;
  - decimalni zarez i separator hiljada;
  - svaki format datuma; zona `Europe/Belgrade` → UTC;
  - konstanta valute; mapiranje akcija; negativna količina prodaje;
  - greške (loš datum, broj, nemapirana akcija, kupovina bez ISIN-a);
  - stabilan `jedinstveni_kljuc`;
  - Windows-1250 sa „šđčćž”.
- `tests/Unit/PredlogMapiranjaTest.php`: predlog kolona i tipova.
- `tests/Unit/Trading212CsvParserTest.php`: dopuna sa `izvor`/`tip`.
- `tests/Unit/TabelaTest.php`: prilagođen novom `sqlUslov`.
- `tests/Feature/UvozRucniTest.php`. U `setUp` se kroz `Schema::create` prave `korisnici`, `imovina`, `transakcije` (sa `tip`, `izvor`), `kursevi` i `sabloni_uvoza`, a NBS HTTP se lažira preko `Http::fake`. Pokriva:
  - ceo tok 1→5 i upisane redove sa `tip`/`izvor`;
  - ponovni uvoz bez duplikata;
  - čuvanje i primenu šablona (1→5 direktno);
  - šablon sa kolonom koja nedostaje → korak 3;
  - tuđi šablon → 404;
  - Odustani briše fajl;
  - istekla sesija.
- `tests/Feature/UvozStranicaTest.php`: dropdown izvora, IBKR `disabled`, NBS dugmad `btn-primary`, forma za izabrani izvor.
- `composer test`, `vendor/bin/pint --test` i `npm run typecheck` moraju da prođu.

## Van obima

- IBKR parser (sledeća iteracija: `IbkrCsvParser implements ParserIzvoda`, `IzvorUvoza::Ibkr->dostupan() = true`).
- Više fajlova odjednom u ručnom čarobnjaku.
- Promena postojećih T212 `jedinstveni_kljuc` vrednosti.
