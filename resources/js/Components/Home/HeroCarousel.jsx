import { useState, useEffect, useCallback } from 'react';

/* Renderiza el carrusel hero con autoplay y navegación manual.
   Las imágenes vienen de la tabla cms_banner (gestionadas desde /admin/banners). */
export default function HeroCarousel({ banners = [] }) {
    const activeSlides = banners;
    const [current, setCurrent] = useState(0);
    const [lock, setLock] = useState(false);
    const [hover, setHover] = useState(false);

    /* Navega a un slide específico con bloqueo antispam. */
    const go = useCallback((i) => {
        if (lock) return;
        setLock(true);
        setCurrent(i);
        setTimeout(() => setLock(false), 500);
    }, [lock]);

    const next = useCallback(() => go((current + 1) % activeSlides.length), [current, go, activeSlides.length]);
    const prev = useCallback(() => go((current - 1 + activeSlides.length) % activeSlides.length), [current, go, activeSlides.length]);

    /* Autoplay del carrusel, se pausa al hacer hover. */
    useEffect(() => {
        if (hover) return;
        const t = setInterval(next, 4500);
        return () => clearInterval(t);
    }, [next, hover]);

    return (
        <section
            className="efe-hero-carousel"
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
        >
            <div
                className="efe-hero-track"
                style={{ transform: `translateX(-${current * 100}%)` }}
            >
                {activeSlides.map((s, idx) => (
                    <div key={s.id || idx} className="efe-hero-slide">
                        {s.link_url ? (
                            <a href={s.link_url} style={{ display: 'block', width: '100%', height: '100%' }}>
                                <img src={s.imagen_url || s.image} alt={s.titulo || s.alt || 'Banner'} draggable="false" />
                            </a>
                        ) : (
                            <img src={s.imagen_url || s.image} alt={s.titulo || s.alt || 'Banner'} draggable="false" />
                        )}
                    </div>
                ))}
            </div>

            <button className="efe-hero-arrow efe-hero-arrow--prev" onClick={prev} aria-label="Anterior">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                    <path d="m15 18-6-6 6-6" />
                </svg>
            </button>

            <button className="efe-hero-arrow efe-hero-arrow--next" onClick={next} aria-label="Siguiente">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>

            <div className="efe-hero-dots">
                {activeSlides.map((_, i) => (
                    <button
                        key={i}
                        className={`efe-hero-dot ${i === current ? 'is-active' : ''}`}
                        onClick={() => go(i)}
                        aria-label={`Slide ${i + 1}`}
                    />
                ))}
            </div>
        </section>
    );
}
