/**
 * Forma sa data-auto-submit se šalje čim se promeni neko polje (npr. izbor perioda).
 */
export function initAutoSlanje(): void {
    document.querySelectorAll<HTMLFormElement>('form[data-auto-submit]').forEach((forma) => {
        forma.addEventListener('change', () => forma.requestSubmit());
    });
}

/**
 * Forma sa data-potvrda="<pitanje>" traži potvrdu pre slanja.
 */
export function initPotvrde(): void {
    document.querySelectorAll<HTMLFormElement>('form[data-potvrda]').forEach((forma) => {
        forma.addEventListener('submit', (dogadjaj) => {
            if (!window.confirm(forma.dataset.potvrda ?? 'Da li ste sigurni?')) {
                dogadjaj.preventDefault();
            }
        });
    });
}

/**
 * Lista sa data-lista-fajlova="<id polja>" prikazuje nazive izabranih fajlova.
 */
export function initListeFajlova(): void {
    document.querySelectorAll<HTMLUListElement>('[data-lista-fajlova]').forEach((lista) => {
        const polje = document.getElementById(lista.dataset.listaFajlova ?? '');

        if (!(polje instanceof HTMLInputElement)) {
            return;
        }

        polje.addEventListener('change', () => {
            lista.replaceChildren(
                ...Array.from(polje.files ?? []).map((fajl) => {
                    const stavka = document.createElement('li');
                    stavka.textContent = `${fajl.name} (${Math.max(1, Math.round(fajl.size / 1024))} KB)`;

                    return stavka;
                }),
            );
        });
    });
}

/**
 * Na uskom ekranu se tabovi knjige skroluju vodoravno; aktivni tab se odmah pomera u vidno polje.
 */
export function initTabove(): void {
    document.querySelectorAll<HTMLElement>('.knjiga-tabovi').forEach((tabovi) => {
        const aktivan = tabovi.querySelector<HTMLElement>('.nav-link.active');

        if (aktivan !== null && aktivan.offsetLeft + aktivan.offsetWidth > tabovi.clientWidth) {
            tabovi.scrollLeft = aktivan.offsetLeft - (tabovi.clientWidth - aktivan.offsetWidth) / 2;
        }
    });
}

/**
 * Polja u data-prikazi-za-tip="3,4" vide se samo za izabrane tipove obaveznika (select[data-tip-obaveznika]).
 * Skrivena polja su isključena, pa se ne šalju i ne proveravaju.
 */
export function initTipObaveznika(): void {
    document.querySelectorAll<HTMLSelectElement>('select[data-tip-obaveznika]').forEach((izbor) => {
        const forma = izbor.form;

        if (forma === null) {
            return;
        }

        const osvezi = (): void => {
            forma.querySelectorAll<HTMLElement>('[data-prikazi-za-tip]').forEach((blok) => {
                const vidljivo = (blok.dataset.prikaziZaTip ?? '').split(',').includes(izbor.value);

                blok.classList.toggle('d-none', !vidljivo);
                blok.querySelectorAll<HTMLInputElement>('input, select, textarea').forEach((polje) => {
                    polje.disabled = !vidljivo;
                });
            });
        };

        izbor.addEventListener('change', osvezi);
        osvezi();
    });
}

/**
 * Tabela <x-tabela> (data-tabela) se menja bez ponovnog učitavanja strane:
 *
 * - pretraga dok se kuca (forma sa data-pretraga-uzivo="<min. znakova>"): šalje se kad polje
 *   ima bar toliko znakova ili se isprazni, ali ne dok se broj još kuca ("2.", "1.519,");
 * - klik na link tabele koji vodi na istu stranu (sort, ×, reset, više kolona, strane).
 *
 * Strana se preuzima u pozadini i menjaju se samo delovi sa data-tabela-osvezi, pa nema
 * treptanja, a polje pretrage zadržava fokus. Klikovi idu u istoriju pregledača (Nazad radi),
 * kucanje ne. Bez JS-a sve radi kao obični linkovi i GET forma.
 */
export function initTabele(): void {
    const tabele = Array.from(document.querySelectorAll<HTMLElement>('[data-tabela]'))
        .map(initTabelu)
        .filter((tabela): tabela is ZivaTabela => tabela !== null);

    if (tabele.length > 0) {
        window.addEventListener('popstate', () => {
            tabele.forEach((tabela) => void tabela.ucitaj(window.location.href, 'bez-istorije'));
        });
    }
}

type Istorija = 'zameni' | 'dodaj' | 'bez-istorije';

interface ZivaTabela {
    ucitaj(adresa: string, istorija: Istorija): Promise<void>;
}

function initTabelu(oblast: HTMLElement): ZivaTabela | null {
    const forma = oblast.querySelector<HTMLFormElement>('form[data-pretraga-uzivo]');
    const polje = forma?.querySelector<HTMLInputElement>('input[name="q"]');

    if (!forma || !polje) {
        return null;
    }

    const minimum = Number.parseInt(forma.dataset.pretragaUzivo ?? '2', 10) || 2;
    let poslednja = polje.value.trim();
    let tajmer: number | undefined;
    let zahtev: AbortController | undefined;

    const ucitaj = async (adresa: string, istorija: Istorija): Promise<void> => {
        window.clearTimeout(tajmer);
        zahtev?.abort();
        zahtev = new AbortController();
        oblast.setAttribute('aria-busy', 'true');

        try {
            const odgovor = await fetch(adresa, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: zahtev.signal,
            });

            // Istekla sesija, greška servera i sl.: obično učitavanje pokazuje šta se desilo.
            if (!odgovor.ok || odgovor.redirected) {
                window.location.assign(adresa);

                return;
            }

            const nova = new DOMParser().parseFromString(await odgovor.text(), 'text/html');
            const fokus = document.activeElement instanceof HTMLElement && oblast.contains(document.activeElement)
                ? document.activeElement.dataset.fokus
                : undefined;

            if (!zameniDelove(oblast, nova)) {
                window.location.assign(adresa);

                return;
            }

            // Kod linkova (npr. reset) pretraga u polju dolazi sa servera; dok se kuca, polje se ne dira.
            if (istorija !== 'zameni') {
                const novoPolje = nova.querySelector<HTMLInputElement>(`[data-tabela="${CSS.escape(oblast.dataset.tabela ?? '')}"] input[name="q"]`);
                polje.value = novoPolje?.value ?? '';
            }

            poslednja = polje.value.trim();

            if (fokus) {
                oblast.querySelector<HTMLElement>(`[data-fokus="${CSS.escape(fokus)}"]`)?.focus();
            }

            if (istorija === 'dodaj') {
                window.history.pushState(null, '', adresa);
            } else if (istorija === 'zameni') {
                window.history.replaceState(null, '', adresa);
            }

            oblast.removeAttribute('aria-busy');
        } catch (greska) {
            if (!(greska instanceof DOMException && greska.name === 'AbortError')) {
                window.location.assign(adresa);
            }
        }
    };

    forma.addEventListener('submit', (dogadjaj) => {
        dogadjaj.preventDefault();
        void ucitaj(adresaForme(forma), 'zameni');
    });

    polje.addEventListener('input', () => {
        window.clearTimeout(tajmer);

        tajmer = window.setTimeout(() => {
            const vrednost = polje.value.trim();

            if (vrednost === poslednja || (vrednost.length > 0 && vrednost.length < minimum) || nedovrsenBroj(vrednost)) {
                return;
            }

            void ucitaj(adresaForme(forma), 'zameni');
        }, 400);
    });

    oblast.addEventListener('click', (dogadjaj) => {
        const link = dogadjaj.target instanceof Element ? dogadjaj.target.closest<HTMLAnchorElement>('a[href]') : null;

        // Ctrl/Cmd/Shift/srednji klik ostaju pregledaču (nova kartica i sl.).
        if (!link || dogadjaj.defaultPrevented || dogadjaj.button !== 0 || dogadjaj.metaKey || dogadjaj.ctrlKey || dogadjaj.shiftKey || dogadjaj.altKey) {
            return;
        }

        const adresa = new URL(link.href, window.location.href);

        // Samo linkovi koji menjaju stanje ove tabele (ista strana, drugi parametri).
        if (link.target || adresa.origin !== window.location.origin || adresa.pathname !== window.location.pathname) {
            return;
        }

        dogadjaj.preventDefault();

        if (link.closest('.pagination') && oblast.getBoundingClientRect().top < 0) {
            oblast.scrollIntoView({ block: 'start' });
        }

        void ucitaj(adresa.toString(), 'dodaj');
    });

    return { ucitaj };
}

/**
 * Cifra pa tačka ili zarez na kraju znači da se decimale (ili hiljade) tek kucaju.
 * Izuzetak je ceo datum sa tačkom na kraju, kako se piše na srpskom: "15.03.2026." ili "03.2026.".
 */
function nedovrsenBroj(vrednost: string): boolean {
    return /\d[.,]$/.test(vrednost) && !/^\d{1,2}\.(\d{1,2}\.)?\d{4}\.$/.test(vrednost);
}

/**
 * GET adresa forme; prazna pretraga se izostavlja iz URL-a.
 */
function adresaForme(forma: HTMLFormElement): string {
    const adresa = new URL(forma.action || window.location.href, window.location.href);
    const parametri = new URLSearchParams();

    new FormData(forma).forEach((vrednost, ime) => {
        if (typeof vrednost === 'string' && !(ime === 'q' && vrednost.trim() === '')) {
            parametri.append(ime, vrednost);
        }
    });

    adresa.search = parametri.toString();

    return adresa.toString();
}

/**
 * Menja delove [data-tabela-osvezi] ove tabele istim delovima iz preuzete strane.
 * Vraća false ako ih u odgovoru nema (npr. promenjena struktura strane).
 */
function zameniDelove(oblast: HTMLElement, nova: Document): boolean {
    const novaOblast = nova.querySelector<HTMLElement>(`[data-tabela="${CSS.escape(oblast.dataset.tabela ?? '')}"]`);

    if (!novaOblast) {
        return false;
    }

    const delovi = Array.from(oblast.querySelectorAll<HTMLElement>('[data-tabela-osvezi]'));
    const zamene = delovi.map((deo) =>
        novaOblast.querySelector<HTMLElement>(`[data-tabela-osvezi="${CSS.escape(deo.dataset.tabelaOsvezi ?? '')}"]`),
    );

    if (zamene.some((zamena) => zamena === null)) {
        return false;
    }

    delovi.forEach((deo, i) => deo.replaceWith(document.importNode(zamene[i] as HTMLElement, true)));

    return true;
}
