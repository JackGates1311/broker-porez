import { Modal } from 'bootstrap';

interface OdgovorPodrske {
    poruka?: string;
    errors?: Record<string, string[]>;
}

const PORUKE_STATUSA: Record<number, string> = {
    413: 'Prilozi su preveliki za server. Pošaljite manje ili manje fajlova.',
    419: 'Sesija je istekla. Osvežite stranicu i pošaljite poruku ponovo.',
    429: 'Dostigli ste ograničenje broja poruka (5 za 10 minuta, 10 dnevno). Pokušajte ponovo kasnije.',
};

/**
 * Forma [data-podrska] (widget za povratne informacije insajdera) šalje se fetch-om, bez osvežavanja stranice.
 * Ograničenja priloga (data-maks-*) proveravaju se već pri izboru fajlova; greške servera (422) idu ispod polja.
 * Modal sa data-podrska-otvori se otvara odmah (greške posle slanja bez JS-a).
 */
export function initPodrska(): void {
    const forma = document.querySelector<HTMLFormElement>('form[data-podrska]');
    const modal = forma?.closest<HTMLElement>('.modal');
    const status = forma?.querySelector<HTMLElement>('[data-podrska-status]');
    const dugme = forma?.querySelector<HTMLButtonElement>('button[type="submit"]');
    const prilozi = forma?.querySelector<HTMLInputElement>('input[type="file"]');

    if (!forma || !modal || !status || !dugme || !prilozi) {
        return;
    }

    const prikaziStatus = (tekst: string, vrsta: 'success' | 'danger' | null): void => {
        status.textContent = tekst;
        status.classList.toggle('d-none', vrsta === null);
        status.classList.toggle('alert-success', vrsta === 'success');
        status.classList.toggle('alert-danger', vrsta === 'danger');
    };

    const postaviGresku = (polje: string, tekst: string): void => {
        forma.querySelector(`[name="${polje}"], [name="${polje}[]"]`)?.classList.add('is-invalid');

        const povratna = forma.querySelector<HTMLElement>(`[data-greska-za="${polje}"]`);

        if (povratna) {
            povratna.textContent = tekst;
        }
    };

    const proveriPriloge = (): void => {
        const fajlovi = Array.from(prilozi.files ?? []);
        const maksPriloga = Number(prilozi.dataset.maksPriloga);
        const maksMb = Number(prilozi.dataset.maksKb) / 1024;
        const maksMbUkupno = Number(prilozi.dataset.maksKbUkupno) / 1024;
        const tipovi = (prilozi.dataset.tipovi ?? '').split(',');
        const ukupno = fajlovi.reduce((zbir, fajl) => zbir + fajl.size, 0);

        let greska = '';

        if (fajlovi.length > maksPriloga) {
            greska = `Najviše ${maksPriloga} fajlova.`;
        } else if (fajlovi.some((fajl) => !tipovi.includes(fajl.name.split('.').pop()?.toLowerCase() ?? ''))) {
            greska = 'Dozvoljene su slike (JPG, PNG, WEBP), PDF i TXT fajlovi.';
        } else if (fajlovi.some((fajl) => fajl.size > maksMb * 1024 * 1024)) {
            greska = `Svaki fajl sme imati najviše ${maksMb} MB.`;
        } else if (ukupno > maksMbUkupno * 1024 * 1024) {
            greska = `Prilozi zajedno ne smeju biti veći od ${maksMbUkupno} MB.`;
        }

        prilozi.setCustomValidity(greska);

        if (greska !== '') {
            postaviGresku('prilozi', greska);
        } else {
            prilozi.classList.remove('is-invalid');
        }
    };

    prilozi.addEventListener('change', proveriPriloge);

    forma.addEventListener('submit', async (dogadjaj) => {
        // initValidacijaFormi je već zaustavila neispravnu formu.
        if (dogadjaj.defaultPrevented) {
            return;
        }

        dogadjaj.preventDefault();
        prikaziStatus('', null);

        const prvobitno = Array.from(dugme.childNodes);
        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('aria-hidden', 'true');
        dugme.replaceChildren(spinner, 'Šaljem…');
        dugme.disabled = true;

        const podaci = new FormData(forma);
        // Stranica se menja i bez osvežavanja (tabele, pushState).
        podaci.set('stranica', window.location.href);

        try {
            const odgovor = await fetch(forma.action, {
                method: 'POST',
                body: podaci,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const telo = (await odgovor.json().catch(() => ({}))) as OdgovorPodrske;

            if (odgovor.ok) {
                forma.reset();
                prilozi.setCustomValidity('');
                forma.querySelector('[data-lista-fajlova]')?.replaceChildren();
                forma.querySelectorAll('.is-invalid').forEach((polje) => polje.classList.remove('is-invalid'));
                prikaziStatus(telo.poruka ?? 'Poruka je poslata.', 'success');
                setTimeout(() => Modal.getOrCreateInstance(modal).hide(), 2500);
            } else if (odgovor.status === 422 && telo.errors) {
                Object.entries(telo.errors).forEach(([kljuc, poruke]) => postaviGresku(kljuc.split('.')[0], poruke[0] ?? ''));
            } else {
                prikaziStatus(PORUKE_STATUSA[odgovor.status] ?? 'Poruka nije poslata. Pokušajte ponovo.', 'danger');
            }
        } catch {
            prikaziStatus('Poruka nije poslata. Proverite internet vezu i pokušajte ponovo.', 'danger');
        } finally {
            dugme.replaceChildren(...prvobitno);
            dugme.disabled = false;
        }
    });

    // Posle uspešnog slanja i zatvaranja, sledeće otvaranje počinje od prazne forme.
    modal.addEventListener('hidden.bs.modal', () => {
        if (status.classList.contains('alert-success')) {
            prikaziStatus('', null);
        }
    });

    if (modal.hasAttribute('data-podrska-otvori')) {
        Modal.getOrCreateInstance(modal).show();
    }
}
