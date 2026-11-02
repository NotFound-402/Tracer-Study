document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('menuButton');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !menuButton) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        menuButton.setAttribute('aria-expanded', 'false');
        overlay?.classList.add('hidden');
    };

    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        menuButton.setAttribute('aria-expanded', 'true');
        overlay?.classList.remove('hidden');
    };

    menuButton.addEventListener('click', () => {
        const isOpen = !sidebar.classList.contains('-translate-x-full');
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay?.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    const mediaQuery = window.matchMedia('(min-width: 1024px)');
    const syncSidebarState = () => {
        if (mediaQuery.matches) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay?.classList.add('hidden');
            menuButton.setAttribute('aria-expanded', 'true');
            return;
        }

        closeSidebar();
    };

    if (typeof mediaQuery.addEventListener === 'function') {
        mediaQuery.addEventListener('change', syncSidebarState);
    } else if (typeof mediaQuery.addListener === 'function') {
        mediaQuery.addListener(syncSidebarState);
    }

    syncSidebarState();

    const searchInput = document.getElementById('searchInput');
    searchInput?.addEventListener('input', function () {
        const keyword = this.value.toLowerCase();
        document.querySelectorAll('.response-row').forEach((row) => {
            const text = row.textContent.toLowerCase();
            row.hidden = !text.includes(keyword);
        });
    });
});