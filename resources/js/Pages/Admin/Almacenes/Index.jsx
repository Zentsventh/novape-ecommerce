import React, { useState, useMemo } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function AlmacenesIndex({ almacenes, productos, categorias, marcas, stocks, logoUrl }) {
    const confirmDialog = useConfirm();

    const [showModal, setShowModal] = useState(false);
    const [showTransferModal, setShowTransferModal] = useState(false);

    // Filtros
    const [filterCategoria, setFilterCategoria] = useState('');
    const [filterMarca, setFilterMarca] = useState('');
    const [searchQuery, setSearchQuery] = useState('');

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

    const submitTransfer = async (e) => {
        e.preventDefault();
        postT('/admin/almacenes/transferir', { onSuccess: () => { setShowTransferModal(false); resetT(); } });
    };

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Eliminar este almacén? Esto podría afectar a los productos vinculados.')) {
            router.delete(`/admin/almacenes/${id}`);
        }
    };

    // Filter products
    const filteredProductos = useMemo(() => {
        return productos.filter(p => {
            if (filterCategoria && p.category_id != filterCategoria) return false;
            if (filterMarca && p.marca_id != filterMarca) return false;
            if (searchQuery) {
                const search = searchQuery.toLowerCase();
                const nombreMatch = p.nombre && p.nombre.toLowerCase().includes(search);
                const skuMatch = p.sku && p.sku.toLowerCase().includes(search);
                if (!nombreMatch && !skuMatch) return false;
            }
            return true;
        });
    }, [productos, filterCategoria, filterMarca, searchQuery]);

    // Calculate max available stock for selected product and origin warehouse
    const maxAvailable = useMemo(() => {
        if (!dataT.almacen_origen_id || !dataT.variante_id) return null;
        const stockInfo = stocks.find(s => 
            s.almacen_id == dataT.almacen_origen_id && 
            s.variante_id == dataT.variante_id
        );
        return stockInfo ? stockInfo.cantidad : 0;
    }, [stocks, dataT.almacen_origen_id, dataT.variante_id]);

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
                                <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontSize: '14px' }}>{a.direccion || 'No especificada'}</td>
                                <td style={{ padding: '15px 20px', color: '#2563eb', fontWeight: 'bold' }}>{a.total_unidades} u.</td>
                                <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)' }}>{a.total_skus} prod.</td>
                                <td style={{ padding: '15px 20px' }}>
                                    {a.activo ? <span style={{ background: '#dbeafe', color: '#2563eb', padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold' }}>Activo</span> : <span style={{ background: '#fee2e2', color: '#ef4444', padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold' }}>Inactivo</span>}
                                </td>
                                <td style={{ padding: '15px 20px', display: 'flex', gap: '10px' }}>
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
                    <div style={{ background: 'white', padding: '30px', borderRadius: '12px', width: '600px', maxHeight: '90vh', overflowY: 'auto' }}>
                        <h2 style={{ margin: '0 0 20px 0', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>Transferencia de Stock</h2>
                        <form onSubmit={submitTransfer} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', padding: '5px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold', color: '#334155' }}>Almacén Origen</label>
                                    <select value={dataT.almacen_origen_id} onChange={e => {
                                        setDataT(prev => ({...prev, almacen_origen_id: e.target.value, almacen_destino_id: prev.almacen_destino_id === e.target.value ? '' : prev.almacen_destino_id}));
                                    }} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #cbd5e1' }} required>
                                        <option value="">Seleccionar...</option>
                                        {almacenes.map(a => <option key={a.id} value={a.id}>{a.nombre}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold', color: '#334155' }}>Almacén Destino</label>
                                    <select value={dataT.almacen_destino_id} onChange={e => setDataT('almacen_destino_id', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #cbd5e1' }} required>
                                        <option value="">Seleccionar...</option>
                                        {almacenes.filter(a => String(a.id) !== String(dataT.almacen_origen_id)).map(a => <option key={a.id} value={a.id}>{a.nombre}</option>)}
                                    </select>
                                </div>
                            </div>
                            
                            <div style={{ border: '1px solid #e2e8f0', padding: '15px', borderRadius: '8px' }}>
                                <h4 style={{ margin: '0 0 10px 0', color: '#0f172a' }}>Buscar Producto a Transferir</h4>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px', marginBottom: '10px' }}>
                                    <select value={filterCategoria} onChange={e => {setFilterCategoria(e.target.value); setDataT('variante_id', '');}} style={{ width: '100%', padding: '8px', borderRadius: '6px', border: '1px solid #cbd5e1', fontSize: '13px' }}>
                                        <option value="">Todas las Categorías</option>
                                        {categorias && categorias.map(c => <option key={c.id} value={c.id}>{c.nombre}</option>)}
                                    </select>
                                    <select value={filterMarca} onChange={e => {setFilterMarca(e.target.value); setDataT('variante_id', '');}} style={{ width: '100%', padding: '8px', borderRadius: '6px', border: '1px solid #cbd5e1', fontSize: '13px' }}>
                                        <option value="">Todas las Marcas</option>
                                        {marcas && marcas.map(m => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                                    </select>
                                </div>
                                <input type="text" placeholder="Buscar por nombre o SKU..." value={searchQuery} onChange={e => {setSearchQuery(e.target.value); setDataT('variante_id', '');}} style={{ width: '100%', padding: '8px', borderRadius: '6px', border: '1px solid #cbd5e1', marginBottom: '10px', fontSize: '13px' }} />
                                
                                <select value={dataT.variante_id} onChange={e => setDataT('variante_id', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #00B4FF', fontWeight: 'bold' }} required>
                                    <option value="">-- Seleccione el producto ({filteredProductos.length} encontrados) --</option>
                                    {filteredProductos.map(p => (
                                        <option key={p.variante_id} value={p.variante_id}>{p.sku} | {p.nombre}</option>
                                    ))}
                                </select>
                            </div>

                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px' }}>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Stock Disponible (Origen)</label>
                                    <div style={{ padding: '10px', borderRadius: '6px', background: maxAvailable > 0 ? 'rgba(0, 180, 255, 0.1)' : '#f8fafc', fontWeight: 'bold', color: maxAvailable > 0 ? '#00B4FF' : '#94a3b8', border: `1px solid ${maxAvailable > 0 ? '#00B4FF' : '#cbd5e1'}`, textAlign: 'center', fontSize: '18px' }}>
                                        {maxAvailable !== null ? `${maxAvailable} unidades` : 'Seleccione origen y producto'}
                                    </div>
                                </div>
                                <div>
                                    <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Cantidad a Transferir</label>
                                    <input 
                                        type="number" 
                                        min="1" 
                                        max={maxAvailable || 1} 
                                        value={dataT.cantidad} 
                                        onChange={e => {
                                            let val = parseInt(e.target.value) || '';
                                            if (val !== '' && maxAvailable !== null && val > maxAvailable) {
                                                val = maxAvailable;
                                            }
                                            setDataT('cantidad', val);
                                        }} 
                                        style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc', fontSize: '16px' }} 
                                        required 
                                    />
                                </div>
                            </div>

                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Motivo / Referencia</label>
                                <input type="text" value={dataT.referencia} onChange={e => setDataT('referencia', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} placeholder="Ej: Reposición de tienda Miraflores" />
                            </div>

                            <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                                <button type="button" onClick={() => setShowTransferModal(false)} style={{ flex: 1, padding: '12px', borderRadius: '6px', border: '1px solid #ccc', background: '#f8fafc', cursor: 'pointer', fontWeight: 'bold' }}>Cancelar</button>
                                <button type="submit" disabled={procT || maxAvailable === 0 || maxAvailable === null || dataT.cantidad > maxAvailable || dataT.cantidad < 1} style={{ flex: 1, padding: '12px', borderRadius: '6px', border: 'none', background: '#00B4FF', color: 'white', fontWeight: 'bold', cursor: (procT || maxAvailable === 0 || maxAvailable === null || dataT.cantidad > maxAvailable || dataT.cantidad < 1) ? 'not-allowed' : 'pointer', opacity: (procT || maxAvailable === 0 || maxAvailable === null || dataT.cantidad > maxAvailable || dataT.cantidad < 1) ? 0.6 : 1 }}>
                                    Realizar Transferencia
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
