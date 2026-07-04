/* Nav scroll behavior */
    const nav = document.getElementById('nav');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 60);
    });

    /* Scroll reveal */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    /* Staggered children reveal for features grid */
    document.querySelectorAll('.feature-card, .step-card, .testi-card').forEach((el, i) => {
        el.style.transitionDelay = (i * 0.06) + 's';
    });