import { initContentTabs } from './tabs';
import { initGalleryModule } from './galeria/index';
import { initPromocionesModule } from './promociones/index';
import { initCertificacionesModule } from './certificaciones/index';
import { initTestimoniosModule } from './testimonios/index';

function bootstrapContenidoPage() {
    initContentTabs();
    initGalleryModule();
    initPromocionesModule();
    initCertificacionesModule();
    initTestimoniosModule();
}

document.addEventListener('DOMContentLoaded', bootstrapContenidoPage);