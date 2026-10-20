{{-- Tekst bez naslova: koristi ga strana /uslovi-koriscenja i modal na koraku Poreski obaveznik. --}}
@php($rukovalac = config('pravno.rukovalac'))

<div class="napomena-alat my-4">
    <strong>BrokerPorez je pomoćni alat, a ne poreski savetnik.</strong>
    Obračun zavisi od podataka koje uvezete i ne garantujemo da je potpuno tačan.
    Pre podnošenja prijave proverite je, a prijavu podnosite i za nju odgovarate isključivo vi.
</div>

<h2>1. O usluzi</h2>
<p>
    BrokerPorez (u daljem tekstu: usluga) je internet aplikacija koju pruža {{ $rukovalac['ime'] }}, {{ $rukovalac['mesto'] }} (u daljem tekstu: pružalac).
    Usluga uvozi transakcije iz CSV izvoda brokera, preračunava ih po srednjem kursu NBS, raspoređuje prodaje po FIFO metodi
    i priprema obrasce PPDG-3R i PP OPO (PDF i XML za portal ePorezi). Usluga je besplatna.
    Registracijom i prihvatanjem ovih uslova zaključujete sa pružaocem ugovor o korišćenju usluge.
</p>

<h2>2. Nalog</h2>
<p>
    Za korišćenje usluge potreban je nalog sa potvrđenom email adresom. Odgovorni ste za tajnost lozinke i za sve što se radi preko vašeg naloga.
    Unosite samo svoje podatke ili podatke lica koje vas je za to ovlastilo. Usluga je namenjena punoletnim licima.
</p>

<h2>3. Pomoćni alat, bez garancije tačnosti</h2>
<p>Usluga je pomoćno sredstvo za obračun i ne predstavlja poresko, pravno ni finansijsko savetovanje. Posebno:</p>
<ul>
    <li>Rezultat zavisi od potpunosti i tačnosti CSV izvoda koje uvezete. Ako nedostaju kupovine, splitovi, prenosi hartija ili korporativne akcije, obračun neće biti tačan.</li>
    <li>Kursevi se preuzimaju sa javnog izvora podataka NBS. Kurs koji nije dostupan označen je u aplikaciji i ne zamenjuje se pretpostavljenom vrednošću.</li>
    <li>Šifre i oznake u obrascima (vrsta prijave, šifra vrste prihoda i slično) su podrazumevane vrednosti koje morate proveriti.</li>
    <li>Poreski propisi i praksa Poreske uprave se menjaju, a usluga ne mora odmah da prati svaku izmenu.</li>
</ul>
<p>
    Vi ste poreski obaveznik u smislu zakona: pre podnošenja proveravate obrasce, sami ih podnosite na portalu ePorezi i odgovarate za njihovu sadržinu.
    Za složenije situacije posavetujte se sa poreskim savetnikom ili Poreskom upravom.
</p>

<h2>4. Ograničenje odgovornosti</h2>
<p>
    Usluga se pruža „kakva jeste”, bez garancije da će biti neprekidno dostupna i bez grešaka.
    U meri u kojoj to dozvoljavaju propisi Republike Srbije, pružalac ne odgovara za štetu, poreske kazne, kamate ni izgubljenu dobit
    nastalu zbog korišćenja obračuna, obrazaca ili podataka iz usluge, niti zbog nedostupnosti usluge.
    Ovo ograničenje ne važi za štetu prouzrokovanu namerno ili krajnjom nepažnjom, ni u slučajevima u kojima zakon ne dozvoljava isključenje odgovornosti.
</p>

<h2>5. Dozvoljeno korišćenje</h2>
<p>
    Zabranjeno je pristupati tuđim nalozima, zaobilaziti zaštitu, opterećivati servis automatizovanim zahtevima i uvoziti sadržaj koji nije izvod transakcija.
    Pružalac može da suspenduje nalog koji krši ove uslove.
</p>

<h2>6. Vaši podaci</h2>
<p>
    Podaci koje unesete ostaju vaši. Obrađujemo ih samo da bismo pružili uslugu, kako je opisano u <a href="{{ route('pravno.politika') }}" target="_blank" rel="noopener">Politici privatnosti</a>,
    koja je sastavni deo ovih uslova. Podatke ne prodajemo i ne delimo radi oglašavanja.
</p>

<h2>7. Prestanak korišćenja</h2>
<p>
    Nalog možete obrisati u svakom trenutku (<em>Moj nalog → Obriši nalog</em>). Brisanjem se trajno uklanjaju nalog, podaci obaveznika, transakcije i obračuni.
    Pre toga preuzmite obrasce i podatke koji su vam potrebni. Pružalac može da ukine uslugu uz obaveštenje objavljeno najmanje 30 dana unapred, kako biste stigli da preuzmete podatke.
</p>

<h2>8. Izmene uslova</h2>
<p>
    O suštinskim izmenama obaveštavamo vas mejlom najmanje 15 dana pre nego što stupe na snagu, uz kratak opis šta se menja.
    Do tada važi verzija koju ste prihvatili. Od dana stupanja na snagu nova verzija se primenjuje na vas kada je prihvatite u aplikaciji,
    a do tada ne možete da nastavite rad u poreskoj knjizi. Ako je ne prihvatite, možete u svakom trenutku preuzeti podatke i obrisati nalog.
</p>

<h2>9. Merodavno pravo i kontakt</h2>
<p>
    Na ove uslove primenjuje se pravo Republike Srbije. Za sporove je nadležan sud prema propisima Republike Srbije, a potrošači zadržavaju pravo na zaštitu po Zakonu o zaštiti potrošača.
    Pitanja i prigovore šaljite na <a href="mailto:{{ $rukovalac['email'] }}">{{ $rukovalac['email'] }}</a>.
</p>
