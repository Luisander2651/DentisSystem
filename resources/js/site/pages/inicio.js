/*
 * Public site (spec 026): entry of the home page. Brings its stylesheet and its behaviour; the 3D
 * tooth is imported after the `load` event so it never delays the first paint (CA8, CA9).
 */
import '../../../css/site/pages/inicio.css';
import '../home.js';

const startTooth = () => import('../tooth3d.js').then(({ initTooth }) => initTooth());

if (document.readyState === 'complete') {
    startTooth();
} else {
    window.addEventListener('load', startTooth, { once: true });
}
