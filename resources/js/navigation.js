function initGuestNavigation() {
    const navLinks = Array.from(document.querySelectorAll('.nav-link[href^="#"]'));
    const sections = Array.from(document.querySelectorAll('section[id]'));

    if (!navLinks.length || !sections.length) {
        return;
    }

    const setActiveLink = (currentId) => {
        navLinks.forEach((link) => {
            link.classList.toggle('active', link.getAttribute('href') === `#${currentId}`);
        });
    };

    const syncHash = (currentId) => {
        const baseUrl = `${window.location.pathname}${window.location.search}`;
        const nextUrl = currentId === 'home' ? baseUrl : `${baseUrl}#${currentId}`;
        const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;

        if (currentUrl !== nextUrl) {
            history.replaceState(null, '', nextUrl);
        }
    };

    let currentSectionId = window.location.hash ? window.location.hash.slice(1) : 'home';
    setActiveLink(currentSectionId);

    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            const target = link.getAttribute('href');
            if (!target || target === '#' || target === '#!') {
                return;
            }

            currentSectionId = target.slice(1);
            setActiveLink(currentSectionId);
            window.closeGuestNavbar?.();
        });
    });

    const observer = new IntersectionObserver((entries) => {
        const nextEntry = entries
            .filter((entry) => entry.isIntersecting)
            .sort((left, right) => {
                if (right.intersectionRatio !== left.intersectionRatio) {
                    return right.intersectionRatio - left.intersectionRatio;
                }

                return left.boundingClientRect.top - right.boundingClientRect.top;
            })[0];

        if (!nextEntry) {
            return;
        }

        const nextId = nextEntry.target.getAttribute('id');
        if (!nextId || nextId === currentSectionId) {
            return;
        }

        currentSectionId = nextId;
        setActiveLink(currentSectionId);
        syncHash(currentSectionId);
    }, {
        root: null,
        rootMargin: '-20% 0px -55% 0px',
        threshold: [0.1, 0.35, 0.6],
    });

    sections.forEach((section) => observer.observe(section));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGuestNavigation);
} else {
    initGuestNavigation();
}
