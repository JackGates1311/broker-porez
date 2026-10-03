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
