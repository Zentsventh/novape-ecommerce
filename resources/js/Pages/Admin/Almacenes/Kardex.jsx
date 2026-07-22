import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';

export default function Kardex({ almacen, movimientos, logoUrl }) {
    const data = movimientos.data || [];
    
    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title={`Kardex - ${almacen.nombre}`} />

            <div style={{ marginBottom: '20px' }}>
                <Link href="/admin/almacenes" style={{ color: 'var(--admin-primary)', textDecoration: 'none', fontWeight: 'bold', fontSize: '14px' }}>
                    ← Volver a Almacenes
                </Link>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <div>
                    <h1 style={{ fontSize: '28px', margin: 0, fontWeight: '700', color: 'var(--admin-text-main)' }}>
                        Kardex: {almacen.nombre}
                    </h1>
                    <p style={{ color: 'var(--admin-text-secondary)', margin: '5px 0 0 0' }}>
                        Historial de movimientos, entradas, salidas y transferencias.
                    </p>
                </div>
            </div>

            <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', overflow: 'hidden', boxShadow: 'var(--admin-shadow-sm)' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead style={{ background: 'var(--admin-bg)', borderBottom: '1px solid var(--admin-border)' }}>
                        <tr>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>FECHA</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>TIPO</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>PRODUCTO / SKU</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px', textAlign: 'right' }}>CANTIDAD</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>REFERENCIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.length > 0 ? data.map(m => (
                            <tr key={m.id} style={{ borderBottom: '1px solid var(--admin-border-light)' }}>
                                <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontSize: '13px' }}>
                                    {new Date(m.created_at).toLocaleString()}
                                </td>
                                <td style={{ padding: '15px 20px' }}>
                                    {m.tipo === 'entrada' && <span style={{ background: 'rgba(37,99,235,0.1)', color: '#2563eb', padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold' }}>ENTRADA</span>}
                                    {m.tipo === 'salida' && <span style={{ background: 'rgba(59,130,246,0.1)', color: '#3b82f6', padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold' }}>SALIDA</span>}
                                    {m.tipo === 'transferencia' && <span style={{ background: 'rgba(59,130,246,0.1)', color: '#3b82f6', padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold' }}>TRANSFERENCIA</span>}
                                </td>
                                <td style={{ padding: '15px 20px' }}>
                                    <div style={{ fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{m.producto_nombre}</div>
                                    <div style={{ fontSize: '12px', color: 'var(--admin-text-muted)' }}>{m.sku}</div>
                                </td>
                                <td style={{ padding: '15px 20px', textAlign: 'right', fontWeight: 'bold', color: m.cantidad > 0 ? '#2563eb' : '#3b82f6', fontSize: '16px' }}>
                                    {m.cantidad > 0 ? `+${m.cantidad}` : m.cantidad}
                                </td>
                                <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontSize: '13px' }}>
                                    {m.referencia || '—'}
                                    {m.destino_nombre && m.cantidad < 0 && <span style={{ display: 'block', color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>Hacia: {m.destino_nombre}</span>}
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="5" style={{ padding: '40px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>No hay movimientos en este almacén.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            
            {/* Simple Pagination */}
            <div style={{ display: 'flex', justifyContent: 'center', marginTop: '20px', gap: '10px' }}>
                {movimientos.links && movimientos.links.map((link, idx) => {
                    if (!link.url) {
                        return (
                            <span
                                key={idx}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                style={{
                                    padding: '8px 12px',
                                    border: '1px solid var(--admin-border)',
                                    background: 'white',
                                    color: 'var(--admin-text-main)',
                                    borderRadius: '6px',
                                    pointerEvents: 'none',
                                    opacity: 0.5
                                }}
                            />
                        );
                    }
                    return (
                        <Link
                            key={idx}
                            href={link.url}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            style={{
                                padding: '8px 12px',
                                border: '1px solid var(--admin-border)',
                                background: link.active ? 'var(--admin-primary)' : 'white',
                                color: link.active ? 'white' : 'var(--admin-text-main)',
                                borderRadius: '6px',
                                textDecoration: 'none'
                            }}
                        />
                    );
                })}
            </div>

        </AdminLayout>
    );
}
