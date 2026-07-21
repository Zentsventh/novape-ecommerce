import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';

export default function AlmacenesIndex({ almacenes, productos, logoUrl }) {
    const [showModal, setShowModal] = useState(false);
    const [showTransferModal, setShowTransferModal] = useState(false);

    const { data: dataA, setData: setDataA, post: postA, processing: procA, reset: resetA } = useForm({
        nombre: '', direccion: '', activo: true
    });

    const { data: dataT, setData: setDataT, post: postT, processing: procT, reset: resetT } = useForm({
        almacen_origen_id: '', almacen_destino_id: '', variante_id: '', cantidad: '', referencia: ''
    });

    const submitAlmacen = (e) => {
        e.preventDefault();
        postA('/admin/almacenes', { onSuccess: () => { setShowModal(false); resetA(); } });
    };

    const submitTransfer = (e) => {
        e.preventDefault();
        postT('/admin/almacenes/transferir', { onSuccess: () => { setShowTransferModal(false); resetT(); } });
    };

    const handleDelete = (id) => {
        if (confirm('¿Eliminar este almacén? Esto podría afectar a los productos vinculados.')) {
            router.delete(`/admin/almacenes/${id}`);
        }
    };

    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title="Gestión de Almacenes" />

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <div>
                    <h1 style={{ fontSize: '28px', margin: 0, fontWeight: '700', color: 'var(--admin-text-main)' }}>Ubicaciones y Almacenes</h1>
                    <p style={{ color: 'var(--admin-text-secondary)', margin: '5px 0 0 0' }}>Gestiona inventario distribuido y transferencias (Kardex).</p>
                </div>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <button onClick={() => setShowTransferModal(true)} style={{ background: 'var(--admin-bg-panel)', color: 'var(--admin-text-main)', padding: '10px 20px', borderRadius: '8px', border: '1px solid var(--admin-border)', cursor: 'pointer', fontWeight: 'bold' }}>
                        ⇄ Transferir Stock
                    </button>
                    <button onClick={() => setShowModal(true)} style={{ background: 'var(--admin-primary)', color: 'white', padding: '10px 20px', borderRadius: '8px', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>
                        + Nuevo Almacén
                    </button>
                </div>
            </div>

            <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', overflow: 'hidden', boxShadow: 'var(--admin-shadow-sm)' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead style={{ background: 'var(--admin-bg)', borderBottom: '1px solid var(--admin-border)' }}>
                        <tr>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ALMACÉN</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>DIRECCIÓN</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>TOTAL UNIDADES</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>TOTAL SKUs</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ESTADO</th>
                            <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        {almacenes.map(a => (
                            <tr key={a.id} style={{ borderBottom: '1px solid var(--admin-border-light)' }}>
                                <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{a.nombre}</td>
                                <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)' }}>{a.direccion || '—'}</td>
                                <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-primary)' }}>{a.total_unidades} u.</td>
                                <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)' }}>{a.total_skus} prod.</td>
                                <td style={{ padding: '15px 20px' }}>
                                    <span style={{
                                        background: a.activo ? 'rgba(37,99,235,0.1)' : 'rgba(59,130,246,0.1)',
                                        color: a.activo ? '#2563eb' : '#3b82f6',
                                        padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold'
                                    }}>{a.activo ? 'Activo' : 'Inactivo'}</span>
                                </td>
                                <td style={{ padding: '15px 20px', display: 'flex', gap: '15px' }}>
                                    <Link href={`/admin/almacenes/${a.id}/kardex`} style={{ color: 'var(--admin-primary)', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 'bold', textDecoration: 'none' }}>
                                        Kardex (Mov.)
                                    </Link>
                                    <button onClick={() => handleDelete(a.id)} style={{ color: '#3b82f6', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>Eliminar</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Modal Nuevo Almacen */}
            {showModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
                    <div style={{ background: 'white', padding: '30px', borderRadius: '12px', width: '400px' }}>
                        <h2 style={{ margin: '0 0 20px 0' }}>Nuevo Almacén</h2>
                        <form onSubmit={submitAlmacen} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Nombre</label>
                                <input type="text" value={dataA.nombre} onChange={e => setDataA('nombre', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required />
                            </div>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Dirección</label>
                                <input type="text" value={dataA.direccion} onChange={e => setDataA('direccion', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} />
                            </div>
                            <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                                <button type="button" onClick={() => setShowModal(false)} style={{ flex: 1, padding: '10px', borderRadius: '6px', border: '1px solid #ccc', background: 'transparent', cursor: 'pointer' }}>Cancelar</button>
                                <button type="submit" disabled={procA} style={{ flex: 1, padding: '10px', borderRadius: '6px', border: 'none', background: 'var(--admin-primary)', color: 'white', fontWeight: 'bold', cursor: 'pointer' }}>Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Transferencia */}
            {showTransferModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
                    <div style={{ background: 'white', padding: '30px', borderRadius: '12px', width: '500px' }}>
                        <h2 style={{ margin: '0 0 20px 0' }}>Transferencia de Stock</h2>
                        <form onSubmit={submitTransfer} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Almacén Origen</label>
                                    <select value={dataT.almacen_origen_id} onChange={e => setDataT('almacen_origen_id', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required>
                                        <option value="">Seleccionar...</option>
                                        {almacenes.map(a => <option key={a.id} value={a.id}>{a.nombre}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Almacén Destino</label>
                                    <select value={dataT.almacen_destino_id} onChange={e => setDataT('almacen_destino_id', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required>
                                        <option value="">Seleccionar...</option>
                                        {almacenes.map(a => <option key={a.id} value={a.id}>{a.nombre}</option>)}
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Producto (Variante)</label>
                                <select value={dataT.variante_id} onChange={e => setDataT('variante_id', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required>
                                    <option value="">Seleccionar producto...</option>
                                    {productos.map(p => (
                                        <option key={p.variante_id} value={p.variante_id}>{p.nombre} ({p.sku})</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Cantidad a Transferir</label>
                                <input type="number" min="1" value={dataT.cantidad} onChange={e => setDataT('cantidad', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required />
                            </div>

                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Motivo / Referencia</label>
                                <input type="text" value={dataT.referencia} onChange={e => setDataT('referencia', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} placeholder="Ej: Reposición de tienda" />
                            </div>

                            <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                                <button type="button" onClick={() => setShowTransferModal(false)} style={{ flex: 1, padding: '10px', borderRadius: '6px', border: '1px solid #ccc', background: 'transparent', cursor: 'pointer' }}>Cancelar</button>
                                <button type="submit" disabled={procT} style={{ flex: 1, padding: '10px', borderRadius: '6px', border: 'none', background: 'var(--admin-primary)', color: 'white', fontWeight: 'bold', cursor: 'pointer' }}>Realizar Transferencia</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
