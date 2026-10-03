import 'bootstrap';

import { initKodUnos, initOdbrojavanje, initPrikazLozinke, initValidacijaFormi } from './auth';
import { initAutoSlanje, initListeFajlova, initPotvrde } from './dashboard';

document.addEventListener('DOMContentLoaded', () => {
    initPrikazLozinke();
    initValidacijaFormi();
    initKodUnos();
    initOdbrojavanje();
    initAutoSlanje();
    initPotvrde();
    initListeFajlova();
});
