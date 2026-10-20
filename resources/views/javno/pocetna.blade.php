@extends('layouts.app')

@section('naslov', 'Porez na akcije i dividende')
@section('sirina-poruke', 'mx-auto')

@section('sadrzaj')
    <section class="uvod" aria-labelledby="uvodNaslov">
        <div class="uvod-tekst">
            <h1 id="uvodNaslov" class="uvod-naslov">Porez na akcije i dividende od izvoda brokera do prijave PPDG-3R</h1>
            <p class="uvod-podnaslov">
                Uvezite CSV izvod iz Trading 212 ili drugog brokera. BrokerPorez preračunava svaku transakciju po srednjem kursu NBS,
                raspoređuje prodaje po FIFO metodi i priprema obrasce PPDG-3R i PP OPO, u PDF-u i kao XML za portal ePorezi.
            </p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <a href="{{ route('registracija') }}" class="btn btn-primary btn-lg">Napravi besplatan nalog</a>
                <a href="{{ route('prijava') }}" class="btn btn-outline-primary btn-lg">Prijavi se</a>
            </div>
            <p class="uvod-poverenje mt-3 mb-0">Podaci su na serveru u EU, zaštićeni po GDPR-u, i ne prodajemo ih.</p>
        </div>

        <figure class="uvod-obrazac" aria-label="Primer obračuna jedne prodaje">
            <figcaption class="obrazac-zaglavlje">
                <span>Primer obračuna</span>
                <span>PPDG-3R, Deo 4</span>
            </figcaption>
            <dl class="obrazac-redovi">
                <div><dt>Prodaja, 14.03.2026.</dt><dd>10 × AAPL po 225,00 USD</dd></div>
                <div><dt>Kurs NBS na dan prodaje</dt><dd>100,5000</dd></div>
                <div><dt>Prodajna cena</dt><dd>226.125,00 RSD</dd></div>
                <div><dt>Nabavna cena (FIFO, kupovina 02.09.2025.)</dt><dd>194.760,00 RSD</dd></div>
                <div class="obrazac-zbir"><dt>Kapitalni dobitak</dt><dd>31.365,00 RSD</dd></div>
            </dl>
            <div class="polje-obrasca mt-3">
                <div class="polje-obrasca-oznaka">Porez za uplatu (15 %)</div>
                <x-kucice :iznos="new \BcMath\Number('4704.75')" />
            </div>
        </figure>
    </section>

    <section class="javna-sekcija" aria-labelledby="kakoRadi">
        <h2 id="kakoRadi" class="javni-naslov">Kako radi</h2>
        <ol class="koraci-javno">
            <li>
                <h3>Uvezite izvod</h3>
                <p>Preuzmite CSV iz Trading 212 i prevucite ga u aplikaciju. Za druge brokere čarobnjak vas vodi kroz povezivanje kolona, a podešavanje se čuva kao šablon.</p>
            </li>
            <li>
                <h3>Kursevi i FIFO</h3>
                <p>Za svaki datum se uzima srednji kurs NBS. Prodaje se uparuju sa najstarijim kupovinama, uključujući i delove akcija.</p>
            </li>
            <li>
                <h3>Proverite obračun</h3>
                <p>Kapitalna dobit i dividende po polugodištima, sa svakom kupovinom koja je ušla u nabavnu cenu. Upozorenje stiže ako nedostaje kurs ili kupovina.</p>
            </li>
            <li>
                <h3>Preuzmite obrasce</h3>
                <p>PPDG-3R i PP OPO kao PDF za proveru i XML za „XML unos” na portalu ePorezi. Prijavu podnosite vi.</p>
            </li>
        </ol>
    </section>

    <section class="javna-sekcija javna-podaci" aria-labelledby="vasiPodaci">
        <div>
            <h2 id="vasiPodaci" class="javni-naslov">Vaši podaci ostaju vaši</h2>
            <p class="text-body-secondary">
                Za poresku prijavu su potrebni JMBG, adresa i tekući račun. Zato ih čuvamo onako kako biste i vi želeli.
            </p>
            <a href="{{ route('pravno.politika') }}">Pročitajte Politiku privatnosti</a>
        </div>
        <ul class="lista-garancija">
            <li><strong>Server u Evropskoj uniji.</strong> Aplikacija i baza su u data centru u EU, pod GDPR-om, a mi poštujemo i srpski Zakon o zaštiti podataka o ličnosti.</li>
            <li><strong>Ne prodajemo i ne delimo.</strong> Nema reklama, analitike trećih strana ni prodaje podataka. Mejlove šaljemo samo za potvrdu adrese i promenu lozinke.</li>
            <li><strong>Šifrovano.</strong> JMBG, tekući račun i broj pasoša su u bazi šifrovani, lozinka se čuva samo kao heš, a veza ide preko HTTPS-a.</li>
            <li><strong>Brisanje jednim potezom.</strong> U „Moj nalog” preuzimate sve svoje podatke ili trajno brišete nalog, bez pisanja zahteva.</li>
            <li><strong>Bez pristupa brokeru.</strong> Ne tražimo lozinku vašeg brokera: radimo samo sa izvodom koji sami uvezete.</li>
        </ul>
    </section>

    <section class="javna-sekcija" aria-labelledby="brokeri">
        <h2 id="brokeri" class="javni-naslov">Podržani brokeri</h2>
        <ul class="lista-brokera">
            <li><strong>Trading 212</strong><span>Direktan uvoz CSV izvoza, bez podešavanja.</span></li>
            <li><strong>Bilo koji broker sa CSV izvozom</strong><span>Čarobnjak za kolone, formate datuma i brojeva i tipove transakcija.</span></li>
            <li><strong>Interactive Brokers</strong><span>Uskoro.</span></li>
        </ul>
    </section>

    <section class="javna-sekcija" aria-labelledby="pitanja">
        <h2 id="pitanja" class="javni-naslov">Česta pitanja</h2>
        <div class="pitanja">
            <details>
                <summary>Da li BrokerPorez podnosi prijavu umesto mene?</summary>
                <p>Ne. Priprema obrasce koje proveravate i sami podnosite na portalu ePorezi, preko „XML unosa” ili ručno. Za prijavu odgovarate vi.</p>
            </details>
            <details>
                <summary>Koliko je obračun tačan?</summary>
                <p>
                    Obračun prati pravila za PPDG-3R (srednji kurs NBS, FIFO), ali zavisi od podataka koje uvezete. Ako u izvodu nedostaju starije kupovine,
                    splitovi ili prenosi hartija, rezultat neće biti tačan. Aplikacija označava prodaje bez dovoljno kupovina i transakcije bez kursa.
                    BrokerPorez je pomoćni alat, a ne poreski savetnik.
                </p>
            </details>
            <details>
                <summary>Gde se čuvaju moji podaci i ko ih vidi?</summary>
                <p>Na serveru u EU. Pristup ima samo vaš nalog. Podatke koriste samo neophodni obrađivači (hosting i slanje mejlova), navedeni u Politici privatnosti.</p>
            </details>
            <details>
                <summary>Mogu li da obrišem nalog?</summary>
                <p>Da, u svakom trenutku, u „Moj nalog”. Brisanje je trajno i odmah uklanja sve vaše podatke. Pre toga možete preuzeti sve podatke u jednom fajlu.</p>
            </details>
            <details>
                <summary>Koliko košta?</summary>
                <p>Korišćenje je besplatno.</p>
            </details>
        </div>
    </section>

    <section class="javna-sekcija javni-kraj">
        <p class="napomena-alat mb-4">
            BrokerPorez je pomoćni alat. Obračun zavisi od uvezenih podataka. Proverite ga pre podnošenja prijave.
            Detalji su u <a href="{{ route('pravno.uslovi') }}">Uslovima korišćenja</a>.
        </p>
        <a href="{{ route('registracija') }}" class="btn btn-primary btn-lg">Napravi besplatan nalog</a>
    </section>
@endsection
