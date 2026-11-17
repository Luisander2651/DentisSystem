/*
 * Public site (spec 026, T019): the photos of the gallery tour after the first ones come with their
 * address in data-src and load only when they come near the view of the tour, so a gallery with
 * many photos does not slow the first visit. Without IntersectionObserver every photo loads at once;
 * without JavaScript the copy in <noscript> shows them.
 */

const AHEAD = '0px 60% 0px 60%';

function load(photo) {
    photo.setAttribute('src', photo.getAttribute('data-src'));
    photo.removeAttribute('data-src');
}

export function loadPhotosNearView(view, win = window) {
    const photos = [...view.querySelectorAll('img[data-src]')];

    if (!photos.length) {
        return;
    }

    if (!win.IntersectionObserver) {
        photos.forEach(load);

        return;
    }

    const observer = new win.IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                load(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { root: view, rootMargin: AHEAD });

    photos.forEach((photo) => observer.observe(photo));
}
