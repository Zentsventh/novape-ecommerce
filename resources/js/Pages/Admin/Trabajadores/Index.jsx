import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Index() {
    const { trabajadores, filtros, flash, errors } = usePage().props;
    const data = trabajadores?.data || [];
    
    const [search, setSearch] = useState(filtros?.buscar || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/trabajadores', { buscar: search }, { preserveState: true });
    };

    const handleDelete = (id) => {
        if (confirm('¿Estás seguro de eliminar este trabajador? (Mover a la papelera)')) {
            router.delete(`/admin/trabajadores/${id}`, { preserveScroll: true });
        }
    };

    const toggleBloqueo = (id) => {
        router.post(`/admin/trabajadores/${id}/bloquear`, {}, { preserveScroll: true });
    };

    const resetPassword = (id) => {
        if (confirm('¿Estás seguro de restablecer la contraseña a Novape2026!?')) {
            router.post(`/admin/trabajadores/${id}/reset-password`, {}, { preserveScroll: true });
        }
    };

    return (
        <AdminLayout logoUrl={null}>
            <Head title="Trabajadores CRM" />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Directorio de Trabajadores</h1>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <a 
                        href="/admin/exportar/trabajadores" 
                        target="_blank"
                        style={{ background: '#2563eb', color: 'white', padding: '10px 16px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '8px', boxShadow: '0 4px 10px rgba(37, 99, 235, 0.3)' }}
                    >
                        Exportar CSV
                    </a>
                    <Link 
                        href="/admin/trabajadores/create" 
                        style={{ background: '#1d4ed8', color: 'white', padding: '10px 16px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center', gap: '8px', boxShadow: '0 4px 10px rgba(29, 78, 216, 0.3)' }}
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 5v14M5 12h14"/></svg>
                        Nuevo Trabajador
                    </Link>
                </div>
            </div>

            {flash?.success && (
                <div style={{ background: 'rgba(37,99,235,0.1)', color: '#2563eb', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', fontWeight: '500', border: '1px solid rgba(37,99,235,0.2)' }}>
                    {flash.success}
                </div>
            )}
            
            {(flash?.error || errors?.error) && (
                <div style={{ background: 'rgba(59,130,246,0.1)', color: '#3b82f6', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', fontWeight: '500', border: '1px solid rgba(59,130,246,0.2)' }}>
                    {flash?.error || errors?.error}
                </div>
            )}

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', marginBottom: '20px' }}>
                <form onSubmit={handleSearch} style={{ display: 'flex', gap: '10px' }}>
                    <input 
                        type="text" 
                        placeholder="Buscar por nombre, email o DNI..." 
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        style={{ flex: 1, padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                    />
                    <button type="submit" style={{ background: '#4b5563', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '8px', fontWeight: 'bold', cursor: 'pointer' }}>
                        Buscar
                    </button>
                    {search && (
                        <Link href="/admin/trabajadores" style={{ background: 'transparent', border: '1px solid var(--admin-border)', color: 'var(--admin-text-muted)', padding: '10px 20px', borderRadius: '8px', textDecoration: 'none', fontWeight: 'bold', display: 'flex', alignItems: 'center' }}>
                            Limpiar
                        </Link>
                    )}
                </form>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                        <tr style={{ borderBottom: '2px solid var(--admin-border)', color: 'var(--admin-text-muted)' }}>
                            <th style={{ padding: '12px' }}>Usuario</th>
                            <th style={{ padding: '12px' }}>DNI / Teléfono</th>
                            <th style={{ padding: '12px' }}>Roles</th>
                            <th style={{ padding: '12px' }}>Pedidos</th>
                            <th style={{ padding: '12px' }}>Estado</th>
                            <th style={{ padding: '12px', textAlign: 'right' }}>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.length > 0 ? data.map(trabajador => (
                            <tr key={trabajador.id} style={{ 
                                borderBottom: '1px solid var(--admin-border)', 
                                transition: 'all 0.2s',
                                background: trabajador.estado === 'bloqueado' ? 'rgba(239,68,68,0.05)' : 'transparent', 
                                opacity: trabajador.estado === 'bloqueado' ? 0.75 : 1 
                            }}>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>
                                    <div style={{ fontWeight: 'bold', textDecoration: trabajador.estado === 'bloqueado' ? 'line-through' : 'none' }}>{trabajador.nombres} {trabajador.apellidos}</div>
                                    <div style={{ fontSize: '12px', color: 'var(--admin-text-muted)' }}>{trabajador.email}</div>
                                </td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)' }}>
                                    <div>{trabajador.dni || '-'}</div>
                                    <div style={{ fontSize: '12px', color: 'var(--admin-text-muted)' }}>{trabajador.telefono || '-'}</div>
                                </td>
                                <td style={{ padding: '12px' }}>
                                    {trabajador.roles && trabajador.roles.length > 0 ? trabajador.roles.map(r => (
                                        <span key={r.id} style={{ background: 'rgba(29,78,216,0.1)', color: '#1d4ed8', padding: '2px 6px', borderRadius: '6px', fontSize: '11px', marginRight: '4px', display: 'inline-block' }}>
                                            {r.nombre}
                                        </span>
                                    )) : <span style={{ color: 'var(--admin-text-muted)', fontSize: '12px' }}>Sin rol</span>}
                                </td>
                                <td style={{ padding: '12px', color: 'var(--admin-text-main)', fontWeight: 'bold' }}>
                                    {trabajador.pedidos_count}
                                </td>
                                <td style={{ padding: '12px' }}>
                                    <span style={{ 
                                        background: trabajador.estado === 'activo' ? 'rgba(37,99,235,0.1)' : 'rgba(59,130,246,0.1)', 
                                        color: trabajador.estado === 'activo' ? '#2563eb' : '#3b82f6', 
                                        padding: '4px 8px', borderRadius: '12px', fontSize: '12px', fontWeight: 'bold', textTransform: 'capitalize' 
                                    }}>
                                        {trabajador.estado === 'bloqueado' ? 'Bloqueado' : 'Activo'}
                                    </span>
                                </td>
                                <td style={{ padding: '12px', textAlign: 'right' }}>
                                    <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end', flexWrap: 'wrap' }}>
                                        <Link href={`/admin/trabajadores/${trabajador.id}`} style={{ color: '#3b82f6', textDecoration: 'none', padding: '6px', borderRadius: '6px', background: 'rgba(59,130,246,0.1)' }} title="Ver Detalle">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </Link>
                                        <Link href={`/admin/trabajadores/${trabajador.id}/edit`} style={{ color: '#1d4ed8', textDecoration: 'none', padding: '6px', borderRadius: '6px', background: 'rgba(29,78,216,0.1)' }} title="Editar">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </Link>
                                        <button onClick={() => toggleBloqueo(trabajador.id)} style={{ background: trabajador.estado === 'bloqueado' ? 'rgba(37,99,235,0.1)' : 'rgba(96,165,250,0.1)', color: trabajador.estado === 'bloqueado' ? '#2563eb' : '#60a5fa', border: 'none', cursor: 'pointer', padding: '6px', borderRadius: '6px' }} title={trabajador.estado === 'bloqueado' ? 'Desbloquear' : 'Bloquear'}>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        </button>
                                        <button onClick={() => resetPassword(trabajador.id)} style={{ background: 'rgba(107,114,128,0.1)', color: 'var(--admin-text-main)', border: 'none', cursor: 'pointer', padding: '6px', borderRadius: '6px' }} title="Resetear Contraseña">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M2 12h4l2-2 4 4 4-4 4 4 2-2"/></svg>
                                        </button>
                                        <button onClick={() => handleDelete(trabajador.id)} style={{ background: 'rgba(59,130,246,0.1)', color: '#3b82f6', border: 'none', cursor: 'pointer', padding: '6px', borderRadius: '6px' }} title="Eliminar">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="6" style={{ padding: '20px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>
                                    No hay trabajadores registrados o que coincidan con la búsqueda.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {trabajadores?.links && trabajadores.links.length > 3 && (
                <div style={{ display: 'flex', justifyContent: 'center', marginTop: '20px', gap: '5px' }}>
                    {trabajadores.links.map((link, i) => (
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
