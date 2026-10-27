interface OdgovorZadovoljstva {
    poruka?: string | null;
    errors?: Record<string, string[]>;
}

/** Posle klika na izvoz pregledač preuzima fajl; kartica se pojavljuje kada preuzimanje krene. */
const KASNJENJE_MS = 2500;

/**
 * Kartica [data-zadovoljstvo] (samo za insajdere kojima je vreme za pitanje) pojavljuje se posle klika na dugme
 * sa data-izvoz-obrasca="<povod>", najviše jednom po učitavanju stranice. Uspešan izvoz je preuzimanje, pa stranica
 * ostaje; kada izvoz ne uspe, server vraća stranicu ponovo i kartica se ni ne prikaže.
 */
export function initZadovoljstvo(): void {
    const kartica = document.querySelector<HTMLElement>('[data-zadovoljstvo]');
    const forma = kartica?.querySelector<HTMLFormElement>('form');
    const povod = forma?.elements.namedItem('povod');
    const greska = kartica?.querySelector<HTMLElement>('[data-zadovoljstvo-greska]');

    if (!kartica || !forma || !(povod instanceof HTMLInputElement) || !greska) {
        return;
    }

    let pokrenuto = false;

    document.addEventListener('submit', (dogadjaj) => {
        const vrednost = dogadjaj.submitter instanceof HTMLElement ? dogadjaj.submitter.dataset.izvozObrasca : undefined;

        if (pokrenuto || vrednost === undefined || dogadjaj.defaultPrevented) {
            return;
        }

        pokrenuto = true;
        povod.value = vrednost;
        setTimeout(() => kartica.classList.remove('d-none'), KASNJENJE_MS);
    });

    const prikaziGresku = (tekst: string): void => {
        greska.textContent = tekst;
        greska.classList.toggle('d-none', tekst === '');
    };

    const dugmad = Array.from(forma.querySelectorAll<HTMLButtonElement>('button[type="submit"]'));

    forma.addEventListener('submit', async (dogadjaj) => {
        dogadjaj.preventDefault();

        const dugme = dogadjaj.submitter instanceof HTMLButtonElement ? dogadjaj.submitter : null;
        const akcija = dugme?.value ?? 'kasnije';

        if (akcija === 'posalji' && forma.querySelector('input[name="ocena"]:checked') === null) {
            prikaziGresku('Izaberite ocenu od 1 do 5.');

            return;
        }

        prikaziGresku('');
        // Pre isključivanja dugmadi: isključeno dugme se ne šalje, a ono nosi akciju (name="akcija").
        const podaci = new FormData(forma, dugme);
        dugmad.forEach((dugme) => (dugme.disabled = true));

        try {
            const odgovor = await fetch(forma.action, {
                method: 'POST',
                body: podaci,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const telo = (await odgovor.json().catch(() => ({}))) as OdgovorZadovoljstva;

            if (odgovor.ok) {
                if (telo.poruka) {
                    const hvala = document.createElement('p');
                    hvala.className = 'mb-0 fw-semibold';
                    hvala.textContent = telo.poruka;
                    forma.replaceChildren(hvala);
                    setTimeout(() => kartica.remove(), 2000);
                } else {
                    kartica.remove();
                }

                return;
            }

            const prva = telo.errors ? Object.values(telo.errors)[0]?.[0] : undefined;
            prikaziGresku(prva ?? 'Ocena nije sačuvana. Pokušajte ponovo.');
        } catch {
            prikaziGresku('Ocena nije sačuvana. Proverite internet vezu i pokušajte ponovo.');
        }

        dugmad.forEach((dugme) => (dugme.disabled = false));
    });
}
