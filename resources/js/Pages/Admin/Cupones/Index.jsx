import React from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function Index() {
    const confirmDialog = useConfirm();

    const { cupones, flash } = usePage().props;
    const data = cupones?.data || [];

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Estás seguro de eliminar este cupón?')) {
            router.delete(`/admin/cupones/${id}`);
        }
    };

    return (
        <AdminLayout logoUrl={null}>
            <Head title="Cupones" />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Gestión de Cupones</h1>
                <Link 
                    href="/admin/cupones/create" 
                    style={{ background: '#1d4ed8', color: 'white', padding: '10px 16px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '8px', boxShadow: '0 4px 10px rgba(29, 78, 216, 0.3)' }}
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 5v14M5 12h14"/></svg>
                    Nuevo Cupón
                </Link>
            </div>

            {flash?.success && (
                <div style={{ background: 'rgba(37,99,235,0.1)', color: '#2563eb', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', fontWeight: '500', border: '1px solid rgba(37,99,235,0.2)' }}>
                    {flash.success}
                </div>
            )}

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                        <tr style={{ borderBottom: '2px solid var(--admin-border)', color: 'var(--admin-text-muted)' }}>
                            <th style={{ padding: '12px', width: '50px' }}>ID</th>
                            <th style={{ padding: '12px' }}>Código</th>
                            <th style={{ padding: '12px' }}>Descuento</th>
                            <th style={{ padding: '12px' }}>Fechas</th>
                            <th style={{ padding: '12px' }}>Usos</th>
                            <th style={{ padding: '12px' }}>Estado</th>
                            <th style={{ padding: '12px', textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.length > 0 ? data.map(cupon => (
                            <tr key={cupon.id} style={{ borderBottom: '1px solid var(--admin-border)', transition: 'background 0.2s' }}>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>#{cupon.id}</td>
                                <td style={{ padding: '12px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>
                                    <span style={{ background: 'var(--admin-border)', padding: '4px 8px', borderRadius: '6px', fontFamily: 'monospace' }}>
                                        {cupon.codigo}
                                    </span>
                                </td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)', fontWeight: 'bold' }}>
                                    {cupon.tipo === 'porcentaje' ? `${cupon.valor}%` : `S/ ${cupon.valor}`}
                                </td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>
                                    <div style={{ fontSize: '13px' }}>
                                        <strong>Inicio:</strong> {cupon.fecha_inicio ? new Date(cupon.fecha_inicio).toLocaleDateString() : 'Sin definir'}<br/>
                                        <strong>Fin:</strong> {cupon.fecha_fin ? new Date(cupon.fecha_fin).toLocaleDateString() : 'Sin definir'}
                                    </div>
                                </td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>
                                    <span style={{ background: 'rgba(59,130,246,0.1)', color: '#3b82f6', padding: '4px 8px', borderRadius: '12px', fontSize: '12px', fontWeight: 'bold' }}>
                                        {cupon.usos_count} usos
                                    </span>
                                </td>
                                <td style={{ padding: '12px' }}>
                                    <span style={{ 
                                        background: cupon.activo ? 'rgba(37,99,235,0.1)' : 'rgba(59,130,246,0.1)', 
                                        color: cupon.activo ? '#2563eb' : '#3b82f6', 
                                        padding: '4px 8px', borderRadius: '12px', fontSize: '12px', fontWeight: 'bold' 
                                    }}>
                                        {cupon.activo ? 'Activo' : 'Inactivo'}
                                    </span>
                                </td>
                                <td style={{ padding: '12px', textAlign: 'right' }}>
                                    <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                                        <Link href={`/admin/cupones/${cupon.id}/edit`} style={{ color: '#3b82f6', textDecoration: 'none', padding: '6px', borderRadius: '6px', background: 'rgba(59,130,246,0.1)' }}>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </Link>
                                        <button onClick={() => handleDelete(cupon.id)} style={{ background: 'rgba(59,130,246,0.1)', color: '#3b82f6', border: 'none', cursor: 'pointer', padding: '6px', borderRadius: '6px' }}>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="7" style={{ padding: '20px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>
                                    No hay cupones registrados.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {cupones?.links && cupones.links.length > 3 && (
                <div style={{ display: 'flex', justifyContent: 'center', marginTop: '20px', gap: '5px' }}>
                    {cupones.links.map((link, i) => (
                        <Link 
                            key={i} 
                            href={link.url || '#'} 
                            style={{ 
                                padding: '8px 12px', 
                                background: link.active ? '#1d4ed8' : 'var(--admin-bg-panel)', 
                                color: link.active ? 'white' : 'var(--admin-text-main)', 
                                borderRadius: '6px', 
                                textDecoration: 'none',
                                opacity: link.url ? 1 : 0.5,
                                pointerEvents: link.url ? 'auto' : 'none'
                            }}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
