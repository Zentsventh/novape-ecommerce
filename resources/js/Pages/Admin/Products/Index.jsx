import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function Index({ productos, categorias, marcas, filters }) {
    const confirmDialog = useConfirm();

    const { delete: destroy } = useForm();
    const [search, setSearch] = useState(filters?.search || '');
    const [categoriaId, setCategoriaId] = useState(filters?.categoria_id || '');
    const [marcaId, setMarcaId] = useState(filters?.marca_id || '');

    const handleFilterChange = (field, value) => {
        let newFilters = { search, categoria_id: categoriaId, marca_id: marcaId, sort: filters?.sort, direction: filters?.direction };
        newFilters[field] = value;
        
        // UX: Si cambiamos la categoría, reseteamos la marca para evitar combinaciones inválidas
        if (field === 'categoria_id') {
            newFilters.marca_id = '';
            setMarcaId('');
        }
        
        router.get('/admin/products', newFilters, { preserveState: true });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/products', { search, categoria_id: categoriaId, marca_id: marcaId, sort: filters?.sort, direction: filters?.direction }, { preserveState: true });
    };

    const handleSort = (field) => {
        const direction = filters?.sort === field && filters?.direction === 'asc' ? 'desc' : 'asc';
        router.get('/admin/products', { search, categoria_id: categoriaId, marca_id: marcaId, sort: field, direction }, { preserveState: true });
    };

    const getSortIndicator = async (field) => {
        if (filters?.sort !== field) return null;
        return filters?.direction === 'asc' ? ' ↑' : ' ↓';
    };

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Estás seguro de que quieres eliminar este producto?')) {
            destroy(`/admin/products/${id}`);
        }
    };

    return (
        <AdminLayout>
            <Head title="Productos" />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Gestión de Productos</h1>
                <Link 
                    href="/admin/products/create" 
                    style={{ background: '#1d4ed8', color: 'white', padding: '10px 16px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '8px', boxShadow: '0 4px 10px rgba(29, 78, 216, 0.3)' }}
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 5v14M5 12h14"/></svg>
                    Nuevo Producto
                </Link>
            </div>

            {/* Buscador y Filtros Profesionales */}
            <div style={{ marginBottom: '20px', background: 'white', padding: '20px', borderRadius: '12px', boxShadow: '0 4px 15px rgba(0, 180, 255, 0.08)', borderLeft: '4px solid #00B4FF' }}>
                <form onSubmit={handleSearch} style={{ display: 'flex', gap: '15px', flexWrap: 'wrap', alignItems: 'flex-end' }}>
                    
                    {/* Categoria */}
                    <div style={{ minWidth: '180px', flex: 1 }}>
                        <div style={{ fontSize: '12px', fontWeight: 'bold', color: '#4A5568', marginBottom: '6px' }}>Categoría</div>
                        <select 
                            value={categoriaId} 
                            onChange={(e) => { setCategoriaId(e.target.value); handleFilterChange('categoria_id', e.target.value); }}
                            style={{ width: '100%', padding: '10px 15px', borderRadius: '8px', border: '1px solid #E2E8F0', outline: 'none', background: '#F7FAFC', color: '#2D3748', fontSize: '13px' }}
                        >
                            <option value="">Todas las categorías</option>
                            {categorias && categorias.map(c => <option key={c.id} value={c.id}>{c.nombre}</option>)}
                        </select>
                    </div>

                    {/* Marca */}
                    <div style={{ minWidth: '180px', flex: 1 }}>
                        <div style={{ fontSize: '12px', fontWeight: 'bold', color: '#4A5568', marginBottom: '6px' }}>Marca</div>
                        <select 
                            value={marcaId} 
                            onChange={(e) => { setMarcaId(e.target.value); handleFilterChange('marca_id', e.target.value); }}
                            style={{ width: '100%', padding: '10px 15px', borderRadius: '8px', border: '1px solid #E2E8F0', outline: 'none', background: '#F7FAFC', color: '#2D3748', fontSize: '13px' }}
                        >
                            <option value="">Todas las marcas</option>
                            {marcas && marcas.map(m => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                        </select>
                    </div>

                    {/* Search Input */}
                    <div style={{ flex: 2, minWidth: '300px' }}>
                        <div style={{ fontSize: '12px', fontWeight: 'bold', color: '#4A5568', marginBottom: '6px' }}>Buscar Producto (Nombre o SKU)</div>
                        <div style={{ display: 'flex', gap: '10px' }}>
                            <input 
                                type="text" 
                                placeholder="Escribe aquí para buscar..." 
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                style={{ flex: 1, padding: '10px 15px', borderRadius: '8px', border: '1px solid #E2E8F0', background: '#F7FAFC', outline: 'none', color: '#2D3748', fontSize: '13px' }}
                            />
                            <button type="submit" style={{ background: '#00B4FF', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: 'bold', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '5px', transition: 'background 0.2s', fontSize: '13px' }} onMouseOver={e=>e.target.style.background='#0082B8'} onMouseOut={e=>e.target.style.background='#00B4FF'}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> Buscar
                            </button>
                            {(filters?.search || filters?.categoria_id || filters?.marca_id) && (
                                <button type="button" onClick={() => { setSearch(''); setCategoriaId(''); setMarcaId(''); router.get('/admin/products'); }} style={{ background: '#E2E8F0', color: '#4A5568', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: 'bold', cursor: 'pointer', transition: 'background 0.2s', fontSize: '13px' }} onMouseOver={e=>e.target.style.background='#CBD5E0'} onMouseOut={e=>e.target.style.background='#E2E8F0'}>
                                    Limpiar
                                </button>
                            )}
                        </div>
                    </div>
                </form>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                        <tr style={{ borderBottom: '2px solid var(--admin-border)', color: 'var(--admin-text-muted)' }}>
                            <th style={{ padding: '12px', cursor: 'pointer', userSelect: 'none' }} onClick={() => handleSort('id')}>ID{getSortIndicator('id')}</th>
                            <th style={{ padding: '12px' }}>Imagen</th>
                            <th style={{ padding: '12px', cursor: 'pointer', userSelect: 'none' }} onClick={() => handleSort('nombre')}>Nombre{getSortIndicator('nombre')}</th>
                            <th style={{ padding: '12px', cursor: 'pointer', userSelect: 'none' }} onClick={() => handleSort('sku_base')}>SKU{getSortIndicator('sku_base')}</th>
                            <th style={{ padding: '12px' }}>Categoría</th>
                            <th style={{ padding: '12px' }}>Marca</th>
                            <th style={{ padding: '12px' }}>Estado</th>
                            <th style={{ padding: '12px', textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {productos.data && productos.data.length > 0 ? (
                            productos.data.map((producto) => (
                                <tr key={producto.id} style={{ borderBottom: '1px solid var(--admin-border)', transition: 'background 0.2s' }}>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>#{producto.id}</td>
                                    <td style={{ padding: '12px' }}>
                                        {producto.imagenes && producto.imagenes.length > 0 ? (
                                            <img src={producto.imagenes[0].url} alt={producto.nombre} style={{ width: '50px', height: '50px', objectFit: 'cover', borderRadius: '6px' }} />
                                        ) : (
                                            <div style={{ width: '50px', height: '50px', background: 'var(--admin-border)', borderRadius: '6px', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--admin-text-muted)', fontSize: '10px' }}>Sin foto</div>
                                        )}
                                    </td>
                                    <td style={{ padding: '12px', fontWeight: '500', color: 'var(--admin-text-main)' }}>{producto.nombre}</td>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>{producto.sku_base}</td>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>
                                        {producto.categorias && producto.categorias.length > 0 
                                            ? <span style={{ background: '#EBF8FF', color: '#0082B8', padding: '2px 8px', borderRadius: '4px', fontSize: '11px', fontWeight: 'bold' }}>{producto.categorias[0].nombre}</span>
                                            : '-'
                                        }
                                    </td>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>{producto.marca ? producto.marca.nombre : '-'}</td>
                                    <td style={{ padding: '12px' }}>
                                        <span style={{ padding: '4px 8px', borderRadius: '12px', fontSize: '12px', fontWeight: 'bold', background: producto.activo ? 'rgba(37,99,235,0.1)' : 'rgba(59,130,246,0.1)', color: producto.activo ? '#2563eb' : '#3b82f6' }}>
                                            {producto.activo ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </td>
                                    <td style={{ padding: '12px', textAlign: 'right' }}>
                                        <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                                            <Link href={`/admin/products/${producto.id}`} title="Ver y Analizar" style={{ color: '#10b981', textDecoration: 'none', padding: '6px', borderRadius: '6px', background: 'rgba(16, 185, 129, 0.1)' }}>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                            </Link>
                                            <Link href={`/admin/products/${producto.id}/edit`} title="Editar" style={{ color: '#3b82f6', textDecoration: 'none', padding: '6px', borderRadius: '6px', background: 'rgba(59,130,246,0.1)' }}>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </Link>
                                            <button onClick={() => handleDelete(producto.id)} title="Eliminar" style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#ef4444', border: 'none', cursor: 'pointer', padding: '6px', borderRadius: '6px' }}>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="7" style={{ padding: '20px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>No hay productos registrados.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Paginación */}
            {productos.links && (
                <div style={{ padding: '20px 0', display: 'flex', gap: '5px', justifyContent: 'center', flexWrap: 'wrap' }}>
                    {productos.links.map((link, k) => (
                        <Link 
                            key={k} 
                            href={link.url || '#'} 
                            style={{ 
                                padding: '8px 12px', 
                                background: link.active ? 'var(--admin-text-main)' : 'var(--admin-bg-panel)', 
                                color: link.active ? 'white' : 'var(--admin-text-main)', 
                                borderRadius: '6px', 
                                border: '1px solid var(--admin-border)',
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
