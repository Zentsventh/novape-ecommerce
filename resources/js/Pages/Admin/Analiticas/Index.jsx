import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { 
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, ResponsiveContainer,
    PieChart, Pie, Cell, Legend
} from 'recharts';

export default function Index({ chartVentas, chartEstados, topProductos, kpis }) {
    // Definimos colores para los gráficos
    const COLORS = ['#8b5cf6', '#3b82f6', '#2563eb', '#1e40af', '#3b82f6', '#6366f1'];

    return (
        <AdminLayout logoUrl={null}>
            <Head title="Analíticas" />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Analíticas y Reportes</h1>
            </div>

            {/* KPIs */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '20px', marginBottom: '30px' }}>
                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', borderLeft: '4px solid #2563eb' }}>
                    <div style={{ color: 'var(--admin-text-muted)', fontSize: '14px', fontWeight: 'bold', marginBottom: '5px' }}>INGRESOS HISTÓRICOS</div>
                    <div style={{ fontSize: '28px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>S/ {kpis.ingresosHistorico.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </div>
                
                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', borderLeft: '4px solid #8b5cf6' }}>
                    <div style={{ color: 'var(--admin-text-muted)', fontSize: '14px', fontWeight: 'bold', marginBottom: '5px' }}>PEDIDOS DEL MES</div>
                    <div style={{ fontSize: '28px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{kpis.pedidosMes}</div>
                </div>
                
                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', borderLeft: '4px solid #3b82f6' }}>
                    <div style={{ color: 'var(--admin-text-muted)', fontSize: '14px', fontWeight: 'bold', marginBottom: '5px' }}>TICKET PROMEDIO (MES)</div>
                    <div style={{ fontSize: '28px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>S/ {kpis.ticketPromedio.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </div>
            </div>

            {/* Gráficos Principales */}
            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: '20px', marginBottom: '30px' }}>
                
                {/* Gráfico de Barras: Ventas últimos 6 meses */}
                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                    <h2 style={{ fontSize: '16px', fontWeight: 'bold', color: 'var(--admin-text-main)', marginBottom: '20px' }}>Ventas de los Últimos 6 Meses</h2>
                    <div style={{ height: '300px', width: '100%' }}>
                        <ResponsiveContainer>
                            <BarChart data={chartVentas} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.1)" vertical={false} />
                                <XAxis dataKey="mes" stroke="var(--admin-text-muted)" fontSize={12} tickLine={false} axisLine={false} />
                                <YAxis stroke="var(--admin-text-muted)" fontSize={12} tickLine={false} axisLine={false} tickFormatter={(value) => `S/${value}`} />
                                <RechartsTooltip 
                                    cursor={{fill: 'rgba(255,255,255,0.05)'}}
                                    contentStyle={{ background: '#1f2937', border: 'none', borderRadius: '8px', color: '#fff' }}
                                    formatter={(value) => [`S/ ${value}`, 'Total Ventas']}
                                />
                                <Bar dataKey="total" fill="#8b5cf6" radius={[4, 4, 0, 0]} barSize={40} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Gráfico de Pastel: Estados de Pedidos */}
                <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                    <h2 style={{ fontSize: '16px', fontWeight: 'bold', color: 'var(--admin-text-main)', marginBottom: '20px' }}>Estado Histórico de Pedidos</h2>
                    <div style={{ height: '300px', width: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                        {chartEstados.length > 0 ? (
                            <ResponsiveContainer>
                                <PieChart>
                                    <Pie
                                        data={chartEstados}
                                        cx="50%"
                                        cy="50%"
                                        innerRadius={60}
                                        outerRadius={90}
                                        paddingAngle={5}
                                        dataKey="value"
                                    >
                                        {chartEstados.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} stroke="transparent" />
                                        ))}
                                    </Pie>
                                    <RechartsTooltip 
                                        contentStyle={{ background: '#1f2937', border: 'none', borderRadius: '8px', color: '#fff' }}
                                    />
                                    <Legend iconType="circle" wrapperStyle={{ fontSize: '12px', color: 'var(--admin-text-main)' }} />
                                </PieChart>
                            </ResponsiveContainer>
                        ) : (
                            <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--admin-text-muted)' }}>
                                No hay datos de pedidos
                            </div>
                        )}
                    </div>
                </div>

            </div>

            {/* Top Productos */}
            <div style={{ background: 'var(--admin-bg-panel)', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                <h2 style={{ fontSize: '16px', fontWeight: 'bold', color: 'var(--admin-text-main)', marginBottom: '20px' }}>Top 5 Productos Más Vendidos</h2>
                
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                        <tr style={{ borderBottom: '2px solid var(--admin-border)', color: 'var(--admin-text-muted)' }}>
                            <th style={{ padding: '12px' }}>Producto</th>
                            <th style={{ padding: '12px', textAlign: 'center' }}>Unidades Vendidas</th>
                            <th style={{ padding: '12px', textAlign: 'right' }}>Ingresos Generados</th>
                        </tr>
                    </thead>
                    <tbody>
                        {topProductos.length > 0 ? topProductos.map((prod, index) => (
                            <tr key={index} style={{ borderBottom: '1px solid var(--admin-border)', transition: 'background 0.2s' }}>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)', fontWeight: 'bold' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                        <span style={{ 
                                            background: index === 0 ? '#1e40af' : index === 1 ? '#94a3b8' : index === 2 ? '#b45309' : 'rgba(255,255,255,0.1)', 
                                            color: index < 3 ? '#fff' : 'var(--admin-text-muted)', 
                                            width: '24px', height: '24px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px' 
                                        }}>
                                            {index + 1}
                                        </span>
                                        {prod.nombre}
                                    </div>
                                </td>
                                <td style={{ padding: '12px', textAlign: 'center', color: 'var(--admin-text-main)' }}>{prod.ventas}</td>
                                <td style={{ padding: '12px', textAlign: 'right', color: '#2563eb', fontWeight: 'bold' }}>S/ {prod.ingresos.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="3" style={{ padding: '20px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>No hay datos de ventas de productos.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

        </AdminLayout>
    );
}
