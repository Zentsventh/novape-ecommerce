import React, { useState, useMemo } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function ComprasIndex({ compras, totalGastado, comprasPendientes, proveedores, productos, categorias, marcas, historialProducto, filters, logoUrl }) {
    const confirmDialog = useConfirm();

    const [showModal, setShowModal] = useState(false);
    const [items, setItems] = useState([{ producto_id: '', variante_id: '', cantidad: 1, costo_unitario: '' }]);
    const { data, setData, post, processing, reset } = useForm({
        proveedor_id: '',
        notas: '',
        items: [],
    });

    const [searchFilters, setSearchFilters] = useState({
        proveedor_id: filters?.proveedor_id || '',
        estado: filters?.estado || '',
        categoria_id: filters?.categoria_id || '',
        marca_id: filters?.marca_id || '',
        producto_id: filters?.producto_id || '',
        search: filters?.search || ''
    });

    const productosFiltrados = useMemo(() => {
        let filtrados = productos;
        if (searchFilters.categoria_id) {
            filtrados = filtrados?.filter(p => p.parent_category_id == searchFilters.categoria_id);
        }
        if (searchFilters.marca_id) {
            filtrados = filtrados?.filter(p => p.marca_id == searchFilters.marca_id);
        }
        return filtrados;
    }, [productos, searchFilters.categoria_id, searchFilters.marca_id]);

    const applyFilters = () => {
        router.get('/admin/compras', searchFilters, { preserveState: true });
    };

    const resetFilters = () => {
        setSearchFilters({ proveedor_id: '', estado: '', categoria_id: '', marca_id: '', producto_id: '', search: '' });
        router.get('/admin/compras');
    };

    const addItemRow = () => {
        setItems([...items, { producto_id: '', variante_id: '', cantidad: 1, costo_unitario: '' }]);
    };

    const updateItem = (idx, field, value) => {
        const updated = [...items];
        updated[idx][field] = value;
        if (field === 'producto_id') {
            const prod = productos.find(p => p.producto_id == value);
            if (prod) {
                updated[idx].variante_id = prod.variante_id;
                updated[idx].costo_unitario = (parseFloat(prod.precio) * 0.6).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }
        setItems(updated);
    };

    const removeItem = (idx) => {
        if (items.length > 1) setItems(items.filter((_, i) => i !== idx));
    };

    const totalOrden = items.reduce((s, i) => s + (parseFloat(i.costo_unitario || 0) * parseInt(i.cantidad || 0)), 0);

    const submit = async (e) => {
        e.preventDefault();
        router.post('/admin/compras', {
            proveedor_id: data.proveedor_id,
            notas: data.notas,
            items: items.map(i => ({
                producto_id: i.producto_id,
                variante_id: i.variante_id,
                cantidad: parseInt(i.cantidad),
                costo_unitario: parseFloat(i.costo_unitario),
            })),
        }, {
            onSuccess: () => {
                setShowModal(false);
                setItems([{ producto_id: '', variante_id: '', cantidad: 1, costo_unitario: '' }]);
                reset();
            },
        });
    };

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Eliminar esta orden de compra y todos sus items?')) {
            router.delete(`/admin/compras/${id}`);
        }
    };

    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title="Historial de Compras" />

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <div>
                    <h1 style={{ fontSize: '28px', margin: 0, fontWeight: '700', color: 'var(--admin-text-main)' }}>Historial de Compras</h1>
                </div>
                <button onClick={() => setShowModal(true)} style={{ background: 'var(--admin-primary)', color: 'white', padding: '10px 20px', borderRadius: '8px', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>+ Nueva Orden</button>
            </div>

            {/* Filtros */}
            <div style={{ background: 'var(--admin-card-bg)', padding: '15px 20px', borderRadius: '12px', boxShadow: 'var(--admin-shadow-sm)', marginBottom: '20px', display: 'flex', gap: '15px', alignItems: 'flex-end', flexWrap: 'wrap' }}>
                <div style={{ flex: 1, minWidth: '150px' }}>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', marginBottom: '5px' }}>Categoría</label>
                    <select value={searchFilters.categoria_id} onChange={e => setSearchFilters({...searchFilters, categoria_id: e.target.value, producto_id: ''})} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid var(--admin-border)', outline: 'none' }}>
                        <option value="">Todas</option>
                        {categorias?.map(c => <option key={c.id} value={c.id}>{c.nombre}</option>)}
                    </select>
                </div>
                <div style={{ flex: 1, minWidth: '150px' }}>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', marginBottom: '5px' }}>Marca</label>
                    <select value={searchFilters.marca_id} onChange={e => setSearchFilters({...searchFilters, marca_id: e.target.value, producto_id: ''})} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid var(--admin-border)', outline: 'none' }}>
                        <option value="">Todas</option>
                        {marcas?.map(m => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                    </select>
                </div>
                <div style={{ flex: 2, minWidth: '200px' }}>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', marginBottom: '5px' }}>Producto Comprado</label>
                    <select value={searchFilters.producto_id} onChange={e => setSearchFilters({...searchFilters, producto_id: e.target.value})} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid var(--admin-border)', outline: 'none' }}>
                        <option value="">Todos los productos</option>
                        {productosFiltrados?.map(p => <option key={p.producto_id} value={p.producto_id}>{p.nombre}</option>)}
                    </select>
                </div>
                <div style={{ flex: 1, minWidth: '150px' }}>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', marginBottom: '5px' }}>Proveedor</label>
                    <select value={searchFilters.proveedor_id} onChange={e => setSearchFilters({...searchFilters, proveedor_id: e.target.value})} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid var(--admin-border)', outline: 'none' }}>
                        <option value="">Todos</option>
                        {proveedores?.map(p => <option key={p.id} value={p.id}>{p.nombre}</option>)}
                    </select>
                </div>
                <div style={{ flex: 1, minWidth: '200px' }}>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', marginBottom: '5px' }}>Buscar SKU / Nombre / Orden</label>
                    <input type="text" placeholder="" value={searchFilters.search} onChange={e => setSearchFilters({...searchFilters, search: e.target.value})} onKeyDown={e => e.key === 'Enter' && applyFilters()} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid var(--admin-border)', outline: 'none' }} />
                </div>
                <div style={{ width: '120px' }}>
                    <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', marginBottom: '5px' }}>Estado</label>
                    <select value={searchFilters.estado} onChange={e => setSearchFilters({...searchFilters, estado: e.target.value})} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid var(--admin-border)', outline: 'none' }}>
                        <option value="">Todos</option>
                        <option value="completado">Completados</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="cancelado">Cancelados</option>
                    </select>
                </div>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <button onClick={resetFilters} style={{ padding: '10px 15px', background: '#F1F5F9', color: '#475569', border: '1px solid #CBD5E1', borderRadius: '6px', cursor: 'pointer', fontWeight: 'bold' }}>Limpiar</button>
                    <button onClick={applyFilters} style={{ padding: '10px 20px', background: 'var(--admin-primary)', color: 'white', border: 'none', borderRadius: '6px', cursor: 'pointer', fontWeight: 'bold' }}>Filtrar</button>
                </div>
            </div>

            {/* KPIs */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '20px', marginBottom: '30px' }}>
                <div style={{ background: 'var(--admin-card-bg)', padding: '20px', borderRadius: '12px', boxShadow: 'var(--admin-shadow-sm)' }}>
                    <div style={{ fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', textTransform: 'uppercase' }}>Inversión Total</div>
                    <div style={{ fontSize: '28px', fontWeight: 'bold', color: '#2563eb' }}>S/ {Number(totalGastado).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </div>
                <div style={{ background: 'var(--admin-card-bg)', padding: '20px', borderRadius: '12px', boxShadow: 'var(--admin-shadow-sm)' }}>
                    <div style={{ fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', textTransform: 'uppercase' }}>Órdenes Pendientes</div>
                    <div style={{ fontSize: '28px', fontWeight: 'bold', color: '#1e40af' }}>{comprasPendientes}</div>
                </div>
                <div style={{ background: 'var(--admin-card-bg)', padding: '20px', borderRadius: '12px', boxShadow: 'var(--admin-shadow-sm)' }}>
                    <div style={{ fontSize: '12px', fontWeight: 'bold', color: 'var(--admin-text-muted)', textTransform: 'uppercase' }}>Total Órdenes</div>
                    <div style={{ fontSize: '28px', fontWeight: 'bold', color: 'var(--admin-primary)' }}>{compras.length}</div>
                </div>
            </div>

            {historialProducto && (
                /* TABLA DE ANÁLISIS DE PRECIOS POR PRODUCTO (KARDEX DE COMPRAS) */
                <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', overflowX: 'auto', boxShadow: 'var(--admin-shadow-sm)', border: '1px solid #3b82f6', marginBottom: '20px' }}>
                    <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                        <thead style={{ background: 'var(--admin-bg)', borderBottom: '1px solid var(--admin-border)' }}>
                            <tr>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>FECHA</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ORDEN</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>PROVEEDOR</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>CANTIDAD</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>PRECIO UNIT. (COSTO)</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>SUBTOTAL</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            {historialProducto.length > 0 ? historialProducto.map((hp, index) => (
                                <tr key={index} style={{ borderBottom: '1px solid var(--admin-border-light)', background: index === 0 ? '#F8FAFC' : 'white' }}>
                                    <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)' }}>{hp.fecha_compra} {index === 0 && <span style={{fontSize:'10px', background:'#22C55E', color:'white', padding:'2px 6px', borderRadius:'10px', marginLeft:'5px'}}>ÚLTIMA</span>}</td>
                                    <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{hp.numero_orden}</td>
                                    <td style={{ padding: '15px 20px', color: '#2563EB', fontWeight: '500' }}>{hp.proveedor_nombre || 'Sin proveedor'}</td>
                                    <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>{hp.cantidad} unds.</td>
                                    <td style={{ padding: '15px 20px', fontWeight: 'bold', color: '#10B981' }}>S/ {Number(hp.costo_unitario).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>S/ {Number(hp.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    <td style={{ padding: '15px 20px' }}>
                                        <span style={{ background: hp.estado === 'completado' ? 'rgba(37,99,235,0.1)' : 'rgba(245,158,11,0.1)', color: hp.estado === 'completado' ? '#2563eb' : '#1e40af', padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold' }}>
                                            {hp.estado}
                                        </span>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="7" style={{ padding: '40px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>No hay compras registradas para este producto.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {/* TABLA GENERAL DE ORDENES DE COMPRA */}
            <div style={{ background: 'var(--admin-card-bg)', borderRadius: '12px', overflowX: 'auto', boxShadow: 'var(--admin-shadow-sm)' }}>
                {historialProducto && <h3 style={{ margin: '15px 20px 10px 20px', color: 'var(--admin-text-main)', fontSize: '16px' }}>Órdenes de Compra Relacionadas</h3>}
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                        <thead style={{ background: 'var(--admin-bg)', borderBottom: '1px solid var(--admin-border)' }}>
                            <tr>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ID ORDEN</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>PROVEEDOR</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>FECHA</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>TOTAL ORDEN</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ESTADO</th>
                                <th style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)', fontWeight: '600', fontSize: '13px' }}>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            {compras.length > 0 ? compras.map(c => (
                                <tr key={c.id} style={{ borderBottom: '1px solid var(--admin-border-light)' }}>
                                    <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>#{c.numero_orden || `OC-${String(c.id).padStart(4, '0')}`}</td>
                                    <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)' }}>{c.proveedor_nombre || 'Sin proveedor'}</td>
                                    <td style={{ padding: '15px 20px', color: 'var(--admin-text-secondary)' }}>{c.fecha_compra}</td>
                                    <td style={{ padding: '15px 20px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>S/ {Number(c.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    <td style={{ padding: '15px 20px' }}>
                                        <span style={{
                                            background: c.estado === 'completado' ? 'rgba(37,99,235,0.1)' : c.estado === 'pendiente' ? 'rgba(245,158,11,0.1)' : 'rgba(59,130,246,0.1)',
                                            color: c.estado === 'completado' ? '#2563eb' : c.estado === 'pendiente' ? '#1e40af' : '#3b82f6',
                                            padding: '4px 10px', borderRadius: '20px', fontSize: '12px', fontWeight: 'bold',
                                        }}>{c.estado === 'completado' ? 'Completado' : c.estado === 'pendiente' ? 'Pendiente' : 'Cancelado'}</span>
                                    </td>
                                    <td style={{ padding: '15px 20px', display: 'flex', gap: '10px' }}>
                                        <Link href={`/admin/compras/${c.id}`} style={{ color: 'var(--admin-primary)', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 'bold', textDecoration: 'none' }}>Ver Detalles</Link>
                                        <button onClick={() => handleDelete(c.id)} style={{ color: '#3b82f6', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 'bold' }}>Eliminar</button>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="6" style={{ padding: '40px', textAlign: 'center', color: 'var(--admin-text-muted)' }}>No hay compras registradas.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

            {/* Modal Nueva Orden de Compra */}
            {showModal && (
                <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }}>
                    <div style={{ background: 'white', padding: '30px', borderRadius: '12px', width: '700px', maxHeight: '90vh', overflowY: 'auto' }}>
                        <h2 style={{ margin: '0 0 20px 0' }}>Nueva Orden de Compra</h2>
                        <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Proveedor</label>
                                <select value={data.proveedor_id} onChange={e => setData('proveedor_id', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc' }} required>
                                    <option value="">Seleccionar proveedor...</option>
                                    {proveedores?.map(p => (
                                        <option key={p.id} value={p.id}>{p.nombre}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Productos</label>
                                {items.map((item, idx) => (
                                    <div key={idx} style={{ display: 'grid', gridTemplateColumns: '2fr 80px 100px 30px', gap: '8px', marginBottom: '8px', alignItems: 'center' }}>
                                        <select value={item.producto_id} onChange={e => updateItem(idx, 'producto_id', e.target.value)} style={{ padding: '8px', borderRadius: '6px', border: '1px solid #ccc', fontSize: '13px' }} required>
                                            <option value="">Seleccionar...</option>
                                            {productos?.map((p, i) => (
                                                <option key={i} value={p.producto_id}>{p.nombre} ({p.sku})</option>
                                            ))}
                                        </select>
                                        <input type="number" min="1" value={item.cantidad} onChange={e => updateItem(idx, 'cantidad', e.target.value)} placeholder="Cant." style={{ padding: '8px', borderRadius: '6px', border: '1px solid #ccc', fontSize: '13px' }} required />
                                        <input type="number" step="0.01" value={item.costo_unitario} onChange={e => updateItem(idx, 'costo_unitario', e.target.value)} placeholder="Costo S/" style={{ padding: '8px', borderRadius: '6px', border: '1px solid #ccc', fontSize: '13px' }} required />
                                        <button type="button" onClick={() => removeItem(idx)} style={{ color: '#3b82f6', background: 'none', border: 'none', cursor: 'pointer', fontSize: '16px' }}>✕</button>
                                    </div>
                                ))}
                                <button type="button" onClick={addItemRow} style={{ background: 'rgba(2,141,252,0.1)', color: 'var(--admin-primary)', border: 'none', padding: '8px 16px', borderRadius: '6px', cursor: 'pointer', fontWeight: 'bold', fontSize: '13px' }}>+ Agregar producto</button>
                            </div>

                            <div style={{ background: '#f0f4f8', padding: '12px 16px', borderRadius: '8px', display: 'flex', justifyContent: 'space-between', fontWeight: 'bold' }}>
                                <span>Total estimado:</span>
                                <span style={{ color: '#2563eb', fontSize: '18px' }}>S/ {totalOrden.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>

                            <div>
                                <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Notas (opcional)</label>
                                <textarea value={data.notas} onChange={e => setData('notas', e.target.value)} style={{ width: '100%', padding: '10px', borderRadius: '6px', border: '1px solid #ccc', minHeight: '60px' }} placeholder="Observaciones sobre la orden..." />
                            </div>

                            <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                                <button type="button" onClick={() => setShowModal(false)} style={{ flex: 1, padding: '12px', borderRadius: '6px', border: '1px solid #ccc', background: 'transparent', cursor: 'pointer' }}>Cancelar</button>
                                <button type="submit" disabled={processing} style={{ flex: 1, padding: '12px', borderRadius: '6px', border: 'none', background: 'var(--admin-primary)', color: 'white', fontWeight: 'bold', cursor: 'pointer' }}>Crear Orden</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
