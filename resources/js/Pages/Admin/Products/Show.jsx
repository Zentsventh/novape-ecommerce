import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Show({ producto, costoPromedio, historialCompras = [], comparativaProveedores = [] }) {
    const precio = parseFloat(producto.variantes?.[0]?.precio || 0);
    const ganancia = precio - costoPromedio;
    const margen = precio > 0 ? (ganancia / precio) * 100 : 0;

    return (
        <AdminLayout>
            <Head title={`Producto: ${producto.nombre}`} />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>
                    Detalle del Producto
                </h1>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <Link href={`/admin/products/${producto.id}/edit`} style={{ padding: '10px 16px', borderRadius: '8px', background: 'var(--admin-primary)', color: 'white', textDecoration: 'none', fontWeight: 'bold' }}>
                        Editar Producto
                    </Link>
                    <Link href="/admin/products" style={{ padding: '10px 16px', borderRadius: '8px', background: 'var(--admin-border)', color: 'var(--admin-text-main)', textDecoration: 'none', fontWeight: 'bold' }}>
                        Volver
                    </Link>
                </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 350px', gap: '20px' }}>
                
                {/* Info Principal */}
                <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '25px', border: '1px solid var(--admin-border)' }}>
                    <div style={{ display: 'flex', gap: '20px' }}>
                        <div style={{ width: '150px', height: '150px', borderRadius: '8px', overflow: 'hidden', background: '#f8fafc', flexShrink: 0, border: '1px solid var(--admin-border)' }}>
                            {producto.imagenes && producto.imagenes.length > 0 ? (
                                <img src={`/storage/${producto.imagenes[0].url.replace('/storage/', '')}`} alt={producto.nombre} style={{ width: '100%', height: '100%', objectFit: 'cover' }} onError={(e) => e.target.src = producto.imagenes[0].url} />
                            ) : (
                                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', width: '100%', height: '100%', color: '#94a3b8' }}>Sin imagen</div>
                            )}
                        </div>
                        <div>
                            <h2 style={{ fontSize: '20px', fontWeight: 'bold', margin: '0 0 10px 0', color: 'var(--admin-text-main)' }}>{producto.nombre}</h2>
                            <p style={{ margin: '0 0 5px 0', color: 'var(--admin-text-muted)' }}><strong>SKU:</strong> {producto.sku_base}</p>
                            <p style={{ margin: '0 0 5px 0', color: 'var(--admin-text-muted)' }}><strong>Marca:</strong> {producto.marca ? producto.marca.nombre : 'Genérica'}</p>
                            <p style={{ margin: '0 0 5px 0', color: 'var(--admin-text-muted)' }}><strong>Categoría:</strong> {producto.categorias && producto.categorias.length > 0 ? producto.categorias[0].nombre : 'Sin categoría'}</p>
                            <p style={{ margin: '0 0 5px 0', color: 'var(--admin-text-muted)' }}><strong>Stock Actual:</strong> {producto.variantes?.[0]?.stock || 0} unidades</p>
                            <p style={{ margin: '0 0 5px 0', color: 'var(--admin-text-muted)' }}><strong>Estado:</strong> {producto.activo ? <span style={{ color: '#10b981', fontWeight: 'bold' }}>Activo</span> : <span style={{ color: '#ef4444', fontWeight: 'bold' }}>Inactivo</span>}</p>
                        </div>
                    </div>
                </div>

                {/* Información de Venta */}
                <div style={{ background: '#f8fafc', borderRadius: '12px', padding: '25px', border: '1px solid #e2e8f0', boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.05)' }}>
                    <h3 style={{ fontSize: '16px', fontWeight: 'bold', color: '#0f172a', margin: '0 0 20px 0', display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        Datos de Venta
                    </h3>

                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '15px', borderBottom: '1px dashed #cbd5e1', paddingBottom: '15px' }}>
                        <span style={{ color: '#64748b', fontSize: '14px' }}>Precio Venta Público:</span>
                        <span style={{ fontWeight: 'bold', fontSize: '18px', color: '#0f172a' }}>S/ {Number(precio).toLocaleString('en-US', {minimumFractionDigits:2})}</span>
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                        <span style={{ color: '#64748b', fontSize: '14px' }}>Costo Promedio Total:</span>
                        <span style={{ fontWeight: 'bold', fontSize: '14px', color: '#0f172a' }}>S/ {Number(costoPromedio).toLocaleString('en-US', {minimumFractionDigits:2})}</span>
                    </div>
                </div>
            </div>

            {/* Comparativa de Proveedores */}
            <div style={{ marginTop: '30px' }}>
                <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: 'var(--admin-text-main)', marginBottom: '15px' }}>
                    Comparativa de Rentabilidad por Proveedor (Historial)
                </h3>
                
                {comparativaProveedores.length > 0 ? (
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: '20px', marginBottom: '30px' }}>
                        {comparativaProveedores.sort((a,b) => a.ultimo_costo - b.ultimo_costo).map((prov, index) => {
                            const gananciaProv = precio - prov.ultimo_costo;
                            const margenProv = precio > 0 ? (gananciaProv / precio) * 100 : 0;
                            return (
                                <div key={prov.proveedor_id} style={{ background: index === 0 ? '#E6F7FF' : 'var(--admin-bg-panel)', border: index === 0 ? '2px solid #00B4FF' : '1px solid var(--admin-border)', borderRadius: '12px', padding: '20px', position: 'relative', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                                    {index === 0 && (
                                        <div style={{ position: 'absolute', top: '-12px', right: '20px', background: '#00B4FF', color: 'white', fontSize: '11px', fontWeight: 'bold', padding: '4px 10px', borderRadius: '12px' }}>
                                            Mayor Margen / Mejor Precio
                                        </div>
                                    )}
                                    <h4 style={{ margin: '0 0 15px 0', fontSize: '16px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{prov.proveedor_nombre}</h4>
                                    
                                    {/* Costos y Compras */}
                                    <div style={{ marginBottom: '15px', paddingBottom: '10px', borderBottom: '1px dashed var(--admin-border)' }}>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                                            <span style={{ color: 'var(--admin-text-muted)', fontSize: '13px' }}>Último costo ud:</span>
                                            <span style={{ fontWeight: 'bold', color: 'var(--admin-text-main)' }}>S/ {Number(prov.ultimo_costo).toLocaleString('en-US', {minimumFractionDigits:2})}</span>
                                        </div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
                                            <span style={{ color: 'var(--admin-text-muted)', fontSize: '13px' }}>Unidades / Órdenes:</span>
                                            <span style={{ fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{prov.total_unidades} uds. en {prov.frecuencia} orden(es)</span>
                                        </div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <span style={{ color: 'var(--admin-text-muted)', fontSize: '13px' }}>Última compra:</span>
                                            <span style={{ fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{prov.ultima_compra}</span>
                                        </div>
                                    </div>
                                    
                                    {/* Rentabilidad con ese proveedor */}
                                    <div style={{ background: index === 0 ? '#BAE6FF' : '#F8FAFC', padding: '10px 15px', borderRadius: '8px' }}>
                                        <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '5px' }}>
                                            <span style={{ color: index === 0 ? '#005C8A' : 'var(--admin-text-muted)', fontSize: '13px', fontWeight: 'bold' }}>Ganancia Neta:</span>
                                            <span style={{ fontWeight: 'bold', color: index === 0 ? '#005C8A' : '#0088CC' }}>S/ {Number(gananciaProv).toLocaleString('en-US', {minimumFractionDigits:2})}</span>
                                        </div>
                                        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                                            <span style={{ color: index === 0 ? '#005C8A' : 'var(--admin-text-muted)', fontSize: '13px', fontWeight: 'bold' }}>Margen:</span>
                                            <span style={{ fontWeight: 'bold', color: index === 0 ? '#005C8A' : '#0088CC' }}>{margenProv.toFixed(2)}%</span>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div style={{ padding: '20px', background: '#F8FAFC', borderRadius: '12px', color: '#64748B', textAlign: 'center', marginBottom: '30px' }}>
                        No hay historial de compras de este producto aún. Registra una compra para ver comparativas de rentabilidad.
                    </div>
                )}
            </div>
            
            {/* Historial de Órdenes Relacionadas */}
            {historialCompras.length > 0 && (
                <div style={{ marginTop: '10px' }}>
                    <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: 'var(--admin-text-main)', marginBottom: '15px' }}>
                        Órdenes de Compra Relacionadas
                    </h3>
                    <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', border: '1px solid var(--admin-border)', overflow: 'hidden' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                            <thead style={{ background: '#F8FAFC' }}>
                                <tr>
                                    <th style={{ padding: '12px 15px', borderBottom: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', fontSize: '13px' }}>Orden</th>
                                    <th style={{ padding: '12px 15px', borderBottom: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', fontSize: '13px' }}>Fecha</th>
                                    <th style={{ padding: '12px 15px', borderBottom: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', fontSize: '13px' }}>Proveedor</th>
                                    <th style={{ padding: '12px 15px', borderBottom: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', fontSize: '13px' }}>Cantidad</th>
                                    <th style={{ padding: '12px 15px', borderBottom: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', fontSize: '13px' }}>Costo Unitario</th>
                                    <th style={{ padding: '12px 15px', borderBottom: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', fontSize: '13px' }}>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {historialCompras.map((hc, i) => (
                                    <tr key={i} style={{ borderBottom: '1px solid var(--admin-border)' }}>
                                        <td style={{ padding: '12px 15px', fontSize: '14px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{hc.numero_orden}</td>
                                        <td style={{ padding: '12px 15px', fontSize: '14px', color: 'var(--admin-text-muted)' }}>{hc.fecha_compra}</td>
                                        <td style={{ padding: '12px 15px', fontSize: '14px', color: 'var(--admin-text-main)' }}>{hc.proveedor_nombre || 'Sin proveedor'}</td>
                                        <td style={{ padding: '12px 15px', fontSize: '14px', color: 'var(--admin-text-muted)' }}>{hc.cantidad}</td>
                                        <td style={{ padding: '12px 15px', fontSize: '14px', color: 'var(--admin-text-main)' }}>S/ {Number(hc.costo_unitario).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                                        <td style={{ padding: '12px 15px', fontSize: '14px', fontWeight: 'bold', color: '#10B981' }}>S/ {Number(hc.subtotal).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
