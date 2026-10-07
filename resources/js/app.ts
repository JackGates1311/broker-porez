import 'bootstrap';

import { initKodUnos, initOdbrojavanje, initPredloge, initPrikazLozinke, initValidacijaFormi } from './auth';
import { initAutoSlanje, initListeFajlova, initPotvrde, initTabele, initTabove, initTipObaveznika } from './dashboard';

document.addEventListener('DOMContentLoaded', () => {
    initPrikazLozinke();
    initValidacijaFormi();
    initKodUnos();
    initPredloge();
    initOdbrojavanje();
    initAutoSlanje();
    initPotvrde();
    initListeFajlova();
    initTabele();
    initTipObaveznika();
    initTabove();
});
