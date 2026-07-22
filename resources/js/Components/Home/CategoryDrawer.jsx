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
                            <div className="efe-cat-drawer-sub-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px', borderBottom: '2px solid #f1f5f9', paddingBottom: '15px' }}>
                                <h4 style={{ fontSize: '22px', fontWeight: '900', color: '#0f172a', textTransform: 'uppercase', position: 'relative', margin: 0, letterSpacing: '-0.5px' }}>
                                    {activeCat.nombre}
                                    <span style={{ position: 'absolute', bottom: '-17px', left: 0, width: '40px', height: '4px', background: '#00B4FF', borderRadius: '4px' }}></span>
                                </h4>
                                <button 
                                    onClick={() => handleVerTodo(activeCat.nombre)} 
                                    style={{ fontSize: '13px', fontWeight: 'bold', color: '#00B4FF', background: 'rgba(0,180,255,0.1)', padding: '8px 18px', borderRadius: '20px', border: 'none', cursor: 'pointer', transition: 'all 0.2s', display: 'flex', alignItems: 'center', gap: '6px' }} 
                                    onMouseEnter={(e)=>{e.currentTarget.style.background='#00B4FF'; e.currentTarget.style.color='white';}} 
                                    onMouseLeave={(e)=>{e.currentTarget.style.background='rgba(0,180,255,0.1)'; e.currentTarget.style.color='#00B4FF';}}
                                >
                                    Ver todo
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                        <polyline points="12 5 19 12 12 19" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div className="efe-cat-drawer-sub-list" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))', gap: '16px', marginTop: '20px', overflowY: 'auto', paddingBottom: '20px' }}>
                                {activeCat.subcategorias && activeCat.subcategorias.length > 0 ? (
                                    activeCat.subcategorias.map(sub => (
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
                                                padding: '12px 14px', 
                                                fontSize: '13px', 
                                                fontWeight: '600',
                                                color: '#334155', 
                                                cursor: 'pointer',
                                                width: '100%',
                                                transition: 'all 0.2s ease',
                                                boxShadow: '0 2px 4px rgba(0,0,0,0.02)',
                                                wordBreak: 'break-word'
                                            }}
                                            onMouseEnter={(e) => { 
                                                e.currentTarget.style.borderColor = '#00B4FF'; 
                                                e.currentTarget.style.color = '#00B4FF'; 
                                                e.currentTarget.style.background = '#f0f9ff'; 
                                                e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,180,255,0.15)';
                                                e.currentTarget.querySelector('svg').style.transform = 'translateX(4px)'; 
                                                e.currentTarget.querySelector('svg').style.stroke = '#00B4FF'; 
                                            }}
                                            onMouseLeave={(e) => { 
                                                e.currentTarget.style.borderColor = '#e2e8f0'; 
                                                e.currentTarget.style.color = '#334155'; 
                                                e.currentTarget.style.background = '#f8fafc'; 
                                                e.currentTarget.style.boxShadow = '0 2px 4px rgba(0,0,0,0.02)';
                                                e.currentTarget.querySelector('svg').style.transform = 'translateX(0)'; 
                                                e.currentTarget.querySelector('svg').style.stroke = '#94a3b8'; 
                                            }}
                                        >
                                            <span style={{ lineHeight: '1.2' }}>{sub.nombre}</span>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{ transition: 'all 0.2s ease', flexShrink: 0 }}>
                                                <polyline points="9 18 15 12 9 6" />
                                            </svg>
                                        </button>
                                    ))
                                ) : (
                                    <div className="efe-cat-drawer-empty efe-cat-drawer-empty-grid" style={{ gridColumn: '1 / -1', background: '#f8fafc', borderRadius: '12px', padding: '40px', border: '1px dashed #cbd5e1' }}>
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="1.5" style={{ marginBottom: '10px' }}>
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                        </svg>
                                        <div style={{ color: '#64748b', fontWeight: '500' }}>Sin subcategorías</div>
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
