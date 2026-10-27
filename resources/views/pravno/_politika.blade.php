{{-- Tekst bez naslova: koristi ga strana /politika-privatnosti i modal na koraku Poreski obaveznik. --}}
@php($rukovalac = config('pravno.rukovalac'))

<p>
    BrokerPorez vam pomaže da obračunate porez na kapitalnu dobit i dividende iz izvoda vašeg brokera.
    Za to su nam potrebni neki vaši podaci. Ovde piše koji su to podaci, gde se čuvaju, ko im ima pristup
    i kako ih brišete. Kratko: podaci se čuvaju na serveru u Evropskoj uniji, ne prodajemo ih, ne koristimo
    ih za reklame i ne delimo ih ni sa kim osim sa servisima koji su neophodni da aplikacija radi.
</p>

<h2>1. Ko obrađuje vaše podatke</h2>
<p>
    Rukovalac podacima je {{ $rukovalac['ime'] }}, {{ $rukovalac['mesto'] }}.
    Za sva pitanja o podacima i za ostvarivanje prava pišite na
    <a href="mailto:{{ $rukovalac['email'] }}">{{ $rukovalac['email'] }}</a>.
</p>
<p>
    Obrada je uređena Zakonom o zaštiti podataka o ličnosti Republike Srbije („Službeni glasnik RS”, br. 87/2018).
    Za korisnike iz Evropske unije primenjuje se i Opšta uredba o zaštiti podataka (GDPR, Uredba (EU) 2016/679).
</p>

<h2>2. Koje podatke obrađujemo</h2>
<ul>
    <li><strong>Podaci naloga:</strong> korisničko ime, email adresa, heš lozinke (lozinku ne znamo), datum registracije i potvrde email adrese, datum i verzija prihvaćenih uslova.</li>
    <li><strong>Podaci poreskog obaveznika</strong> koje unosite za obrasce PPDG-3R i PP OPO: tip obaveznika, JMBG ili PIB, ime i prezime, prebivalište, opština, adresa, telefon, email, JMBG podnosioca, zemlja rezidentstva, JMBG/PIB punomoćnika, tekući račun i broj pasoša.</li>
    <li><strong>Transakcije</strong> iz CSV izvoda koje uvezete: datum i vreme, hartija (ISIN, oznaka, naziv), količina, cena, iznosi, porez po odbitku, provizije i valute, uz kurs NBS primenjen na svaku transakciju. Šabloni uvoza koje sačuvate.</li>
    <li><strong>Poruke podrške i ocene zadovoljstva</strong>, ako ste u Moj nalog uključili povratne informacije: naslov i tekst poruke, priloženi fajlovi (slike, PDF, TXT), adresa stranice sa koje ste poruku poslali i podaci o pregledaču, kao i ocena od 1 do 5, komentar i obrazac posle čijeg izvoza ste ocenili aplikaciju.</li>
    <li><strong>Tehnički podaci:</strong> sesija za prijavu (sa IP adresom i podacima o pregledaču), privremeni fajl CSV izvoda dok traje čarobnjak uvoza i sistemski logovi grešaka.</li>
</ul>
<p>
    Ne obrađujemo podatke o vašem brokerskom nalogu (korisničko ime i lozinku kod brokera nikad ne tražimo), ne pristupamo vašem računu kod brokera i ne pratimo vas na drugim sajtovima.
</p>

<h2>3. Zašto i na osnovu čega</h2>
<p>
    Podatke obrađujemo da bismo vam pružili uslugu koju ste zatražili: izračun poreza i pripremu obrazaca (izvršenje ugovora, ZZPL čl. 12 st. 1 tač. 2, GDPR čl. 6(1)(b)).
    Email koristimo samo za potvrdu adrese, za link za novu lozinku i za odgovor na poruke koje nam pošaljete. Ne šaljemo reklamne poruke.
    Tehničke podatke i logove koristimo da bismo zaštitili nalog (npr. ograničenje pokušaja prijave) i otklonili greške (legitimni interes, ZZPL čl. 12 st. 1 tač. 6).
</p>

<h2>4. Prijava Google nalogom</h2>
<p>
    Ako izaberete „Prijavi se preko Google-a”, od Google-a dobijamo samo vaš Google identifikator, email adresu, podatak da je adresa potvrđena i ime.
    Koristimo ih isključivo da vas prijavimo i povežemo sa vašim nalogom. Ne tražimo pristup Gmail-u, Drive-u, kontaktima ni drugim Google podacima.
    Upotreba podataka dobijenih od Google API-ja je u skladu sa
    <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener">Google API Services User Data Policy</a>,
    uključujući zahteve za ograničenu upotrebu (Limited Use). Ove podatke ne prenosimo drugima, ne koristimo ih za oglašavanje i ne koristimo ih za treniranje modela veštačke inteligencije.
</p>

<h2>5. Gde se podaci čuvaju i ko ima pristup</h2>
<p>
    Aplikacija i baza rade na serveru kompanije Hetzner, a lokacija servera je {{ config('pravno.lokacija_servera') }}. Podatke ne prodajemo, ne iznajmljujemo i ne delimo radi oglašavanja.
    Nemamo analitiku trećih strana ni reklamne kolačiće. Fontovi i skripte se učitavaju sa našeg servera.
    Podatke poveravamo samo sledećim obrađivačima, i to samo u meri potrebnoj za njihov posao:
</p>
<ul>
    @foreach (config('pravno.obradjivaci') as $obradjivac)
        <li><strong>{{ $obradjivac['naziv'] }}</strong> ({{ $obradjivac['sediste'] }}): {{ $obradjivac['svrha'] }}</li>
    @endforeach
</ul>
<p>
    Kurseve NBS preuzimamo sa javnog servisa kurs.resenje.org. Tom servisu šaljemo samo datum za koji nam treba kurs, nikakav vaš podatak.
    Svi obrađivači su u Evropskoj uniji, koja po ZZPL-u pruža primeren nivo zaštite. Podatke ne iznosimo van EU i Srbije.
    Podatke možemo dati državnom organu samo kada to zakon izričito nalaže.
</p>

<h2>6. Kako štitimo podatke</h2>
<ul>
    <li>Sav saobraćaj ide preko šifrovane veze (HTTPS).</li>
    <li>JMBG/PIB, JMBG podnosioca, tekući račun i broj pasoša čuvaju se u bazi šifrovani (AES-256).</li>
    <li>Lozinke čuvamo samo kao heš (bcrypt). Lozinku niko ne može da pročita, pa ni mi.</li>
    <li>Podaci svakog korisnika su odvojeni: svaki upit u aplikaciji je ograničen na vaš nalog.</li>
    <li>Broj pokušaja prijave je ograničen, a kodovi i linkovi za potvrdu ističu posle 15 minuta.</li>
</ul>

<h2>7. Koliko dugo čuvamo podatke</h2>
<ul>
    <li>Podatke naloga, obaveznika i transakcije čuvamo dok imate nalog. Kada nalog obrišete, brišu se odmah i trajno.</li>
    <li>Poruke podrške sa prilozima i ocene zadovoljstva čuvamo uz nalog dok ga imate i brišu se zajedno sa njim. Kopija poruke koja stigne u sanduče podrške čuva se dok je potrebna za odgovor, a na vaš zahtev je brišemo.</li>
    <li>Privremeni fajl CSV izvoda briše se po završetku uvoza, a najkasnije posle 24 sata.</li>
    <li>Sistemski logovi se čuvaju do {{ config('pravno.rok_logova_dana') }} dana.</li>
    <li>Rezervne kopije baze se čuvaju do {{ config('pravno.rok_rezervnih_kopija_dana') }} dana. Obrisani podaci u njima nestaju kada kopija istekne i iz njih se ne vraćaju.</li>
</ul>
<p>
    Posle brisanja naloga čuvamo samo anonimni zapis da je jedan nalog obrisan (mesec registracije i način prijave), bez ikakve veze sa vama.
    Takav zapis nije podatak o ličnosti.
</p>
<p>
    Napomena: po Zakonu o poreskom postupku dokumentaciju za poresku prijavu čuva poreski obaveznik, a ne BrokerPorez.
    Pre brisanja naloga preuzmite obrasce i svoje podatke.
</p>

<h2>8. Vaša prava</h2>
<p>U svakom trenutku imate pravo:</p>
<ul>
    <li>da vidite i preuzmete svoje podatke: <em>Moj nalog → Preuzmi moje podatke</em> daje sve podatke u JSON formatu (pravo na pristup i prenosivost);</li>
    <li>da ispravite podatke: u delu <em>Poreski obaveznik</em> i ponovnim uvozom transakcija;</li>
    <li>da obrišete nalog i sve podatke: <em>Moj nalog → Obriši nalog</em>;</li>
    <li>da uložite prigovor na obradu ili zatražite ograničenje obrade, pisanjem na {{ $rukovalac['email'] }}.</li>
</ul>
<p>
    Na zahtev poslat mejlom odgovaramo najkasnije u roku od 30 dana.
    Ako smatrate da su vam prava povređena, možete podneti pritužbu Povereniku za informacije od javnog značaja i zaštitu podataka o ličnosti
    (<a href="https://www.poverenik.rs" target="_blank" rel="noopener">www.poverenik.rs</a>), Bulevar kralja Aleksandra 15, Beograd.
    Korisnici iz EU mogu se obratiti i nadzornom organu svoje države.
</p>

<h2>9. Kolačići</h2>
<p>
    Koristimo samo kolačiće bez kojih aplikacija ne može da radi: kolačić sesije (da ostanete prijavljeni), zaštitu od CSRF napada i, ako ga uključite, „Zapamti me”.
    Zato ne tražimo saglasnost za kolačiće i nemamo baner.
</p>

<h2>10. Maloletna lica</h2>
<p>Usluga je namenjena punoletnim licima. Svesno ne prikupljamo podatke lica mlađih od 15 godina.</p>

<h2>11. Izmene politike</h2>
<p>
    Ako suštinski promenimo ovu politiku, obavestićemo vas mejlom najmanje 15 dana pre nego što izmene stupe na snagu i objaviti novu verziju na stranici Politika privatnosti.
    Od dana stupanja na snagu, pre nastavka rada u poreskoj knjizi, zatražićemo da potvrdite da ste je pročitali.
</p>
