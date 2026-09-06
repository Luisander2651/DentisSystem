import './bootstrap';

function syncSidebarMenu(sidebar, menu, toggleButton, openIcon, closeIcon) {
	if (!sidebar || !menu || !toggleButton) {
		return;
	}

	const isDesktop = window.innerWidth >= 768;

	if (isDesktop) {
		menu.classList.remove('hidden');
		toggleButton.setAttribute('aria-expanded', 'true');

		if (openIcon) openIcon.classList.remove('hidden');
		if (closeIcon) closeIcon.classList.add('hidden');
		return;
	}

	const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';

	if (!isExpanded) {
		menu.classList.add('hidden');
		if (openIcon) openIcon.classList.remove('hidden');
		if (closeIcon) closeIcon.classList.add('hidden');
		return;
	}

	menu.classList.remove('hidden');
	if (openIcon) openIcon.classList.add('hidden');
	if (closeIcon) closeIcon.classList.remove('hidden');
}

function initSidebarToggle(sidebar) {
	if (!sidebar || sidebar.dataset.sidebarInit === 'true') {
		return;
	}

	sidebar.dataset.sidebarInit = 'true';

	const toggleButton = sidebar.querySelector('[data-sidebar-toggle]');
	const menu = sidebar.querySelector('[data-sidebar-menu]');
	const openIcon = sidebar.querySelector('[data-icon-open]');
	const closeIcon = sidebar.querySelector('[data-icon-close]');

	if (!toggleButton || !menu) {
		return;
	}

	syncSidebarMenu(sidebar, menu, toggleButton, openIcon, closeIcon);

	toggleButton.addEventListener('click', function () {
		const isHidden = menu.classList.contains('hidden');

		if (isHidden) {
			menu.classList.remove('hidden');
			toggleButton.setAttribute('aria-expanded', 'true');
			if (openIcon) openIcon.classList.add('hidden');
			if (closeIcon) closeIcon.classList.remove('hidden');
			return;
		}

		menu.classList.add('hidden');
		toggleButton.setAttribute('aria-expanded', 'false');
		if (openIcon) openIcon.classList.remove('hidden');
		if (closeIcon) closeIcon.classList.add('hidden');
	});
}

document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('[data-sidebar-root]').forEach(initSidebarToggle);
});

window.addEventListener('resize', function () {
	document.querySelectorAll('[data-sidebar-root]').forEach(function (sidebar) {
		const toggleButton = sidebar.querySelector('[data-sidebar-toggle]');
		const menu = sidebar.querySelector('[data-sidebar-menu]');
		const openIcon = sidebar.querySelector('[data-icon-open]');
		const closeIcon = sidebar.querySelector('[data-icon-close]');

		syncSidebarMenu(sidebar, menu, toggleButton, openIcon, closeIcon);
	});
});
