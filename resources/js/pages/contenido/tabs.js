import { readListState, writeListState } from '../../ui/pagination';

/*
 * Spec 028 (CA8): the tab and its page live in the address (`pestana`, `pagina`), so reloading or
 * coming back with "Atrás" shows the same tab on the same page. Each tab keeps its own page: going
 * to another tab and back finds it where it was.
 */

function tabKeys() {
    return Array.prototype.slice.call(document.querySelectorAll('[data-content-tab]'))
        .map(function (button) {
            return button.getAttribute('data-content-tab');
        });
}

/**
 * The tab the address asks for, or the first one when it asks for none or for one that does not
 * exist.
 */
function tabFromAddress() {
    var keys = tabKeys();
    var asked = new URLSearchParams(window.location.search).get('pestana');

    return keys.indexOf(asked) !== -1 ? asked : keys[0];
}

function writeTabState(tabKey, page) {
    writeListState({ pagina: page, busqueda: '', estado: '' }, { pestana: tabKey });
}

/**
 * The page a tab starts on: the one in the address for the tab it names, the first for the others.
 */
export function contentTabStartPage(panel) {
    if (!panel || panel.getAttribute('data-content-panel') !== tabFromAddress()) {
        return 1;
    }

    return readListState(window.location.search).pagina;
}

/**
 * A tab tells the page it is showing; the address follows it while the tab is the one in sight.
 */
export function rememberContentTabPage(panel, page) {
    if (!panel) {
        return;
    }

    panel.setAttribute('data-content-page', String(page));

    if (!panel.classList.contains('hidden')) {
        writeTabState(panel.getAttribute('data-content-panel'), page);
    }
}

export function initContentTabs() {
    var tabButtons = Array.prototype.slice.call(document.querySelectorAll('[data-content-tab]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-content-panel]'));

    if (!tabButtons.length || !panels.length) {
        return;
    }

    function activateTab(tabKey) {
        tabButtons.forEach(function (button) {
            var isActive = button.getAttribute('data-content-tab') === tabKey;
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.getAttribute('data-content-panel') !== tabKey);
        });
    }

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var tabKey = button.getAttribute('data-content-tab');
            var panel = panels.find(function (candidate) {
                return candidate.getAttribute('data-content-panel') === tabKey;
            });

            activateTab(tabKey);
            writeTabState(tabKey, Number(panel ? panel.getAttribute('data-content-page') : 1) || 1);
        });
    });

    activateTab(tabFromAddress());
}
