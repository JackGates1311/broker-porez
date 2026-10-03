const DUZINA_KODA = 6;

/**
 * Dugme sa atributom data-toggle-lozinka="<id polja>" prikazuje/sakriva lozinku.
 */
export function initPrikazLozinke(): void {
    document.querySelectorAll<HTMLButtonElement>('[data-toggle-lozinka]').forEach((dugme) => {
        const polje = document.getElementById(dugme.dataset.toggleLozinka ?? '');

        if (!(polje instanceof HTMLInputElement)) {
            return;
        }

        dugme.addEventListener('click', () => {
            const prikazana = polje.type === 'text';

            polje.type = prikazana ? 'password' : 'text';
            dugme.textContent = prikazana ? 'Prikaži' : 'Sakrij';
            dugme.setAttribute('aria-pressed', String(!prikazana));
        });
    });
}

/**
 * Bootstrap validacija na klijentu za forme sa klasom .needs-validation.
 * Polje sa data-poklapa-se-sa="<id>" mora imati istu vrednost kao navedeno polje.
 */
export function initValidacijaFormi(): void {
    document.querySelectorAll<HTMLFormElement>('form.needs-validation').forEach((forma) => {
        const potvrde = forma.querySelectorAll<HTMLInputElement>('[data-poklapa-se-sa]');

        const proveriPotvrde = (): void => {
            potvrde.forEach((potvrda) => {
                const original = document.getElementById(potvrda.dataset.poklapaSeSa ?? '');
                const poklapaSe = original instanceof HTMLInputElement && original.value === potvrda.value;

                potvrda.setCustomValidity(poklapaSe ? '' : 'Lozinke se ne poklapaju.');
            });
        };

        // Greške sa servera se uklanjaju čim korisnik izmeni polje.
        forma.addEventListener('input', (dogadjaj) => {
            if (dogadjaj.target instanceof HTMLInputElement) {
                dogadjaj.target.classList.remove('is-invalid');
            }

            proveriPotvrde();
        });

        forma.addEventListener('submit', (dogadjaj) => {
            proveriPotvrde();

            if (!forma.checkValidity()) {
                dogadjaj.preventDefault();
                dogadjaj.stopPropagation();
            }

            forma.classList.add('was-validated');
        });
    });
}

/**
 * Polje za kod prihvata samo cifre (i nalepljen kod) i automatski šalje formu posle 6 cifara.
 */
export function initKodUnos(): void {
    const polje = document.querySelector<HTMLInputElement>('[data-kod-unos]');
    const forma = polje?.closest<HTMLFormElement>('form');

    if (!polje || !forma) {
        return;
    }

    let poslato = false;

    const obradi = (vrednost: string): void => {
        polje.value = vrednost.replace(/\D/g, '').slice(0, DUZINA_KODA);

        if (polje.value.length === DUZINA_KODA && !poslato) {
            poslato = true;
            forma.requestSubmit();
        }
    };

    polje.addEventListener('input', () => obradi(polje.value));

    polje.addEventListener('paste', (dogadjaj: ClipboardEvent) => {
        const tekst = dogadjaj.clipboardData?.getData('text');

        if (tekst) {
            dogadjaj.preventDefault();
            obradi(tekst);
        }
    });

    // Ako validacija na klijentu zaustavi slanje, dozvoli ponovni pokušaj.
    forma.addEventListener('submit', (dogadjaj) => {
        if (dogadjaj.defaultPrevented) {
            poslato = false;
        }
    });
}

/**
 * Dugme sa data-odbrojavanje="<sekunde>" ostaje onemogućeno dok odbrojavanje ne istekne.
 */
export function initOdbrojavanje(): void {
    document.querySelectorAll<HTMLButtonElement>('[data-odbrojavanje]').forEach((dugme) => {
        const originalniTekst = dugme.textContent?.trim() ?? '';
        let preostalo = Number.parseInt(dugme.dataset.odbrojavanje ?? '0', 10);

        if (!Number.isFinite(preostalo) || preostalo <= 0) {
            return;
        }

        const osvezi = (): void => {
            if (preostalo <= 0) {
                window.clearInterval(interval);
                dugme.disabled = false;
                dugme.textContent = originalniTekst;

                return;
            }

            dugme.disabled = true;
            dugme.textContent = `${originalniTekst} (${preostalo} s)`;
            preostalo--;
        };

        const interval = window.setInterval(osvezi, 1000);
        osvezi();
    });
}
