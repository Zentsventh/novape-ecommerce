import React, { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { Plus, Edit, Trash2, Tag, Copy, Check, X } from 'lucide-react';

export default function Index({ cupones }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [currentCupon, setCurrentCupon] = useState(null);
    const [copiedCode, setCopiedCode] = useState(null);
    const [deleteConfirm, setDeleteConfirm] = useState(null);

    const [form, setForm] = useState({
        codigo: '',
        tipo: 'porcentaje',
        valor: '',
        monto_minimo: '',
        fecha_inicio: '',
        fecha_fin: '',
        limite_usos: '',
        activo: true,
        unico_por_cliente: true
    });

    const openCreateModal = () => {
        setForm({
            codigo: '',
            tipo: 'porcentaje',
            valor: '',
            monto_minimo: '',
            fecha_inicio: '',
            fecha_fin: '',
            limite_usos: '',
            activo: true
        });
        setIsEditing(false);
        setIsModalOpen(true);
    };

    const openEditModal = (cupon) => {
        setForm({
            codigo: cupon.codigo,
            tipo: cupon.tipo,
            valor: cupon.valor,
            monto_minimo: cupon.monto_minimo || '',
            fecha_inicio: cupon.fecha_inicio ? cupon.fecha_inicio.substring(0, 16) : '',
            fecha_fin: cupon.fecha_fin ? cupon.fecha_fin.substring(0, 16) : '',
            limite_usos: cupon.limite_usos || '',
            activo: cupon.activo == 1,
            unico_por_cliente: cupon.unico_por_cliente == 1
        });
        setCurrentCupon(cupon);
        setIsEditing(true);
        setIsModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (isEditing) {
            router.put(`/admin/cupones/${currentCupon.id}`, form, {
                onSuccess: () => setIsModalOpen(false)
            });
        } else {
            router.post('/admin/cupones', form, {
                onSuccess: () => setIsModalOpen(false)
            });
        }
    };

    const confirmDelete = (id) => {
        setDeleteConfirm(id);
    };

    const executeDelete = () => {
        if (deleteConfirm) {
            router.delete(`/admin/cupones/${deleteConfirm}`, {
                onSuccess: () => setDeleteConfirm(null)
            });
        }
    };

    const handleCopy = (code) => {
        navigator.clipboard.writeText(code);
        setCopiedCode(code);
        setTimeout(() => setCopiedCode(null), 2000);
    };

    return (
        <AdminLayout>
            <Head title="Gestión de Cupones" />

            <div className="admin-content">
                <div className="admin-page-header">
                    <div>
                        <h1 className="admin-page-title">Cupones de Descuento</h1>
                        <p className="admin-page-subtitle">Crea y administra códigos promocionales para los clientes.</p>
                    </div>
                    <button onClick={openCreateModal} className="admin-btn-primary" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <Plus size={18} />
                        Nuevo Cupón
                    </button>
                </div>

                <div className="admin-table-container">
                    <table className="admin-table">
                        <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Usos</th>
                                    <th>Vigencia</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        <tbody>
                                {cupones.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" style={{ textAlign: 'center', padding: '24px', color: 'var(--admin-text-muted)' }}>
                                            No hay cupones registrados.
                                        </td>
                                    </tr>
                                ) : (
                                    cupones.map(cupon => {
                                        const now = new Date();
                                        const isExpired = cupon.fecha_fin && new Date(cupon.fecha_fin) < now;
                                        const rowStyle = isExpired ? { opacity: 0.6, filter: 'grayscale(100%)' } : {};

                                        return (
                                        <tr key={cupon.id} style={rowStyle}>
                                            <td>
                                                <div 
                                                    onClick={() => handleCopy(cupon.codigo)}
                                                    style={{ 
                                                        cursor: 'pointer',
                                                        display: 'inline-flex',
                                                        alignItems: 'center',
                                                        gap: '6px',
                                                        background: '#f4f7f6',
                                                        padding: '4px 10px',
                                                        borderRadius: '6px',
                                                        border: '1px dashed #c2c9d6',
                                                        color: '#111827',
                                                        fontWeight: '600',
                                                        transition: 'all 0.2s',
                                                        position: 'relative'
                                                    }}
                                                    title="Copiar cupón"
                                                    onMouseOver={(e) => { e.currentTarget.style.borderColor = 'var(--admin-primary)'; e.currentTarget.style.color = 'var(--admin-primary)'; }}
                                                    onMouseOut={(e) => { e.currentTarget.style.borderColor = '#c2c9d6'; e.currentTarget.style.color = '#111827'; }}
                                                >
                                                    {copiedCode === cupon.codigo ? <Check size={14} color="var(--admin-success)" /> : <Copy size={14} />}
                                                    {cupon.codigo}
                                                </div>
                                            </td>
                                            <td>
                                                <span className="admin-badge admin-badge-info">
                                                    {cupon.tipo === 'porcentaje' ? 'Porcentaje' : 'Monto Fijo'}
                                                </span>
                                            </td>
                                            <td style={{ fontWeight: '700', color: 'var(--admin-success)' }}>
                                                {cupon.tipo === 'porcentaje' ? `${parseFloat(cupon.valor)}%` : `S/ ${parseFloat(cupon.valor).toFixed(2)}`}
                                            </td>
                                            <td style={{ color: 'var(--admin-text-secondary)', fontSize: '12px' }}>
                                                <strong>{cupon.usos_actuales}</strong> {cupon.limite_usos ? `/ ${cupon.limite_usos}` : '(∞)'}
                                            </td>
                                            <td style={{ fontSize: '11px', color: 'var(--admin-text-muted)' }}>
                                                <div>{cupon.fecha_inicio ? new Date(cupon.fecha_inicio).toLocaleDateString() : 'Sin inicio'}</div>
                                                <div>{cupon.fecha_fin ? new Date(cupon.fecha_fin).toLocaleDateString() : 'Sin fin'}</div>
                                            </td>
                                            <td>
                                                {isExpired ? (
                                                    <span className="admin-badge admin-badge-warning" style={{ background: '#f3f4f6', color: '#6b7280' }}>
                                                        Expirado
                                                    </span>
                                                ) : (
                                                    <span className={`admin-badge ${cupon.activo ? 'admin-badge-success' : 'admin-badge-error'}`}>
                                                        {cupon.activo ? 'Activo' : 'Inactivo'}
                                                    </span>
                                                )}
                                            </td>
                                            <td>
                                                <div style={{ display: 'flex', gap: '8px' }}>
                                                    <button onClick={() => openEditModal(cupon)} style={{ background: 'transparent', border: '1px solid var(--admin-primary)', color: 'var(--admin-primary)', padding: '4px 8px', borderRadius: '6px', cursor: 'pointer', transition: 'all 0.2s', opacity: isExpired ? 0.7 : 1 }} onMouseOver={e => {e.currentTarget.style.background='var(--admin-primary-lighter)'}} onMouseOut={e => {e.currentTarget.style.background='transparent'}} title="Editar">
                                                        <Edit size={14} />
                                                    </button>
                                                    <button onClick={() => confirmDelete(cupon.id)} style={{ background: 'transparent', border: '1px solid var(--admin-error)', color: 'var(--admin-error)', padding: '4px 8px', borderRadius: '6px', cursor: 'pointer', transition: 'all 0.2s', opacity: isExpired ? 0.7 : 1 }} onMouseOver={e => {e.currentTarget.style.background='var(--admin-error-bg)'}} onMouseOut={e => {e.currentTarget.style.background='transparent'}} title="Eliminar">
                                                        <Trash2 size={14} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
            </div>

            {/* Modal */}
            {isModalOpen && (
                <div style={{ position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(3px)', zIndex: 1050, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <div style={{ background: '#fff', borderRadius: '16px', width: '100%', maxWidth: '600px', boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)', padding: '0', overflow: 'hidden' }}>
                        <div style={{ padding: '24px 24px 16px', borderBottom: '1px solid var(--admin-border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h5 style={{ margin: 0, fontSize: '18px', fontWeight: '700', color: 'var(--admin-text-main)' }}>{isEditing ? 'Editar Cupón' : 'Nuevo Cupón'}</h5>
                            <button type="button" onClick={() => setIsModalOpen(false)} style={{ background: 'transparent', border: 'none', cursor: 'pointer', color: 'var(--admin-text-muted)' }}><X size={20}/></button>
                        </div>
                            <form onSubmit={handleSubmit}>
                                <div style={{ padding: '24px', display: 'flex', flexDirection: 'column', gap: '20px' }}>
                                    
                                    <div>
                                        <label style={{ display: 'block', fontSize: '13px', fontWeight: '600', color: 'var(--admin-text-secondary)', marginBottom: '8px' }}>Código del Cupón <span style={{color: 'var(--admin-error)'}}>*</span></label>
                                        <input type="text" value={form.codigo} onChange={e => setForm({...form, codigo: e.target.value.toUpperCase()})} required placeholder="Ej: VERANO2026" style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', outline: 'none', transition: 'all 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#d1d5db'} />
                                    </div>

                                    <div style={{ display: 'flex', gap: '16px' }}>
                                        <div style={{ flex: 1 }}>
                                            <label style={{ display: 'block', fontSize: '13px', fontWeight: '600', color: 'var(--admin-text-secondary)', marginBottom: '8px' }}>Tipo <span style={{color: 'var(--admin-error)'}}>*</span></label>
                                            <select value={form.tipo} onChange={e => setForm({...form, tipo: e.target.value})} required style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', outline: 'none', background: '#fff', transition: 'all 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#d1d5db'}>
                                                <option value="porcentaje">Porcentaje (%)</option>
                                                <option value="fijo">Monto Fijo (S/)</option>
                                            </select>
                                        </div>
                                        <div style={{ flex: 1 }}>
                                            <label style={{ display: 'block', fontSize: '13px', fontWeight: '600', color: 'var(--admin-text-secondary)', marginBottom: '8px' }}>Valor <span style={{color: 'var(--admin-error)'}}>*</span></label>
                                            <input type="number" step="0.01" value={form.valor} onChange={e => setForm({...form, valor: e.target.value})} required min="0" placeholder="Ej: 15.00" style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', outline: 'none', transition: 'all 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#d1d5db'} />
                                        </div>
                                    </div>

                                    <div>
                                        <label style={{ display: 'block', fontSize: '13px', fontWeight: '600', color: 'var(--admin-text-secondary)', marginBottom: '8px' }}>Monto Mínimo de Compra (Opcional)</label>
                                        <input type="number" step="0.01" value={form.monto_minimo} onChange={e => setForm({...form, monto_minimo: e.target.value})} min="0" placeholder="Ej: 100.00" style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', outline: 'none', transition: 'all 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#d1d5db'} />
                                    </div>

                                    <div style={{ display: 'flex', gap: '16px' }}>
                                        <div style={{ flex: 1 }}>
                                            <label style={{ display: 'block', fontSize: '13px', fontWeight: '600', color: 'var(--admin-text-secondary)', marginBottom: '8px' }}>Fecha Inicio (Opcional)</label>
                                            <input type="datetime-local" value={form.fecha_inicio} onChange={e => setForm({...form, fecha_inicio: e.target.value})} style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', outline: 'none', transition: 'all 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#d1d5db'} />
                                        </div>
                                        <div style={{ flex: 1 }}>
                                            <label style={{ display: 'block', fontSize: '13px', fontWeight: '600', color: 'var(--admin-text-secondary)', marginBottom: '8px' }}>Fecha Fin (Opcional)</label>
                                            <input type="datetime-local" value={form.fecha_fin} onChange={e => setForm({...form, fecha_fin: e.target.value})} style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', outline: 'none', transition: 'all 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#d1d5db'} />
                                        </div>
                                    </div>

                                    <div style={{ display: 'flex', gap: '16px', alignItems: 'center' }}>
                                        <div style={{ flex: 1 }}>
                                            <label style={{ display: 'block', fontSize: '13px', fontWeight: '600', color: 'var(--admin-text-secondary)', marginBottom: '8px' }}>Límite de usos (Opcional)</label>
                                            <input type="number" value={form.limite_usos} onChange={e => setForm({...form, limite_usos: e.target.value})} min="1" placeholder="Ej: 100" style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid #d1d5db', fontSize: '14px', outline: 'none', transition: 'all 0.2s' }} onFocus={e => e.target.style.borderColor = 'var(--admin-primary)'} onBlur={e => e.target.style.borderColor = '#d1d5db'} />
                                        </div>
                                        <div style={{ flex: 1, display: 'flex', alignItems: 'center', gap: '16px', marginTop: '22px' }}>
                                            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer', fontSize: '13px', fontWeight: '500', color: 'var(--admin-text-main)' }}>
                                                <input type="checkbox" checked={form.unico_por_cliente} onChange={e => setForm({...form, unico_por_cliente: e.target.checked})} style={{ width: '16px', height: '16px', accentColor: 'var(--admin-primary)', cursor: 'pointer' }} />
                                                1 solo uso por cliente
                                            </label>
                                            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer', fontSize: '13px', fontWeight: '500', color: 'var(--admin-text-main)' }}>
                                                <input type="checkbox" checked={form.activo} onChange={e => setForm({...form, activo: e.target.checked})} style={{ width: '16px', height: '16px', accentColor: 'var(--admin-primary)', cursor: 'pointer' }} />
                                                Cupón Activo
                                            </label>
                                        </div>
                                    </div>

                                </div>
                                <div style={{ padding: '16px 24px', borderTop: '1px solid var(--admin-border)', background: 'var(--admin-bg)', display: 'flex', justifyContent: 'flex-end', gap: '12px' }}>
                                    <button type="button" onClick={() => setIsModalOpen(false)} style={{ background: '#fff', border: '1px solid var(--admin-border)', padding: '10px 20px', borderRadius: '8px', fontWeight: '600', color: 'var(--admin-text-main)', cursor: 'pointer' }}>Cancelar</button>
                                    <button type="submit" className="admin-btn-primary" style={{ borderRadius: '8px' }}>Guardar Cupón</button>
                                </div>
                            </form>
                        </div>
                    </div>
            )}

            {/* Delete Confirm Modal */}
            {deleteConfirm && (
                <div style={{ position: 'fixed', top: 0, left: 0, width: '100%', height: '100%', background: 'rgba(0,0,0,0.5)', backdropFilter: 'blur(3px)', zIndex: 1100, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <div style={{ background: '#fff', borderRadius: '16px', width: '100%', maxWidth: '400px', boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1)', padding: '24px', textAlign: 'center' }}>
                        <div style={{ width: '60px', height: '60px', background: 'var(--admin-error-bg)', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 16px' }}>
                            <Trash2 size={28} color="var(--admin-error)" />
                        </div>
                        <h5 style={{ margin: '0 0 12px', fontSize: '18px', fontWeight: '700', color: 'var(--admin-text-main)' }}>¿Eliminar este cupón?</h5>
                        <p style={{ color: 'var(--admin-text-secondary)', fontSize: '14px', margin: '0 0 24px' }}>Esta acción no se puede deshacer. El cupón ya no será válido para futuras compras.</p>
                        <div style={{ display: 'flex', gap: '12px', justifyContent: 'center' }}>
                            <button type="button" onClick={() => setDeleteConfirm(null)} style={{ background: '#fff', border: '1px solid var(--admin-border)', padding: '10px 20px', borderRadius: '8px', fontWeight: '600', color: 'var(--admin-text-main)', cursor: 'pointer', flex: 1, transition: 'all 0.2s' }} onMouseOver={e => e.currentTarget.style.background = 'var(--admin-bg)'} onMouseOut={e => e.currentTarget.style.background = '#fff'}>Cancelar</button>
                            <button type="button" onClick={executeDelete} style={{ background: 'var(--admin-error)', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: '600', color: '#fff', cursor: 'pointer', flex: 1, transition: 'all 0.2s' }} onMouseOver={e => e.currentTarget.style.filter = 'brightness(0.9)'} onMouseOut={e => e.currentTarget.style.filter = 'none'}>Sí, eliminar</button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
