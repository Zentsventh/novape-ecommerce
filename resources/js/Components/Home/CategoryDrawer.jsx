import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

/**
 * CategoryDrawer: Panel lateral tipo Mega-Menú.
 * - Panel izquierdo: lista las categorías padre.
 * - Panel derecho: muestra las subcategorías de la categoría seleccionada.
 */
export default function CategoryDrawer({ isOpen, onClose, categorias = [] }) {
    const [activeId, setActiveId] = useState(null);
    const { auth } = usePage().props;
    const user = auth?.user;
    const activeCat = categorias.find(c => c.id === activeId);

    const handleCatHover = (id) => {
        setActiveId(id);
    };

    const handleSubClick = (subNombre) => {
        router.get('/catalogo', { categoria: activeCat.nombre, subcategoria: subNombre }, {
            preserveScroll: true,
            onFinish: onClose
        });
    };

    const handleVerTodo = (catNombre) => {
        router.get('/catalogo', { categoria: catNombre }, {
            preserveScroll: true,
            onFinish: onClose
        });
    };

    return (
        <>
            {/* Overlay */}
            <div 
                className={`efe-cat-drawer-overlay ${isOpen ? 'is-open' : ''}`}
                onClick={onClose}
            />

            {/* Drawer */}
            <div className={`efe-cat-drawer ${isOpen ? 'is-open' : ''}`}>
                {/* Panel Izquierdo - Categorías Padre */}
                <div className="efe-cat-drawer-left">
                    <div className="efe-cat-drawer-header">
                        <h3>{user?.nombres ? `¡Hola, ${user.nombres.split(' ')[0]}!` : '¡Hola!'}</h3>
                        <button className="efe-cat-drawer-close" onClick={onClose}>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    <div className="efe-cat-drawer-list">
                        {categorias.map(cat => (
                            <button
                                key={cat.id}
                                className={`efe-cat-drawer-item ${activeId === cat.id ? 'is-active' : ''}`}
                                onMouseEnter={() => handleCatHover(cat.id)}
                                onFocus={() => handleCatHover(cat.id)}
                                onClick={() => handleVerTodo(cat.nombre)}
                            >
                                {cat.nombre}
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                    <polyline points="9 18 15 12 9 6" />
                                </svg>
                            </button>
                        ))}
                    </div>
                </div>

                {/* Panel Derecho - Subcategorías */}
                <div className="efe-cat-drawer-right">
                    {activeCat ? (
                        <>
                            <div className="efe-cat-drawer-sub-header">
                                <h4 className="efe-cat-drawer-sub-title">{activeCat.nombre}</h4>

                            </div>
                            <div className="efe-cat-drawer-sub-list">
                                {activeCat.subcategorias && activeCat.subcategorias.length > 0 ? (
                                    activeCat.subcategorias.map(sub => (
                                        <button 
                                            key={sub.id}
                                            className="efe-cat-drawer-sub-item"
                                            type="button"
                                            onClick={() => handleSubClick(sub.nombre)}
                                        >
                                            {sub.nombre}
                                        </button>
                                    ))
                                ) : (
                                    <div className="efe-cat-drawer-empty efe-cat-drawer-empty-grid">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" strokeWidth="1.5">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                        </svg>
                                        Sin subcategorías
                                    </div>
                                )}
                            </div>
                        </>
                    ) : (
                        <div className="efe-cat-drawer-empty">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" strokeWidth="1.5">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                            <span>Pasa el cursor por una categoría<br/>para ver sus subcategorías</span>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
