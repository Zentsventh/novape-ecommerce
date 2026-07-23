import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function Index() {
    const confirmDialog = useConfirm();

    const { roles, filtros, flash, errors } = usePage().props;
    const data = roles?.data || [];
    
    const [search, setSearch] = useState(filtros?.buscar || '');

    const handleSearch = async (e) => {
        e.preventDefault();
        router.get('/admin/roles', { buscar: search }, { preserveState: true });
    };

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Estás seguro de eliminar este rol? Esta acción no se puede deshacer.')) {
            router.delete(`/admin/roles/${id}`, { preserveScroll: true });
        }
    };

    return (
        <AdminLayout logoUrl={null}>
            <Head title="Roles y Permisos" />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Roles y Permisos</h1>
                <Link 
                    href="/admin/roles/create" 
                    style={{ background: '#1d4ed8', color: 'white', padding: '10px 16px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '8px', boxShadow: '0 4px 10px rgba(29, 78, 216, 0.3)' }}
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 5v14M5 12h14"/></svg>
                    Nuevo Rol
                </Link>
            </div>

            {flash?.success && (
                <div style={{ background: 'rgba(37,99,235,0.1)', color: '#2563eb', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', fontWeight: '500', border: '1px solid rgba(37,99,235,0.2)' }}>
                    {flash.success}
                </div>
            )}
            
            {(flash?.error || errors?.error) && (
                <div style={{ background: 'rgba(239,68,68,0.1)', color: '#ef4444', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', fontWeight: '500', border: '1px solid rgba(239,68,68,0.2)' }}>
                    {flash?.error || errors?.error}
                </div>
            )}

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', marginBottom: '20px' }}>
                <form onSubmit={handleSearch} style={{ display: 'flex', gap: '10px' }}>
                    <input 
                        type="text" 
                        placeholder="Buscar rol por nombre o descripción..." 
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        style={{ flex: 1, padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                    />
                    <button type="submit" style={{ background: '#4b5563', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: 'bold', cursor: 'pointer' }}>
                        Buscar
                    </button>
                    {search && (
                        <Link href="/admin/roles" style={{ background: 'transparent', border: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', padding: '10px 20px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center' }}>
                            Limpiar
                        </Link>
                    )}
                </form>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                        <tr style={{ borderBottom: '2px solid var(--admin-border)', color: 'var(--admin-text-muted)' }}>
                            <th style={{ padding: '12px' }}>ID</th>
                            <th style={{ padding: '12px' }}>Rol (Slug)</th>
                            <th style={{ padding: '12px' }}>Descripción</th>
                            <th style={{ padding: '12px' }}>Usuarios Asignados</th>
                            <th style={{ padding: '12px', textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.length > 0 ? data.map(rol => (
                            <tr key={rol.id} style={{ borderBottom: '1px solid var(--admin-border)', transition: 'all 0.2s' }}>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>#{rol.id}</td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>
                                    <span style={{ fontWeight: 'bold', background: 'rgba(29,78,216,0.1)', color: '#1d4ed8', padding: '4px 8px', borderRadius: '6px' }}>{rol.nombre}</span>
                                </td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>
                                    {rol.descripcion}
                                </td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>
                                    <strong>{rol.usuarios_count}</strong>
                                </td>
                                <td style={{ padding: '12px', textAlign: 'right' }}>
                                    <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end', flexWrap: 'wrap' }}>
                                        <Link href={`/admin/roles/${rol.id}/edit`} style={{ color: '#1d4ed8', textDecoration: 'none', padding: '6px', borderRadius: '6px', background: 'rgba(29,78,216,0.1)' }} title="Editar">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </Link>
                                        {!['admin', 'cajero', 'almacen'].includes(rol.nombre) && (
                                            <button onClick={() => handleDelete(rol.id)} style={{ background: 'rgba(239,68,68,0.1)', color: '#ef4444', border: 'none', cursor: 'pointer', padding: '6px', borderRadius: '6px' }} title="Eliminar">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="5" style={{ padding: '20px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>
                                    No se encontraron roles.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
