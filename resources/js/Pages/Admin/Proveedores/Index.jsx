import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function ProveedorIndex({ proveedores, filters }) {
    const [search, setSearch] = useState(filters?.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/proveedores', { search, sort: filters?.sort, direction: filters?.direction }, { preserveState: true });
    };

    const handleSort = (column) => {
        const direction = filters?.sort === column && filters?.direction === 'asc' ? 'desc' : 'asc';
        router.get('/admin/proveedores', { search, sort: column, direction }, { preserveState: true });
    };

    const handleDelete = (id) => {
        if (confirm('¿Estás seguro de que deseas eliminar este proveedor?')) {
            router.delete(`/admin/proveedores/${id}`);
        }
    };

    const getSortIndicator = (field) => {
        if (filters?.sort !== field) return null;
        return filters?.direction === 'asc' ? ' ↑' : ' ↓';
    };

    return (
        <AdminLayout>
            <Head title="Proveedores" />

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Gestión de Proveedores</h1>
                <Link 
                    href="/admin/proveedores/create" 
                    style={{ background: '#1d4ed8', color: 'white', padding: '10px 16px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '8px', boxShadow: '0 4px 10px rgba(29, 78, 216, 0.3)' }}
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 5v14M5 12h14"/></svg>
                    Nuevo Proveedor
                </Link>
            </div>

            {/* Buscador */}
            <div style={{ marginBottom: '20px', background: 'var(--admin-bg-panel)', padding: '15px', borderRadius: '12px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                <form onSubmit={handleSearch} style={{ display: 'flex', gap: '10px' }}>
                    <input 
                        type="text" 
                        placeholder="Buscar por nombre, RUC o email..." 
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        style={{ flex: 1, padding: '10px 15px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'var(--admin-bg-body)', color: 'var(--admin-text-main)' }}
                    />
                    <button type="submit" style={{ background: '#3b82f6', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: 'bold', cursor: 'pointer' }}>
                        Buscar
                    </button>
                    {filters?.search && (
                        <button type="button" onClick={() => { setSearch(''); router.get('/admin/proveedores'); }} style={{ background: '#3b82f6', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: 'bold', cursor: 'pointer' }}>
                            Limpiar
                        </button>
                    )}
                </form>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                        <tr style={{ borderBottom: '2px solid var(--admin-border)', color: 'var(--admin-text-muted)' }}>
                            <th style={{ padding: '12px', cursor: 'pointer', userSelect: 'none' }} onClick={() => handleSort('id')}>ID{getSortIndicator('id')}</th>
                            <th style={{ padding: '12px', cursor: 'pointer', userSelect: 'none' }} onClick={() => handleSort('nombre')}>Nombre{getSortIndicator('nombre')}</th>
                            <th style={{ padding: '12px', cursor: 'pointer', userSelect: 'none' }} onClick={() => handleSort('ruc')}>RUC{getSortIndicator('ruc')}</th>
                            <th style={{ padding: '12px' }}>Contacto</th>
                            <th style={{ padding: '12px' }}>Teléfono</th>
                            <th style={{ padding: '12px' }}>Estado</th>
                            <th style={{ padding: '12px', textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {proveedores.data && proveedores.data.length > 0 ? (
                            proveedores.data.map((prov) => (
                                <tr key={prov.id} style={{ borderBottom: '1px solid var(--admin-border)', transition: 'background 0.2s' }}>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>#{prov.id}</td>
                                    <td style={{ padding: '12px', fontWeight: '500', color: 'var(--admin-text-main)' }}>{prov.nombre}</td>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>{prov.ruc || '-'}</td>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>{prov.contacto || '-'}</td>
                                    <td style={{ padding: '12px', color: 'var(--admin-text-muted)' }}>{prov.telefono || '-'}</td>
                                    <td style={{ padding: '12px' }}>
                                        <span style={{ padding: '4px 8px', borderRadius: '12px', fontSize: '12px', fontWeight: 'bold', background: prov.activo ? 'rgba(37,99,235,0.1)' : 'rgba(59,130,246,0.1)', color: prov.activo ? '#2563eb' : '#3b82f6' }}>
                                            {prov.activo ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </td>
                                    <td style={{ padding: '12px', textAlign: 'right' }}>
                                        <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end' }}>
                                            <Link href={`/admin/proveedores/${prov.id}/edit`} style={{ color: '#3b82f6', textDecoration: 'none', padding: '6px', borderRadius: '6px', background: 'rgba(59,130,246,0.1)' }}>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            </Link>
                                            <button onClick={() => handleDelete(prov.id)} style={{ background: 'rgba(59,130,246,0.1)', color: '#3b82f6', border: 'none', cursor: 'pointer', padding: '6px', borderRadius: '6px' }}>
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="7" style={{ padding: '20px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>No se encontraron proveedores.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Paginación */}
            {proveedores.links && (
                <div style={{ padding: '20px 0', display: 'flex', gap: '5px', justifyContent: 'center', flexWrap: 'wrap' }}>
                    {proveedores.links.map((link, k) => (
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
