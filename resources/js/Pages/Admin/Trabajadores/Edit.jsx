import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Edit({ trabajador, roles }) {
    const { data, setData, put, processing, errors } = useForm({
        nombres: trabajador.nombres,
        apellidos: trabajador.apellidos,
        email: trabajador.email,
        password: '',
        roles: trabajador.roles ? trabajador.roles.map(r => r.id) : [],
        dni: trabajador.dni || '',
        telefono: trabajador.telefono || '',
    });

    const toggleRole = (roleId) => {
        const currentRoles = data.roles || [];
        if (currentRoles.includes(roleId)) {
            setData('roles', currentRoles.filter(id => id !== roleId));
        } else {
            setData('roles', [...currentRoles, roleId]);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        put(`/admin/trabajadores/${trabajador.id}`);
    };

    return (
        <AdminLayout logoUrl={null}>
            <Head title="Editar Usuario" />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Editar Usuario</h1>
                <Link href="/admin/trabajadores" style={{ color: 'var(--admin-text-muted)', textDecoration: 'none', fontWeight: '500' }}>
                    &larr; Volver a Usuarios
                </Link>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '30px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', maxWidth: '800px' }}>
                <form onSubmit={submit} style={{ display: 'grid', gap: '20px' }}>
                    
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Nombres *</label>
                            <input
                                type="text"
                                value={data.nombres}
                                onChange={e => setData('nombres', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                required
                            />
                            {errors.nombres && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.nombres}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Apellidos *</label>
                            <input
                                type="text"
                                value={data.apellidos}
                                onChange={e => setData('apellidos', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                required
                            />
                            {errors.apellidos && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.apellidos}</div>}
                        </div>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Email *</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={e => setData('email', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                required
                            />
                            {errors.email && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.email}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Nueva Contraseña</label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={e => setData('password', e.target.value)}
                                placeholder="Dejar en blanco para no cambiar"
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                            />
                            {errors.password && <div style={{ color: '#ef4444', fontSize: '12px', marginTop: '4px' }}>{errors.password}</div>}
                        </div>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>DNI</label>
                            <input
                                type="text"
                                value={data.dni}
                                onChange={e => setData('dni', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                            />
                            {errors.dni && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.dni}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Teléfono</label>
                            <input
                                type="text"
                                value={data.telefono}
                                onChange={e => setData('telefono', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                            />
                            {errors.telefono && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.telefono}</div>}
                        </div>
                    </div>

                    <div>
                        <label style={{ display: 'block', marginBottom: '12px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Roles del Usuario *</label>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: '15px' }}>
                            {roles.map(r => {
                                const isSelected = data.roles.includes(r.id);
                                return (
                                    <div 
                                        key={r.id}
                                        onClick={() => toggleRole(r.id)}
                                        style={{
                                            padding: '16px',
                                            borderRadius: '12px',
                                            border: `2px solid ${isSelected ? '#1d4ed8' : 'var(--admin-border)'}`,
                                            background: isSelected ? 'rgba(138,43,226,0.03)' : 'var(--admin-bg-panel)',
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
                                            <div style={{ fontWeight: 'bold', color: isSelected ? '#1d4ed8' : 'var(--admin-text-main)', textTransform: 'capitalize' }}>{r.nombre}</div>
                                            <div style={{ fontSize: '11px', color: 'var(--admin-text-muted)', marginTop: '4px', lineHeight: '1.4' }}>
                                                {r.descripcion}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        {errors.roles && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '8px' }}>Debe seleccionar al menos un rol para el usuario.</div>}
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '10px', paddingTop: '20px', borderTop: '1px solid var(--admin-border)' }}>
                        <Link 
                            href="/admin/trabajadores" 
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
                                boxShadow: '0 4px 10px rgba(29, 78, 216, 0.3)'
                            }}
                        >
                            {processing ? 'Actualizando...' : 'Actualizar Usuario'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
