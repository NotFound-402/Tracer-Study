
const menuToggle = document.getElementById('menuToggle');
const navMenu = document.getElementById('navMenu');

if (menuToggle && navMenu) {
    menuToggle.addEventListener('click', () => {
        const isOpen = navMenu.classList.toggle('open');

        menuToggle.setAttribute('aria-expanded', String(isOpen));
        menuToggle.setAttribute(
            'aria-label',
            isOpen ? 'Tutup menu' : 'Buka menu'
        );
        menuToggle.textContent = isOpen ? '✕' : '☰';
    });

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('open');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'Buka menu');
            menuToggle.textContent = '☰';
        });
    });
}

const revealItems = document.querySelectorAll('.reveal');

if (revealItems.length) {
    const revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15 }
    );

    revealItems.forEach((item) => revealObserver.observe(item));
}

const counters = document.querySelectorAll('.count-up');

const animateCounter = (element) => {
    const target = Number(element.dataset.target || 0);
    const duration = 1200;
    const start = performance.now();

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(target * eased);
        element.textContent = current.toLocaleString('id-ID');

        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            element.textContent = target.toLocaleString('id-ID');
        }
    };

    requestAnimationFrame(tick);
};

if (counters.length) {
    const counterObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach((counter) => counterObserver.observe(counter));
}
