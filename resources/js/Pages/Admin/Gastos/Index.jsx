import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function GastosIndex({ gastos, totalGastos, logoUrl, filters = {} }) {
    const confirmDialog = useConfirm();

    const [showModal, setShowModal] = useState(false);
    const [editingGasto, setEditingGasto] = useState(null);
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');
    const [search, setSearch] = useState(filters.search || '');
    const [categoria, setCategoria] = useState(filters.categoria || 'Todos');
    const { data, setData, post, put, processing, reset, errors } = useForm({
        concepto: '',
        monto: '',
        categoria: 'Operativo',
        tipo: 'variable',
        fecha_gasto: new Date().toISOString().split('T')[0]
    });

    const openCreateModal = () => {
        setEditingGasto(null);
        reset();
        setShowModal(true);
    };

    const openEditModal = (gasto) => {
        setEditingGasto(gasto);
        setData({
            concepto: gasto.concepto,
            monto: gasto.monto,
            categoria: gasto.categoria,
            tipo: gasto.tipo,
            fecha_gasto: gasto.fecha_gasto.split('T')[0] || gasto.fecha_gasto
        });
        setShowModal(true);
    };

    const submit = async (e) => {
        e.preventDefault();
        if (editingGasto) {
            put(`/admin/gastos/${editingGasto.id}`, {
                onSuccess: () => {
                    setShowModal(false);
                    reset();
                }
            });
        } else {
            post('/admin/gastos', {
                onSuccess: () => {
                    setShowModal(false);
                    reset();
                }
            });
        }
    };

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Eliminar este gasto?')) {
            router.delete(`/admin/gastos/${id}`);
        }
    };

    const applyFilters = (overrideCategoria = categoria, overrideSearch = search) => {
        router.get('/admin/gastos', {
            start_date: startDate,
            end_date: endDate,
            search: overrideSearch,
            categoria: overrideCategoria
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setStartDate('');
        setEndDate('');
        setSearch('');
        setCategoria('Todos');
        router.get('/admin/gastos', { start_date: '', end_date: '', search: '', categoria: '' }, { preserveState: true });
    };

    const handleCategoriaClick = (cat) => {
        setCategoria(cat);
        applyFilters(cat, search);
    };

    const handleSearch = (e) => {
        e.preventDefault();
        applyFilters(categoria, search);
    };

    const categoriasList = ['Todos', 'Operativo', 'Infraestructura TI', 'Planilla', 'Marketing', 'Software', 'Administrativo', 'Otros'];

    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title="Control de Gastos" />

            <div className="admin-content-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px', flexWrap: 'wrap', gap: '15px' }}>
                <div>
                    <h1 className="admin-page-title" style={{ fontSize: '28px', margin: 0, fontWeight: '700', color: 'var(--admin-text-main)' }}>Control de Gastos</h1>
                </div>

                <div style={{ display: 'flex', gap: '15px', alignItems: 'center', flexWrap: 'wrap' }}>
                    <form onSubmit={handleSearch} style={{ display: 'flex', alignItems: 'center', background: 'var(--admin-bg-panel)', padding: '6px 12px', borderRadius: '8px', border: '1px solid var(--admin-border)' }}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--admin-text-muted)" strokeWidth="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" placeholder="Buscar personal o servicio..." value={search} onChange={e => setSearch(e.target.value)} style={{ border: 'none', background: 'transparent', color: 'var(--admin-text-main)', outline: 'none', fontSize: '13px', marginLeft: '8px', width: '200px' }} />
                        <button type="submit" style={{ display: 'none' }}>Buscar</button>
                    </form>
                    
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', background: 'var(--admin-bg-panel)', padding: '6px 12px', borderRadius: '8px', border: '1px solid var(--admin-border)' }}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--admin-text-muted)" strokeWidth="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <input type="date" value={startDate} onChange={e => setStartDate(e.target.value)} style={{ border: 'none', background: 'transparent', color: 'var(--admin-text-main)', outline: 'none', fontSize: '13px' }} />
                        <span style={{ color: 'var(--admin-text-muted)', fontSize: '12px' }}>hasta</span>
                        <input type="date" value={endDate} onChange={e => setEndDate(e.target.value)} style={{ border: 'none', background: 'transparent', color: 'var(--admin-text-main)', outline: 'none', fontSize: '13px' }} />
                        <button onClick={() => applyFilters(categoria, search)} style={{ background: '#3b82f6', color: 'white', border: 'none', borderRadius: '6px', padding: '4px 12px', cursor: 'pointer', marginLeft: '5px', fontSize: '12px', fontWeight: 'bold' }}>Filtrar</button>
                        {(startDate || endDate || search || categoria !== 'Todos') && <button onClick={clearFilters} style={{ background: 'rgba(100,116,139,0.1)', color: '#64748B', border: 'none', borderRadius: '6px', padding: '4px 8px', cursor: 'pointer', marginLeft: '5px', fontSize: '12px', fontWeight: 'bold' }}>X</button>}
                    </div>

                    <button 
                        onClick={openCreateModal}
                        className="admin-btn-primary" 
                        style={{ background: 'var(--admin-primary)', color: 'white', padding: '10px 20px', borderRadius: '8px', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}
                    >
                        + Nuevo Gasto
                    </button>
                </div>
            </div>

            <div style={{ display: 'flex', gap: '10px', marginBottom: '20px', flexWrap: 'wrap' }}>
                {categoriasList.map(cat => (
                    <button 
                        key={cat}
                        onClick={() => handleCategoriaClick(cat)}
                        style={{ 
                            padding: '6px 14px', 
                            borderRadius: '20px', 
                            fontSize: '13px', 
                            fontWeight: '600',
                            cursor: 'pointer',
                            border: categoria === cat ? 'none' : '1px solid var(--admin-border)',
                            background: categoria === cat ? 'var(--admin-primary)' : 'var(--admin-bg-panel)',
                            color: categoria === cat ? 'white' : 'var(--admin-text-secondary)',
                            transition: 'all 0.2s'
                        }}
                    >
                        {cat}
                    </button>
                ))}
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))', gap: '20px', marginBottom: '30px' }}>
                <div style={{ background: 'var(--admin-card-bg)', padding: '20px', borderRadius: '12px', boxShadow: 'var(--admin-shadow-sm)' }}>
                    <div style={{ fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)' }}>GASTOS TOTALES</div>
                    <div style={{ fontSize: '28px', fontWeight: 'bold', color: '#3b82f6' }}>S/ {Number(totalGastos).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </div>
            </div>

            <div className="admin-card" style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', overflowX: 'auto', boxShadow: 'var(--admin-shadow-sm)' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead style={{ background: 'var(--admin-bg)', borderBottom: '1px solid var(--admin-border)' }}>
                        <tr>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>FECHA</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>CONCEPTO</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>CATEGORÍA</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>MONTO</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        {gastos.data.length > 0 ? gastos.data.map(g => (
                            <tr key={g.id} style={{ borderBottom: '1px solid var(--admin-border-light)' }}>
                                <td style={{ padding: '15px 20px' }}>{g.fecha_gasto}</td>
                                <td style={{ padding: '15px 20px', fontWeight: '500' }}>{g.concepto}</td>
                                <td style={{ padding: '15px 20px' }}>
                                    <span style={{ background: 'var(--admin-bg)', padding: '4px 8px', borderRadius: '4px', fontSize: '12px' }}>{g.categoria}</span>
                                </td>
                                <td style={{ padding: '15px 20px', fontWeight: 'bold', color: '#3b82f6' }}>S/ {Number(g.monto).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td style={{ padding: '15px 20px' }}>
                                    <div style={{ display: 'flex', gap: '10px' }}>
                                        <button onClick={() => openEditModal(g)} style={{ color: 'var(--admin-primary)', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>Editar</button>
                                        <button onClick={() => handleDelete(g.id)} style={{ color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="5" style={{ padding: '40px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>No hay gastos registrados aún.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {showModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
                    <div style={{ background: 'white', padding: '30px', borderRadius: '12px', width: '400px' }}>
                        <h2 style={{ margin: '0 0 20px 0' }}>{editingGasto ? 'Editar Gasto' : 'Registrar Gasto'}</h2>
                        <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Concepto</label>
                                <input type="text" value={data.concepto} onChange={e => setData('concepto', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Monto (S/)</label>
                                <input type="number" step="0.01" value={data.monto} onChange={e => setData('monto', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Categoría</label>
                                <select value={data.categoria} onChange={e => setData('categoria', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }}>
                                    <option value="Operativo">Operativo (Luz, Agua, Local)</option>
                                    <option value="Infraestructura TI">Infraestructura TI</option>
                                    <option value="Marketing">Marketing / Publicidad</option>
                                    <option value="Planilla">Planilla / Sueldos</option>
                                    <option value="Software">Software y Suscripciones</option>
                                    <option value="Administrativo">Administrativo</option>
                                    <option value="Otros">Otros Gastos</option>
                                </select>
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Fecha</label>
                                <input type="date" value={data.fecha_gasto} onChange={e => setData('fecha_gasto', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required />
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
