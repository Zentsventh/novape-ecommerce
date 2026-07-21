import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';

export default function Form({ cupon }) {
    const isEdit = !!cupon;
    
    const { data, setData, post, put, processing, errors } = useForm({
        codigo: cupon?.codigo || '',
        tipo: cupon?.tipo || 'porcentaje',
        valor: cupon?.valor || '',
        fecha_inicio: cupon?.fecha_inicio ? cupon.fecha_inicio.split('T')[0] : '',
        fecha_fin: cupon?.fecha_fin ? cupon.fecha_fin.split('T')[0] : '',
        activo: cupon ? cupon.activo : true,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(`/admin/cupones/${cupon.id}`);
        } else {
            post('/admin/cupones');
        }
    };

    return (
        <AdminLayout logoUrl={null}>
            <Head title={isEdit ? "Editar Cupón" : "Nuevo Cupón"} />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>
                    {isEdit ? 'Editar Cupón' : 'Crear Nuevo Cupón'}
                </h1>
                <Link href="/admin/cupones" style={{ color: 'var(--admin-text-muted)', textDecoration: 'none', fontWeight: '500' }}>
                    &larr; Volver a Cupones
                </Link>
            </div>

            <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '30px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)', maxWidth: '600px' }}>
                <form onSubmit={submit} style={{ display: 'grid', gap: '20px' }}>
                    
                    <div>
                        <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Código del Cupón *</label>
                        <input
                            type="text"
                            value={data.codigo}
                            onChange={e => setData('codigo', e.target.value.toUpperCase())}
                            placeholder="EJ: VERANO2026"
                            style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)', textTransform: 'uppercase' }}
                            required
                        />
                        {errors.codigo && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.codigo}</div>}
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Tipo de Descuento *</label>
                            <select
                                value={data.tipo}
                                onChange={e => setData('tipo', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'var(--admin-bg-panel)', color: 'var(--admin-text-main)' }}
                                required
                            >
                                <option value="porcentaje">Porcentaje (%)</option>
                                <option value="monto">Monto Fijo (S/)</option>
                            </select>
                            {errors.tipo && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.tipo}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Valor *</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.valor}
                                onChange={e => setData('valor', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                required
                            />
                            {errors.valor && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.valor}</div>}
                        </div>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Fecha de Inicio</label>
                            <input
                                type="date"
                                value={data.fecha_inicio}
                                onChange={e => setData('fecha_inicio', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                            />
                            {errors.fecha_inicio && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.fecha_inicio}</div>}
                        </div>
                        <div>
                            <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Fecha de Fin</label>
                            <input
                                type="date"
                                value={data.fecha_fin}
                                onChange={e => setData('fecha_fin', e.target.value)}
                                style={{ width: '100%', padding: '10px 14px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                            />
                            {errors.fecha_fin && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.fecha_fin}</div>}
                        </div>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                        <input
                            type="checkbox"
                            id="activo"
                            checked={data.activo}
                            onChange={e => setData('activo', e.target.checked)}
                            style={{ width: '18px', height: '18px' }}
                        />
                        <label htmlFor="activo" style={{ fontWeight: 'bold', color: 'var(--admin-text-main)', cursor: 'pointer' }}>Cupón Activo</label>
                        {errors.activo && <div style={{ color: '#3b82f6', fontSize: '12px', marginTop: '4px' }}>{errors.activo}</div>}
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '12px', marginTop: '10px', paddingTop: '20px', borderTop: '1px solid var(--admin-border)' }}>
                        <Link 
                            href="/admin/cupones" 
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
                            {processing ? 'Guardando...' : (isEdit ? 'Actualizar Cupón' : 'Guardar Cupón')}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
