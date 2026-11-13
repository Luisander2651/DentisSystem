/*
 * Public site (spec 026): script every page of the site loads. For now it only marks that
 * JavaScript runs, so that the stylesheets hide what JavaScript will reveal only in that case;
 * T009 brings the rest of core.js from the proposal.
 */
document.documentElement.classList.add('js');
