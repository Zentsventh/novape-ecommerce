import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';

export default function BannersIndex({ banners, logoUrl }) {
    const [showModal, setShowModal] = useState(false);
    const [editingBanner, setEditingBanner] = useState(null);

    const { data, setData, post, processing, reset, clearErrors } = useForm({
        titulo: '', 
        subtitulo: '', 
        imagen: null, 
        enlace_url: '', 
        posicion: 'hero', 
        fecha_inicio: '', 
        fecha_fin: ''
    });

    const openCreateModal = () => {
        setEditingBanner(null);
        reset();
        clearErrors();
        setShowModal(true);
    };

    const openEditModal = (banner) => {
        setEditingBanner(banner);
        setData({
            titulo: banner.titulo || '',
            subtitulo: banner.subtitulo || '',
            imagen: null, // Reset file input when editing, only send if they want to replace it
            enlace_url: banner.enlace_url || '',
            posicion: banner.posicion || 'hero',
            fecha_inicio: banner.fecha_inicio || '',
            fecha_fin: banner.fecha_fin || ''
        });
        clearErrors();
        setShowModal(true);
    };

    const submit = (e) => {
        e.preventDefault();
        
        if (editingBanner) {
            post(`/admin/banners/${editingBanner.id}`, {
                onSuccess: () => { 
                    setShowModal(false); 
                    reset(); 
                    setEditingBanner(null);
                }
            });
        } else {
            post('/admin/banners', { 
                onSuccess: () => { 
                    setShowModal(false); 
                    reset(); 
                } 
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('¿Estás seguro de que deseas eliminar este banner permanentemente?')) {
            router.delete(`/admin/banners/${id}`);
        }
    };

    const toggleActivo = (id, currentStatus) => {
        router.post(`/admin/banners/${id}`, { activo: !currentStatus }, { preserveScroll: true });
    };

    const handleImageError = (e) => {
        e.target.style.display = 'none';
        e.target.nextSibling.style.display = 'flex';
    };

    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title="Gestión de Banners" />

            {/* Header Section */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px', background: 'var(--admin-card-bg)', padding: '24px', borderRadius: '16px', boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.05)' }}>
                <div>
                    <h1 style={{ fontSize: '28px', margin: 0, fontWeight: '800', color: 'var(--admin-text-main)', letterSpacing: '-0.5px' }}>
                        Banners Publicitarios
                    </h1>
                    <p style={{ color: 'var(--admin-text-secondary)', margin: '8px 0 0 0', fontSize: '15px' }}>
                        Gestiona las campañas visuales, hero banners y promociones de la tienda.
                    </p>
                </div>
                <button 
                    onClick={openCreateModal} 
                    style={{ 
                        background: 'linear-gradient(135deg, var(--admin-primary) 0%, #2563EB 100%)', 
                        color: 'white', 
                        padding: '12px 24px', 
                        borderRadius: '12px', 
                        border: 'none', 
                        cursor: 'pointer', 
                        fontWeight: '700',
                        fontSize: '14px',
                        boxShadow: '0 4px 12px rgba(37, 99, 235, 0.3)',
                        transition: 'transform 0.2s, box-shadow 0.2s',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px'
                    }}
                    onMouseOver={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 6px 16px rgba(37, 99, 235, 0.4)'; }}
                    onMouseOut={e => { e.currentTarget.style.transform = 'translateY(0)'; e.currentTarget.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.3)'; }}
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Nuevo Banner
                </button>
            </div>

            {/* Banners List */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '20px' }}>
                {banners.map(b => {
                    const now = new Date();
                    const inicio = b.fecha_inicio ? new Date(b.fecha_inicio.replace(' ', 'T')) : null;
                    const fin = b.fecha_fin ? new Date(b.fecha_fin.replace(' ', 'T')) : null;
                    
                    let estadoLabel = 'Público';
                    let estadoColor = '#059669';
                    let estadoBg = '#ECFDF5';
                    let estadoBorder = '#A7F3D0';

                    if (!b.activo) {
                        estadoLabel = 'Oculto';
                        estadoColor = '#DC2626';
                        estadoBg = '#FEF2F2';
                        estadoBorder = '#FECACA';
                    } else if (inicio && inicio > now) {
                        estadoLabel = 'Programado';
                        estadoColor = '#D97706';
                        estadoBg = '#FFFBEB';
                        estadoBorder = '#FDE68A';
                    } else if (fin && fin < now) {
                        estadoLabel = 'Expirado';
                        estadoColor = '#4B5563';
                        estadoBg = '#F3F4F6';
                        estadoBorder = '#E5E7EB';
                    }

                    return (
                    <div key={b.id} style={{ 
                        background: 'var(--admin-card-bg)', 
                        borderRadius: '16px', 
                        padding: '24px', 
                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                        display: 'grid',
                        gridTemplateColumns: '180px 1fr 150px 180px',
                        gap: '24px',
                        alignItems: 'center',
                        border: '1px solid var(--admin-border-light)',
                        transition: 'box-shadow 0.2s',
                        position: 'relative',
                        overflow: 'hidden'
                    }}
                    onMouseOver={e => e.currentTarget.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.08)'}
                    onMouseOut={e => e.currentTarget.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.05)'}
                    >
                        {/* Status Indicator Bar */}
                        <div style={{ position: 'absolute', left: 0, top: 0, bottom: 0, width: '4px', background: estadoColor }}></div>

                        {/* Preview Image */}
                        <div style={{ width: '180px', height: '100px', background: '#F3F4F6', borderRadius: '12px', overflow: 'hidden', position: 'relative', boxShadow: 'inset 0 2px 4px rgba(0,0,0,0.05)' }}>
                            {b.imagen_url ? (
                                <>
                                    <img src={b.imagen_url.startsWith('/') ? b.imagen_url : '/' + b.imagen_url} alt={b.titulo} onError={handleImageError} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                    <div style={{ display: 'none', position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', alignItems: 'center', justifyContent: 'center', color: '#6B7280', fontSize: '12px', fontWeight: 'bold', background: '#F3F4F6', flexDirection: 'column', gap: '5px' }}>
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                        Imágen Rota
                                    </div>
                                </>
                            ) : (
                                <div style={{ width: '100%', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#9ca3af', fontSize: '12px', fontWeight: 'bold' }}>
                                    N/A
                                </div>
                            )}
                        </div>

                        {/* Title & Info */}
                        <div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
                                <h3 style={{ margin: 0, fontSize: '18px', fontWeight: '800', color: 'var(--admin-text-main)' }}>{b.titulo}</h3>
                                <span style={{ 
                                    background: b.posicion === 'hero' ? '#EFF6FF' : '#F3F4F6', 
                                    color: b.posicion === 'hero' ? '#2563EB' : '#4B5563', 
                                    padding: '4px 10px', 
                                    borderRadius: '20px', 
                                    fontSize: '11px', 
                                    fontWeight: '800',
                                    letterSpacing: '0.5px'
                                }}>
                                    {b.posicion.toUpperCase()}
                                </span>
                            </div>
                            <p style={{ fontSize: '14px', color: 'var(--admin-text-secondary)', margin: '0 0 8px 0' }}>{b.subtitulo || 'Sin descripción adicional'}</p>
                        </div>

                        {/* Status & Dates */}
                        <div>
                            <div style={{ fontSize: '12px', color: 'var(--admin-text-muted)', fontWeight: 'bold', marginBottom: '6px' }}>ESTADO Y VIGENCIA</div>
                            <button 
                                onClick={() => toggleActivo(b.id, b.activo)}
                                style={{ 
                                    background: estadoBg, 
                                    color: estadoColor, 
                                    padding: '6px 12px', 
                                    borderRadius: '8px', 
                                    fontSize: '12px', 
                                    fontWeight: '800', 
                                    border: `1px solid ${estadoBorder}`,
                                    cursor: 'pointer',
                                    display: 'inline-block',
                                    marginBottom: '8px',
                                    transition: 'all 0.2s'
                                }}
                            >
                                <span style={{ display: 'inline-block', width: '6px', height: '6px', borderRadius: '50%', background: estadoColor, marginRight: '6px' }}></span>
                                {estadoLabel}
                            </button>
                            <div style={{ fontSize: '12px', color: 'var(--admin-text-secondary)', display: 'flex', alignItems: 'center', gap: '4px' }}>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                {b.fecha_inicio ? `${b.fecha_inicio} al ${b.fecha_fin || 'Siempre'}` : 'Permanente'}
                            </div>
                        </div>

                        {/* Actions */}
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                            <button onClick={() => openEditModal(b)} style={{ 
                                width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'white', color: 'var(--admin-text-main)', fontWeight: '700', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', transition: 'background 0.2s' 
                            }} onMouseOver={e => e.currentTarget.style.background = '#F9FAFB'} onMouseOut={e => e.currentTarget.style.background = 'white'}>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                Editar Banner
                            </button>
                            <button onClick={() => handleDelete(b.id)} style={{ 
                                width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid #FECACA', background: '#FEF2F2', color: '#DC2626', fontWeight: '700', cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '6px', transition: 'background 0.2s' 
                            }} onMouseOver={e => e.currentTarget.style.background = '#FEE2E2'} onMouseOut={e => e.currentTarget.style.background = '#FEF2F2'}>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                Eliminar
                            </button>
                        </div>
                    </div>
                )})}
            </div>

            {/* Modal de Creación / Edición */}
            {showModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(15, 23, 42, 0.6)', backdropFilter: 'blur(4px)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
                    <div style={{ background: 'white', padding: '32px', borderRadius: '20px', width: '550px', boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)', border: '1px solid rgba(255,255,255,0.2)' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                            <h2 style={{ margin: 0, fontSize: '22px', fontWeight: '800', color: 'var(--admin-text-main)' }}>
                                {editingBanner ? 'Editar Banner' : 'Nuevo Banner Promocional'}
                            </h2>
                            <button type="button" onClick={() => setShowModal(false)} style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#9CA3AF' }}>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </div>
                        
                        <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '6px', fontWeight: '700', fontSize: '13px', color: '#374151' }}>TÍTULO PRINCIPAL</label>
                                <input type="text" value={data.titulo} onChange={e => setData('titulo', e.target.value)} style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid #D1D5DB', fontSize: '14px', outline: 'none', transition: 'border-color 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#D1D5DB'} required placeholder="Ej: Gran Venta de Verano" />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '6px', fontWeight: '700', fontSize: '13px', color: '#374151' }}>SUBTÍTULO / DESCRIPCIÓN (Opcional)</label>
                                <input type="text" value={data.subtitulo} onChange={e => setData('subtitulo', e.target.value)} style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid #D1D5DB', fontSize: '14px', outline: 'none' }} placeholder="Ej: Hasta 50% de descuento en seleccionados" />
                            </div>
                            
                            <div style={{ background: '#F8FAFC', padding: '16px', borderRadius: '12px', border: '1px dashed #CBD5E1' }}>
                                <label style={{ display: 'block', marginBottom: '4px', fontWeight: '700', fontSize: '13px', color: '#374151' }}>
                                    IMAGEN DEL BANNER {editingBanner && '(Nueva imagen opcional)'}
                                </label>
                                <div style={{ fontSize: '12px', color: '#6B7280', marginBottom: '12px' }}>
                                    Recomendado: 1200x400px. Máx 2MB (JPG, PNG, WEBP).
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/png, image/jpeg, image/webp" 
                                    onChange={e => setData('imagen', e.target.files[0])} 
                                    style={{ width: '100%', padding: '8px', background: 'white', borderRadius: '8px', border: '1px solid #CBD5E1', fontSize: '14px' }} 
                                    required={!editingBanner} 
                                />
                            </div>

                            <div>
                                <label style={{ display: 'block', marginBottom: '6px', fontWeight: '700', fontSize: '13px', color: '#374151' }}>ENLACE DESTINO (Opcional)</label>
                                <input type="text" value={data.enlace_url} onChange={e => setData('enlace_url', e.target.value)} style={{ width: '100%', padding: '12px', borderRadius: '10px', border: '1px solid #D1D5DB', fontSize: '14px', outline: 'none' }} placeholder="Ej: /catalogo" />
                            </div>
                            
                            <div style={{ background: '#F9FAFB', padding: '16px', borderRadius: '12px', border: '1px solid #E5E7EB' }}>
                                <label style={{ display: 'block', marginBottom: '12px', fontWeight: '800', fontSize: '13px', color: '#374151' }}>PROGRAMACIÓN DE CAMPAÑA</label>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px' }}>
                                    <div>
                                        <label style={{ display: 'block', marginBottom: '4px', fontWeight: '600', fontSize: '12px', color: '#6B7280' }}>Inicia el:</label>
                                        <input type="datetime-local" min={new Date().toISOString().split('T')[0] + 'T00:00'} value={data.fecha_inicio} onChange={e => setData('fecha_inicio', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid #D1D5DB', fontSize: '13px' }} />
                                    </div>
                                    <div>
                                        <label style={{ display: 'block', marginBottom: '4px', fontWeight: '600', fontSize: '12px', color: '#6B7280' }}>Finaliza el:</label>
                                        <input type="datetime-local" value={data.fecha_fin} onChange={e => setData('fecha_fin', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid #D1D5DB', fontSize: '13px' }} />
                                    </div>
                                </div>
                            </div>

                            <div style={{ display: 'flex', gap: '12px', marginTop: '16px' }}>
                                <button type="button" onClick={() => setShowModal(false)} style={{ flex: 1, padding: '14px', borderRadius: '10px', border: '1px solid #D1D5DB', background: 'white', cursor: 'pointer', fontWeight: '700', color: '#374151', transition: 'background 0.2s' }} onMouseOver={e => e.currentTarget.style.background = '#F3F4F6'} onMouseOut={e => e.currentTarget.style.background = 'white'}>
                                    Cancelar
                                </button>
                                <button type="submit" disabled={processing} style={{ flex: 2, padding: '14px', borderRadius: '10px', border: 'none', background: 'linear-gradient(135deg, var(--admin-primary) 0%, #2563EB 100%)', color: 'white', fontWeight: '800', cursor: 'pointer', boxShadow: '0 4px 12px rgba(37, 99, 235, 0.3)', transition: 'transform 0.2s', fontSize: '15px' }} onMouseOver={e => e.currentTarget.style.transform = 'translateY(-2px)'} onMouseOut={e => e.currentTarget.style.transform = 'translateY(0)'}>
                                    {processing ? 'Guardando...' : (editingBanner ? 'Guardar Cambios' : 'Publicar Banner')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
