import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function ProveedorForm({ proveedor }) {
    const isEditing = !!proveedor;

    const { data, setData, post, put, processing, errors } = useForm({
        nombre: proveedor?.nombre || '',
        ruc: proveedor?.ruc || '',
        direccion: proveedor?.direccion || '',
        telefono: proveedor?.telefono || '',
        email: proveedor?.email || '',
        contacto: proveedor?.contacto || '',
        activo: proveedor?.activo ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (isEditing) {
            put(`/admin/proveedores/${proveedor.id}`);
        } else {
            post('/admin/proveedores');
        }
    };

    return (
        <AdminLayout>
            <Head title={isEditing ? 'Editar Proveedor' : 'Nuevo Proveedor'} />

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>
                    {isEditing ? 'Editar Proveedor' : 'Crear Nuevo Proveedor'}
                </h1>
                <Link href="/admin/proveedores" style={{ color: 'var(--admin-text-muted)', textDecoration: 'none', fontWeight: '500' }}>
                    &larr; Volver a Proveedores
                </Link>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '30px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                <form onSubmit={handleSubmit} style={{ display: 'grid', gap: '24px' }}>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Nombre / Razón Social <span style={{ color: '#3b82f6' }}>*</span></label>
                            <input 
                                type="text" 
                                value={data.nombre} 
                                onChange={e => setData('nombre', e.target.value)}
                                placeholder="Ej. TechNova S.A."
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: errors.nombre ? '1px solid #3b82f6' : '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }} 
                            />
                            {errors.nombre && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.nombre}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>RUC</label>
                            <input 
                                type="text" 
                                value={data.ruc} 
                                onChange={e => setData('ruc', e.target.value)}
                                placeholder="Ej. 20123456781"
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: errors.ruc ? '1px solid #3b82f6' : '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }} 
                            />
                            {errors.ruc && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.ruc}</div>}
                        </div>
                    </div>

                    <div>
                        <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Dirección</label>
                        <input 
                            type="text" 
                            value={data.direccion} 
                            onChange={e => setData('direccion', e.target.value)}
                            placeholder="Ej. Av. Principal 123"
                            style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: errors.direccion ? '1px solid #3b82f6' : '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }} 
                        />
                        {errors.direccion && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.direccion}</div>}
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Teléfono</label>
                            <input 
                                type="text" 
                                value={data.telefono} 
                                onChange={e => setData('telefono', e.target.value)}
                                placeholder="Ej. 987654321"
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: errors.telefono ? '1px solid #3b82f6' : '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }} 
                            />
                            {errors.telefono && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.telefono}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Email Comercial</label>
                            <input 
                                type="email" 
                                value={data.email} 
                                onChange={e => setData('email', e.target.value)}
                                placeholder="Ej. ventas@empresa.com"
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: errors.email ? '1px solid #3b82f6' : '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }} 
                            />
                            {errors.email && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.email}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Persona de Contacto</label>
                            <input 
                                type="text" 
                                value={data.contacto} 
                                onChange={e => setData('contacto', e.target.value)}
                                placeholder="Ej. Juan Pérez"
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: errors.contacto ? '1px solid #3b82f6' : '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }} 
                            />
                            {errors.contacto && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.contacto}</div>}
                        </div>
                    </div>

                    <div>
                        <label style={{ display: 'flex', alignItems: 'center', cursor: 'pointer' }}>
                            <input 
                                type="checkbox" 
                                checked={data.activo} 
                                onChange={e => setData('activo', e.target.checked)}
                                style={{ width: '18px', height: '18px', marginRight: '10px', accentColor: '#3b82f6' }}
                            />
                            <span style={{ fontWeight: '500', color: 'var(--admin-text-main)' }}>Proveedor Activo (puede ser usado en productos nuevos)</span>
                        </label>
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '20px', borderTop: '1px solid var(--admin-border)', paddingTop: '20px' }}>
                        <button 
                            type="button" 
                            onClick={() => window.history.back()}
                            style={{ padding: '10px 20px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)', fontWeight: 'bold', cursor: 'pointer' }}
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            disabled={processing}
                            style={{ padding: '10px 20px', borderRadius: '8px', border: 'none', background: '#3b82f6', color: 'white', fontWeight: 'bold', cursor: processing ? 'not-allowed' : 'pointer', opacity: processing ? 0.7 : 1, display: 'flex', alignItems: 'center', gap: '8px', boxShadow: '0 4px 6px rgba(59,130,246,0.3)' }}
                        >
                            {processing ? (
                                <>
                                    <svg className="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                    Guardando...
                                </>
                            ) : (
                                <>
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    Guardar Proveedor
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
