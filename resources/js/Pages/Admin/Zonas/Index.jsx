import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function ZonasIndex({ zonas, logoUrl }) {
    const confirmDialog = useConfirm();

    const [showModal, setShowModal] = useState(false);
    const { data, setData, post, processing, reset } = useForm({
        nombre: '', descripcion: '', costo_envio: ''
    });

    const submit = async (e) => {
        e.preventDefault();
        post('/admin/zonas', { onSuccess: () => { setShowModal(false); reset(); } });
    };

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Eliminar esta zona de envío?')) router.delete(`/admin/zonas/${id}`);
    };

    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title="Zonas de Envío" />
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <div>
                    <h1 style={{ fontSize: '28px', margin: 0, fontWeight: '700', color: 'var(--admin-text-main)' }}>Zonas de Envío y Logística</h1>
                    <p style={{ color: 'var(--admin-text-secondary)', margin: '5px 0 0 0' }}>Administra costos de entrega por área geográfica.</p>
                </div>
                <button onClick={() => setShowModal(true)} style={{ background: 'var(--admin-primary)', color: 'white', padding: '10px 20px', borderRadius: '8px', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>+ Nueva Zona</button>
            </div>

            <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', overflow: 'hidden', boxShadow: 'var(--admin-shadow-sm)' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead style={{ background: 'var(--admin-bg)', borderBottom: '1px solid var(--admin-border)' }}>
                        <tr>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ZONA</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>DESCRIPCIÓN</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>COSTO ENVÍO</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ESTADO</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        {zonas.map(z => (
                            <tr key={z.id} style={{ borderBottom: '1px solid var(--admin-border-light)' }}>
                                <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{z.nombre}</td>
                                <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)' }}>{z.descripcion || '—'}</td>
                                <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-primary)' }}>S/ {Number(z.costo_envio).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td style={{ padding: '15px 20px' }}>
                                    <span style={{ background: z.activo ? 'rgba(37,99,235,0.1)' : 'rgba(59,130,246,0.1)', color: z.activo ? '#2563eb' : '#3b82f6', padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold' }}>
                                        {z.activo ? 'Activa' : 'Inactiva'}
                                    </span>
                                </td>
                                <td style={{ padding: '15px 20px' }}>
                                    <button onClick={() => handleDelete(z.id)} style={{ color: '#3b82f6', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>Eliminar</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {showModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
                    <div style={{ background: 'white', padding: '30px', borderRadius: '12px', width: '420px' }}>
                        <h2 style={{ margin: '0 0 20px 0' }}>Nueva Zona de Envío</h2>
                        <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Nombre de la Zona</label>
                                <input type="text" value={data.nombre} onChange={e => setData('nombre', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required placeholder="Ej: Lima Norte" />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Descripción / Cobertura</label>
                                <textarea value={data.descripcion} onChange={e => setData('descripcion', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc', minHeight: '60px' }} placeholder="Distritos que cubre..." />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Costo de Envío (S/)</label>
                                <input type="number" step="0.01" value={data.costo_envio} onChange={e => setData('costo_envio', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required />
                            </div>
                            <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                                <button type="button" onClick={() => setShowModal(false)} style={{ flex: 1, padding: '10px', borderRadius: '6px', border: '1px solid #ccc', background: 'transparent', cursor: 'pointer' }}>Cancelar</button>
                                <button type="submit" disabled={processing} style={{ flex: 1, padding: '10px', borderRadius: '6px', border: 'none', background: 'var(--admin-primary)', color: 'white', fontWeight: 'bold', cursor: 'pointer' }}>Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
