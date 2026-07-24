import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useDeviceContext } from '@/Contexts/DeviceContext';

/**
 * CategoryDrawer: Panel lateral tipo Mega-Menú.
 * - Panel izquierdo: lista las categorías padre.
 * - Panel derecho: muestra las subcategorías de la categoría seleccionada.
 */
export default function CategoryDrawer({ isOpen, onClose, categorias = [] }) {
    const [activeId, setActiveId] = useState(null);
    const { auth } = usePage().props;
    const { isMobile } = useDeviceContext();
    const user = auth?.user;
    const activeCat = categorias.find((c) => c.id === activeId);

    const handleCatHover = (id) => {
        if (!isMobile) setActiveId(id);
    };

    const handleCatClick = (cat) => {
        if (isMobile) {
            setActiveId(cat.id);
        } else {
            handleVerTodo(cat.nombre);
        }
    };

    const handleSubClick = (subNombre) => {
        router.get(
            '/catalogo',
            { categoria: activeCat.nombre, subcategoria: subNombre },
            {
                preserveScroll: true,
                onFinish: onClose,
            }
        );
    };

    const handleVerTodo = (catNombre) => {
        router.get(
            '/catalogo',
            { categoria: catNombre },
            {
                preserveScroll: true,
                onFinish: onClose,
            }
        );
    };

    const handleBack = () => {
        setActiveId(null);
    };

    return (
        <>
            {/* Overlay */}
            <div
                className={`efe-cat-drawer-overlay ${isOpen ? 'is-open' : ''}`}
                onClick={onClose}
            />

            {/* Drawer */}
            <div
                className={`efe-cat-drawer ${isOpen ? 'is-open' : ''} ${isMobile && activeId ? 'show-right' : ''}`}
            >
                {/* Panel Izquierdo - Categorías Padre */}
                <div className="efe-cat-drawer-left">
                    <div className="efe-cat-drawer-header">
                        <h3>
                            {user?.nombres ? `¡Hola, ${user.nombres.split(' ')[0]}!` : '¡Hola!'}
                        </h3>
                        <button className="efe-cat-drawer-close" onClick={onClose}>
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                            >
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                    </div>

                    <div className="efe-cat-drawer-list">
                        {categorias.map((cat) => (
                            <button
                                key={cat.id}
                                className={`efe-cat-drawer-item ${activeId === cat.id ? 'is-active' : ''}`}
                                onMouseEnter={() => handleCatHover(cat.id)}
                                onClick={() => handleCatClick(cat)}
                            >
                                {cat.nombre}
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2.5"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                >
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
                            <div
                                className="efe-cat-drawer-sub-header"
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: '15px',
                                    marginBottom: '20px',
                                    borderBottom: '2px solid #f1f5f9',
                                    paddingBottom: '15px',
                                }}
                            >
                                {isMobile && (
                                    <button
                                        onClick={handleBack}
                                        style={{
                                            alignSelf: 'flex-start',
                                            background: 'transparent',
                                            border: 'none',
                                            color: '#64748b',
                                            fontSize: '14px',
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: '5px',
                                            cursor: 'pointer',
                                            padding: 0,
                                            fontWeight: 'bold',
                                        }}
                                    >
                                        <svg
                                            width="16"
                                            height="16"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2.5"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        >
                                            <line x1="19" y1="12" x2="5" y2="12" />
                                            <polyline points="12 19 5 12 12 5" />
                                        </svg>
                                        Volver
                                    </button>
                                )}

                                <div
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'center',
                                        width: '100%',
                                    }}
                                >
                                    <h4
                                        style={{
                                            fontSize: '20px',
                                            fontWeight: '900',
                                            color: '#0f172a',
                                            textTransform: 'uppercase',
                                            position: 'relative',
                                            margin: 0,
                                            letterSpacing: '-0.5px',
                                        }}
                                    >
                                        {activeCat.nombre}
                                        <span
                                            style={{
                                                position: 'absolute',
                                                bottom: '-17px',
                                                left: 0,
                                                width: '40px',
                                                height: '4px',
                                                background: '#00B4FF',
                                                borderRadius: '4px',
                                            }}
                                        ></span>
                                    </h4>
                                    <button
                                        onClick={() => handleVerTodo(activeCat.nombre)}
                                        style={{
                                            fontSize: '12px',
                                            fontWeight: 'bold',
                                            color: '#00B4FF',
                                            background: 'rgba(0,180,255,0.1)',
                                            padding: '6px 14px',
                                            borderRadius: '20px',
                                            border: 'none',
                                            cursor: 'pointer',
                                            transition: 'all 0.2s',
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: '6px',
                                        }}
                                    >
                                        Ver todo
                                    </button>
                                </div>
                            </div>

                            <div
                                className="efe-cat-drawer-sub-list"
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: 'repeat(auto-fill, minmax(140px, 1fr))',
                                    gap: '12px',
                                    marginTop: '10px',
                                    overflowY: 'auto',
                                    paddingBottom: '20px',
                                }}
                            >
                                {activeCat.subcategorias && activeCat.subcategorias.length > 0 ? (
                                    activeCat.subcategorias.map((sub) => (
                                        <button
                                            key={sub.id}
                                            className="efe-cat-drawer-sub-item-link"
                                            type="button"
                                            onClick={() => handleSubClick(sub.nombre)}
                                            style={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'space-between',
                                                background: '#f8fafc',
                                                border: '1px solid #e2e8f0',
                                                borderRadius: '10px',
                                                textAlign: 'left',
                                                padding: '10px 12px',
                                                fontSize: '12px',
                                                fontWeight: '600',
                                                color: '#334155',
                                                cursor: 'pointer',
                                                width: '100%',
                                                transition: 'all 0.2s ease',
                                                wordBreak: 'break-word',
                                            }}
                                        >
                                            <span>{sub.nombre}</span>
                                            <svg
                                                width="14"
                                                height="14"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="#94a3b8"
                                                strokeWidth="2.5"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                style={{ flexShrink: 0 }}
                                            >
                                                <polyline points="9 18 15 12 9 6" />
                                            </svg>
                                        </button>
                                    ))
                                ) : (
                                    <div
                                        style={{
                                            gridColumn: '1 / -1',
                                            background: '#f8fafc',
                                            borderRadius: '12px',
                                            padding: '30px',
                                            border: '1px dashed #cbd5e1',
                                            textAlign: 'center',
                                        }}
                                    >
                                        <div
                                            style={{
                                                color: '#64748b',
                                                fontWeight: '500',
                                                fontSize: '13px',
                                            }}
                                        >
                                            Sin subcategorías
                                        </div>
                                    </div>
                                )}
                            </div>
                        </>
                    ) : (
                        <div
                            className="efe-cat-drawer-empty"
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                height: '100%',
                                color: '#9ca3af',
                                gap: '12px',
                                fontSize: '14px',
                                textAlign: 'center',
                                padding: '40px',
                            }}
                        >
                            <svg
                                width="48"
                                height="48"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#d1d5db"
                                strokeWidth="1.5"
                            >
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg>
                            <span>
                                Pasa el cursor por una categoría
                                <br />
                                para ver sus subcategorías
                            </span>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
