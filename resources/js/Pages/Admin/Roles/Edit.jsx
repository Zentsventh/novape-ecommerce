import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Edit({ rol, permisos }) {
    const { data, setData, put, processing, errors } = useForm({
        nombre: rol.nombre,
        descripcion: rol.descripcion,
        permisos: rol.permisos ? rol.permisos.map(p => p.id) : [],
    });

    const isCoreRole = ['admin', 'cajero', 'almacen'].includes(rol.nombre);

    const togglePermiso = (permisoId) => {
        const currentPermisos = data.permisos || [];
        if (currentPermisos.includes(permisoId)) {
            setData('permisos', currentPermisos.filter(id => id !== permisoId));
        } else {
            setData('permisos', [...currentPermisos, permisoId]);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/roles/${rol.id}`);
    };

    return (
        <AdminLayout logoUrl={null}>
            <Head title="Editar Rol" />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Editar Rol</h1>
                <Link href="/admin/roles" style={{ color: 'var(--admin-text-muted)', textDecoration: 'none', fontWeight: '500' }}>
                    &larr; Volver a Roles
                </Link>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '30px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', maxWidth: '800px' }}>
                <form onSubmit={submit} style={{ display: 'grid', gap: '20px' }}>
                    
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Nombre Corto (Slug) *</label>
                            <input
                                type="text"
                                value={data.nombre}
                                onChange={e => setData('nombre', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: isCoreRole ? 'var(--admin-text-muted)' : 'var(--admin-text-main)', cursor: isCoreRole ? 'not-allowed' : 'text' }}
                                required
                                disabled={isCoreRole}
                            />
                            {isCoreRole && <small style={{ color: '#ef4444', display: 'block', marginTop: '4px' }}>Este es un rol base del sistema. Su nombre no se puede modificar.</small>}
                            {errors.nombre && <div style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px' }}>{errors.nombre}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Descripción Detallada *</label>
                            <input
                                type="text"
                                value={data.descripcion}
                                onChange={e => setData('descripcion', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                required
                            />
                            {errors.descripcion && <div style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px' }}>{errors.descripcion}</div>}
                        </div>
                    </div>

                    <div style={{ marginTop: '10px' }}>
                        <label style={{ display: 'block', marginBottom: '12px', fontWeight: 'bold', color: 'var(--admin-text-main)', fontSize: '16px' }}>
                            Módulos y Permisos del Sistema
                        </label>
                        <p style={{ color: 'var(--admin-text-muted)', marginBottom: '15px', fontSize: '13px' }}>
                            Selecciona las áreas del sistema a las que tendrá acceso este rol.
                        </p>
                        
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                            {permisos && permisos.map(p => {
                                const isSelected = data.permisos.includes(p.id);
                                return (
                                    <div 
                                        key={p.id}
                                        onClick={() => togglePermiso(p.id)}
                                        style={{
                                            padding: '16px',
                                            borderRadius: '12px',
                                            border: `2px solid ${isSelected ? '#1d4ed8' : 'var(--admin-border)'}`,
                                            background: isSelected ? 'rgba(29,78,216,0.03)' : 'var(--admin-bg-panel)',
                                            cursor: 'pointer',
                                            transition: 'all 0.2s ease',
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: '12px'
                                        }}
                                    >
                                        <div style={{ 
                                            width: '24px', height: '24px', borderRadius: '6px', 
                                            background: isSelected ? '#1d4ed8' : 'transparent', 
                                            border: `2px solid ${isSelected ? '#1d4ed8' : 'var(--admin-border)'}`,
                                            display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0
                                        }}>
                                            {isSelected && <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="3"><polyline points="20 6 9 17 4 12"></polyline></svg>}
                                        </div>
                                        <div>
                                            <div style={{ fontWeight: 'bold', color: isSelected ? '#1d4ed8' : 'var(--admin-text-main)' }}>{p.nombre}</div>
                                            <div style={{ fontSize: '11px', color: 'var(--admin-text-muted)', marginTop: '4px', lineHeight: '1.4' }}>
                                                {p.descripcion}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        {errors.permisos && <div style={{ color: '#ef4444', fontSize: '12px', marginTop: '8px' }}>Debe seleccionar al menos un permiso.</div>}
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '10px', paddingTop: '20px', borderTop: '1px solid var(--admin-border)' }}>
                        <Link 
                            href="/admin/roles" 
                            style={{ padding: '10px 20px', borderRadius: '8px', color: 'var(--admin-text-main)', textDecoration: 'none', fontWeight: '500' }}
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            style={{
                                background: '#1d4ed8',
                                color: 'white',
                                border: 'none',
                                padding: '10px 24px',
                                borderRadius: '8px',
                                fontWeight: 'bold',
                                cursor: processing ? 'not-allowed' : 'pointer',
                                opacity: processing ? 0.7 : 1,
                                boxShadow: '0 4px 6px rgba(29, 78, 216, 0.2)'
                            }}
                        >
                            {processing ? 'Guardando...' : 'Guardar Cambios'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
