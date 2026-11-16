/*
 * Public site (spec 026): entry of the gallery. Brings its stylesheet, pins the tour of the clinic
 * while its photos pass sideways (gallery.js), loads each photo as it comes near the view and wires
 * the before-and-after comparator with its 3D denture (the flat drawing stays without WebGL).
 */
import '../../../css/site/pages/galeria.css';
import { initCompare, initDenture, initTour } from '../gallery.js';
import { loadPhotosNearView } from '../gallery-photos.js';

const section = document.querySelector('[data-gallery="recorrido"]');

if (section) {
    initTour(section);
    loadPhotosNearView(section.querySelector('.hz-view'));
}

const beforeAfter = document.querySelector('[data-before-after]');

if (beforeAfter) {
    initDenture(beforeAfter, initCompare(beforeAfter));
}
