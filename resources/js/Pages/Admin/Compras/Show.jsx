import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';

export default function CompraShow({ compra, items, logoUrl }) {
    const handleCompletar = () => {
        if (confirm('¿Estás seguro de completar esta compra? Esto añadirá el stock al inventario y actualizará los costos.')) {
            router.post(`/admin/compras/${compra.id}/completar`, {}, { preserveScroll: true });
        }
    };
    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title={`Orden #OC-${String(compra.id).padStart(4, '0')}`} />

            <div style={{ marginBottom: '20px' }}>
                <Link href="/admin/compras" style={{ color: 'var(--admin-primary)', textDecoration: 'none', fontWeight: 'bold', fontSize: '14px' }}>
                    ← Volver al Historial
                </Link>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 320px', gap: '20px' }}>
                {/* Detalle de Items */}
                <div>
                    <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', padding: '25px', boxShadow: 'var(--admin-shadow-sm)', marginBottom: '20px' }}>
                        <h1 style={{ fontSize: '24px', margin: '0 0 5px 0', fontWeight: '700', color: 'var(--admin-text-main)' }}>
                            Orden de Compra #OC-{String(compra.id).padStart(4, '0')}
                        </h1>
                        <p style={{ color: 'var(--admin-text-secondary)', margin: 0 }}>
                            Fecha: {compra.fecha_compra} &nbsp;|&nbsp;
                            Estado: <span style={{
                                background: compra.estado === 'completado' ? 'rgba(37,99,235,0.1)' : compra.estado === 'pendiente' ? 'rgba(245,158,11,0.1)' : 'rgba(59,130,246,0.1)',
                                color: compra.estado === 'completado' ? '#2563eb' : compra.estado === 'pendiente' ? '#1e40af' : '#3b82f6',
                                padding: '3px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold'
                            }}>{compra.estado === 'completado' ? 'Completado' : compra.estado === 'pendiente' ? 'Pendiente' : 'Cancelado'}</span>
                        </p>
                    </div>

                    <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', overflow: 'hidden', boxShadow: 'var(--admin-shadow-sm)' }}>
                        <div style={{ padding: '15px 20px', borderBottom: '1px solid var(--admin-border)', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>
                            Productos de la Orden ({items.length} ítems)
                        </div>
                        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                            <thead style={{ background: 'var(--admin-bg)' }}>
                                <tr>
                                    <th style={{ padding: '12px 20px', textAlign: 'left', color: 'var(--admin-text-secondary)', fontSize: '12px', fontWeight: '600' }}>PRODUCTO</th>
                                    <th style={{ padding: '12px 20px', textAlign: 'left', color: 'var(--admin-text-secondary)', fontSize: '12px', fontWeight: '600' }}>SKU</th>
                                    <th style={{ padding: '12px 20px', textAlign: 'right', color: 'var(--admin-text-secondary)', fontSize: '12px', fontWeight: '600' }}>CANTIDAD</th>
                                    <th style={{ padding: '12px 20px', textAlign: 'right', color: 'var(--admin-text-secondary)', fontSize: '12px', fontWeight: '600' }}>COSTO UNIT.</th>
                                    <th style={{ padding: '12px 20px', textAlign: 'right', color: 'var(--admin-text-secondary)', fontSize: '12px', fontWeight: '600' }}>SUBTOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map(item => (
                                    <tr key={item.id} style={{ borderBottom: '1px solid var(--admin-border-light)' }}>
                                        <td style={{ padding: '12px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{item.producto_nombre || 'Producto'}</td>
                                        <td style={{ padding: '12px 20px', color: 'var(--admin-text-muted)', fontSize: '13px' }}>{item.sku || '—'}</td>
                                        <td style={{ padding: '12px 20px', textAlign: 'right', fontWeight: 'bold' }}>{item.cantidad}</td>
                                        <td style={{ padding: '12px 20px', textAlign: 'right', color: 'var(--admin-text-secondary)' }}>S/ {Number(item.costo_unitario).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        <td style={{ padding: '12px 20px', textAlign: 'right', fontWeight: 'bold', color: 'var(--admin-primary)' }}>S/ {Number(item.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot style={{ borderTop: '2px solid var(--admin-border)' }}>
                                <tr>
                                    <td colSpan="4" style={{ padding: '15px 20px', textAlign: 'right', fontWeight: 'bold', fontSize: '16px' }}>TOTAL ORDEN:</td>
                                    <td style={{ padding: '15px 20px', textAlign: 'right', fontWeight: 'bold', fontSize: '18px', color: '#2563eb' }}>S/ {Number(compra.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {compra.notas && (
                        <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', padding: '20px', marginTop: '20px', boxShadow: 'var(--admin-shadow-sm)' }}>
                            <h3 style={{ margin: '0 0 10px 0', fontSize: '14px', color: 'var(--admin-text-main)' }}>Notas de la Orden</h3>
                            <p style={{ color: 'var(--admin-text-secondary)', margin: 0, lineHeight: '1.6' }}>{compra.notas}</p>
                        </div>
                    )}
                </div>

                {/* Sidebar: Info del Proveedor */}
                <div>
                    <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', padding: '20px', boxShadow: 'var(--admin-shadow-sm)' }}>
                        <h3 style={{ margin: '0 0 15px 0', fontSize: '14px', color: 'var(--admin-text-main)', borderBottom: '1px solid var(--admin-border)', paddingBottom: '10px' }}>Información del Proveedor</h3>
                        <div style={{ marginBottom: '12px' }}>
                            <div style={{ fontSize: '11px', color: 'var(--admin-text-muted)', textTransform: 'uppercase', marginBottom: '3px' }}>Razón Social</div>
                            <div style={{ fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{compra.proveedor_nombre || 'No asignado'}</div>
                        </div>
                        {compra.proveedor_email && (
                            <div style={{ marginBottom: '12px' }}>
                                <div style={{ fontSize: '11px', color: 'var(--admin-text-muted)', textTransform: 'uppercase', marginBottom: '3px' }}>Email</div>
                                <div style={{ color: 'var(--admin-primary)' }}>{compra.proveedor_email}</div>
                            </div>
                        )}
                        {compra.proveedor_telefono && (
                            <div style={{ marginBottom: '12px' }}>
                                <div style={{ fontSize: '11px', color: 'var(--admin-text-muted)', textTransform: 'uppercase', marginBottom: '3px' }}>Teléfono</div>
                                <div style={{ color: 'var(--admin-text-main)' }}>{compra.proveedor_telefono}</div>
                            </div>
                        )}
                    </div>

                    <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', padding: '20px', marginTop: '15px', boxShadow: 'var(--admin-shadow-sm)' }}>
                        <h3 style={{ margin: '0 0 15px 0', fontSize: '14px', color: 'var(--admin-text-main)', borderBottom: '1px solid var(--admin-border)', paddingBottom: '10px' }}>Resumen Financiero</h3>
                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                            <span style={{ color: 'var(--admin-text-secondary)', fontSize: '13px' }}>Items</span>
                            <span style={{ fontWeight: 'bold' }}>{items.length}</span>
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                            <span style={{ color: 'var(--admin-text-secondary)', fontSize: '13px' }}>Unidades totales</span>
                            <span style={{ fontWeight: 'bold' }}>{items.reduce((sum, i) => sum + i.cantidad, 0)}</span>
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', borderTop: '1px dashed var(--admin-border)', paddingTop: '10px', marginTop: '10px' }}>
                            <span style={{ fontWeight: 'bold', fontSize: '15px' }}>Total</span>
                            <span style={{ fontWeight: 'bold', fontSize: '15px', color: '#2563eb' }}>S/ {Number(compra.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                    </div>

                    {compra.estado === 'pendiente' && (
                        <button 
                            onClick={handleCompletar}
                            className="admin-btn-primary"
                            style={{ width: '100%', marginTop: '15px', padding: '12px', display: 'flex', justifyContent: 'center', gap: '8px' }}
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            Completar Compra y Cargar Stock
                        </button>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
