import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, ArcElement } from 'chart.js';
import { Pie } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, ArcElement);

export default function Inventario({ 
    totalProductos, totalCategorias, totalProveedores,
    valorInventario, stockBajo, productos, logoUrl
}) {
    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title="Panel de Inventario" />

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="text-2xl font-bold" style={{ color: 'var(--admin-text-main)' }}>Panel de Inventario y Almacén</h1>
                    <p style={{ color: 'var(--admin-text-muted)', fontSize: '14px', marginTop: '4px' }}>Control de mercadería, proveedores y valoraciones.</p>
                </div>
            </div>

            {/* KPI Cards */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '20px', marginBottom: '30px' }}>
                
                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', display: 'flex', alignItems: 'center', gap: '15px' }}>
                    <div style={{ width: '48px', height: '48px', borderRadius: '12px', background: 'rgba(59, 130, 246, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" strokeWidth="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <div>
                        <div style={{ color: 'var(--admin-text-muted)', fontSize: '12px', fontWeight: 'bold' }}>PRODUCTOS EN CATÁLOGO</div>
                        <div style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{totalProductos}</div>
                    </div>
                </div>

                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', display: 'flex', alignItems: 'center', gap: '15px' }}>
                    <div style={{ width: '48px', height: '48px', borderRadius: '12px', background: 'rgba(16, 185, 129, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563eb" strokeWidth="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    <div>
                        <div style={{ color: 'var(--admin-text-muted)', fontSize: '12px', fontWeight: 'bold' }}>VALOR DEL INVENTARIO</div>
                        <div style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>S/ {valorInventario.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    </div>
                </div>

                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', display: 'flex', alignItems: 'center', gap: '15px' }}>
                    <div style={{ width: '48px', height: '48px', borderRadius: '12px', background: 'rgba(138, 43, 226, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" strokeWidth="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div>
                        <div style={{ color: 'var(--admin-text-muted)', fontSize: '12px', fontWeight: 'bold' }}>PROVEEDORES ACTIVOS</div>
                        <div style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{totalProveedores}</div>
                    </div>
                </div>

                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', display: 'flex', alignItems: 'center', gap: '15px' }}>
                    <div style={{ width: '48px', height: '48px', borderRadius: '12px', background: 'rgba(59, 130, 246, 0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" strokeWidth="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <div>
                        <div style={{ color: 'var(--admin-text-muted)', fontSize: '12px', fontWeight: 'bold' }}>PRODUCTOS BAJO STOCK</div>
                        <div style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{stockBajo.length}</div>
                    </div>
                </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '20px', marginBottom: '30px' }}>
                <div className="admin-card">
                    <div className="admin-card-header">
                        <h2 className="admin-card-title">Listado Rápido de Stock</h2>
                    </div>
                    <div className="admin-card-body p-0">
                        <div className="overflow-x-auto">
                            <table className="admin-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Proveedor</th>
                                        <th>Costo</th>
                                        <th>Stock Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {productos.map(p => (
                                        <tr key={p.id}>
                                            <td className="font-medium text-gray-800 line-clamp-1">{p.nombre}</td>
                                            <td>{p.marca || '-'}</td>
                                            <td>{p.proveedor || '-'}</td>
                                            <td>S/ {p.costo ? Number(p.costo).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00'}</td>
                                            <td className="font-bold">{p.stock}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div className="admin-card" style={{ borderTop: '4px solid #3b82f6' }}>
                    <div className="admin-card-header">
                        <h2 className="admin-card-title" style={{ color: '#3b82f6' }}>¡Alerta de Stock Bajo!</h2>
                    </div>
                    <div className="admin-card-body p-0">
                        <div className="overflow-x-auto">
                            <table className="admin-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Proveedor</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {stockBajo.length === 0 ? (
                                        <tr><td colSpan="3" className="text-center p-4">Excelente. No hay stock bajo.</td></tr>
                                    ) : stockBajo.map(p => (
                                        <tr key={p.id}>
                                            <td className="font-medium text-gray-800 line-clamp-1" title={p.nombre}>{p.nombre.substring(0, 20)}...</td>
                                            <td className="text-sm">{p.proveedor_nombre || '-'}</td>
                                            <td className="font-bold text-red-600">{p.stock_total}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
