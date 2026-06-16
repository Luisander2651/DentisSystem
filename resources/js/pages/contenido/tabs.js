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
            button.style.borderColor = isActive ? '#F5C2D6' : '#E2E8F0';
            button.style.background = isActive ? '#FDF1F6' : '#FFFFFF';
            button.style.color = isActive ? '#B5114A' : '#475569';
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