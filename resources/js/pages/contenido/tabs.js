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
            activateTab(button.getAttribute('data-content-tab'));
        });
    });

    activateTab('galeria');
}