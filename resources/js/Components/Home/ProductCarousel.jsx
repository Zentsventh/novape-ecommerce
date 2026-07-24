import { useState, useRef, useEffect, useCallback } from 'react';
import ProductCard from './ProductCard';
import { useDeviceContext } from '@/Contexts/DeviceContext';

/* Renderiza un carrusel horizontal de productos con paginación. */
export default function ProductCarousel({ products }) {
    const [currentPage, setCurrentPage] = useState(0);
    const containerRef = useRef(null);
    const { screenWidth, isTouch } = useDeviceContext();

    const itemsPerPage = screenWidth < 480 ? 1 : screenWidth < 768 ? 2 : screenWidth < 1024 ? 3 : screenWidth < 1280 ? 4 : 5;
    const totalPages = Math.max(1, Math.ceil(products.length / itemsPerPage));
    const gap = 16;

    useEffect(() => {
        if (currentPage >= totalPages && totalPages > 0) {
            setCurrentPage(totalPages - 1);
        }
    }, [totalPages, currentPage]);

    const goToPage = useCallback((page) => {
        if (page < 0 || page >= totalPages) return;
        setCurrentPage(page);
    }, [totalPages]);

    /* Calcula el desplazamiento basado en el ancho real del contenedor (sin padding lateral). */
    const [contentWidth, setContentWidth] = useState(0);
    const arrowSpace = 40; /* px padding a cada lado para las flechas */

    useEffect(() => {
        const el = containerRef.current;
        if (!el) return;

        const measure = () => setContentWidth(el.clientWidth - arrowSpace * 2);
        measure();

        const observer = new ResizeObserver(measure);
        observer.observe(el);
        return () => observer.disconnect();
    }, []);

    const cardWidth = contentWidth > 0
        ? (contentWidth - gap * (itemsPerPage - 1)) / itemsPerPage
        : 260;

    const translateX = currentPage * (contentWidth + gap);

    const [touchStart, setTouchStart] = useState(0);
    const [touchEnd, setTouchEnd] = useState(0);

    const handleTouchStart = (e) => {
        setTouchStart(e.targetTouches[0].clientX);
    };

    const handleTouchMove = (e) => {
        setTouchEnd(e.targetTouches[0].clientX);
    };

    const handleTouchEnd = () => {
        if (!touchStart || !touchEnd) return;
        const distance = touchStart - touchEnd;
        const isLeftSwipe = distance > 50;
        const isRightSwipe = distance < -50;
        
        if (isLeftSwipe && currentPage < totalPages - 1) {
            goToPage(currentPage + 1);
        }
        if (isRightSwipe && currentPage > 0) {
            goToPage(currentPage - 1);
        }
        
        setTouchStart(0);
        setTouchEnd(0);
    };

    return (
        <div 
            className="efe-section-carousel" 
            ref={containerRef}
            onTouchStart={handleTouchStart}
            onTouchMove={handleTouchMove}
            onTouchEnd={handleTouchEnd}
        >
            <div
                className="efe-carousel-track"
                style={{
                    transform: `translateX(-${translateX}px)`,
                    gap: `${gap}px`,
                }}
            >
                {products.map((prod) => (
                    <div key={prod.id} style={{ minWidth: `${cardWidth}px`, maxWidth: `${cardWidth}px` }}>
                        <ProductCard product={prod} />
                    </div>
                ))}
            </div>

            {totalPages > 1 && (
                <>
                    <button
                        className="efe-carousel-arrow efe-carousel-arrow--prev"
                        onClick={() => goToPage(currentPage - 1)}
                        disabled={currentPage === 0}
                        aria-label="Anterior"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </button>
                    <button
                        className="efe-carousel-arrow efe-carousel-arrow--next"
                        onClick={() => goToPage(currentPage + 1)}
                        disabled={currentPage === totalPages - 1}
                        aria-label="Siguiente"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </button>
                    <div className="efe-carousel-dots">
                        {Array.from({ length: totalPages }).map((_, i) => (
                            <button
                                key={i}
                                className={`efe-carousel-dot ${i === currentPage ? 'is-active' : ''}`}
                                onClick={() => goToPage(i)}
                                aria-label={`Página ${i + 1}`}
                            />
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}
