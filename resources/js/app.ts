import 'bootstrap';

import { initKodUnos, initOdbrojavanje, initPredloge, initPrikazLozinke, initValidacijaFormi } from './auth';
import { initAutoSlanje, initListeFajlova, initPotvrde, initSlanje, initTabele, initTabove, initTipObaveznika } from './dashboard';
import { initPodrska } from './podrska';
import { initZadovoljstvo } from './zadovoljstvo';

document.addEventListener('DOMContentLoaded', () => {
    initPrikazLozinke();
    initValidacijaFormi();
    initKodUnos();
    initPredloge();
    initOdbrojavanje();
    initAutoSlanje();
    initPotvrde();
    initSlanje();
    initListeFajlova();
    initTabele();
    initTipObaveznika();
    initTabove();
    initPodrska();
    initZadovoljstvo();
});
