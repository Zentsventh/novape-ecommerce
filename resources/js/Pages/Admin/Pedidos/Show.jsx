import React from 'react';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function Show({ pedido }) {
    const confirmDialog = useConfirm();

    const { flash } = usePage().props;
    const { data, setData, put, processing } = useForm({
        estado: pedido.estado,
        tracking: pedido.envio?.tracking || '',
        estado_envio: pedido.envio?.estado || ''
    });

    const updateStatus = (e) => {
        e.preventDefault();
        put(`/admin/pedidos/${pedido.id}/estado`);
    };

    const getStatusColor = async (estado) => {
        switch (estado) {
            case 'pendiente': return '#60a5fa';
            case 'procesando': return '#3b82f6';
            case 'enviado': return '#1d4ed8';
            case 'completado': return '#2563eb';
            case 'cancelado': return '#3b82f6';
            default: return '#6b7280';
        }
    };

    return (
        <AdminLayout logoUrl={null}>
            <Head title={`Pedido ${pedido.codigo}`} />
            
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <div>
                    <h1 style={{ fontSize: '24px', fontWeight: 'bold', color: 'var(--admin-text-main)' }}>Pedido #{pedido.codigo}</h1>
                    <p style={{ color: 'var(--admin-text-muted)' }}>{new Date(pedido.created_at).toLocaleString()}</p>
                </div>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <Link href="/admin/pedidos" style={{ color: 'var(--admin-text-muted)', textDecoration: 'none', padding: '10px 16px', border: '1px solid var(--admin-border)', borderRadius: '8px', fontWeight: 'bold' }}>
                        Volver
                    </Link>
                    <a href={`/admin/pedidos/${pedido.id}/factura`} target="_blank" rel="noreferrer" style={{ background: '#2563eb', color: 'white', textDecoration: 'none', padding: '10px 16px', borderRadius: '8px', fontWeight: 'bold' }}>
                        Ver Factura
                    </a>
                </div>
            </div>

            {flash?.success && (
                <div style={{ background: 'rgba(37,99,235,0.1)', color: '#2563eb', padding: '12px 16px', borderRadius: '8px', marginBottom: '20px', fontWeight: '500', border: '1px solid rgba(37,99,235,0.2)' }}>
                    {flash.success}
                </div>
            )}

            <div className="admin-grid-charts">
                {/* Lado izquierdo: Artículos */}
                <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                    <h2 style={{ fontSize: '18px', fontWeight: 'bold', color: 'var(--admin-text-main)', borderBottom: '1px solid var(--admin-border)', paddingBottom: '10px', marginBottom: '15px' }}>Artículos del Pedido</h2>
                    
                    <div className="admin-table-container">
                        <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', minWidth: '600px' }}>
                            <thead>
                                <tr style={{ color: 'var(--admin-text-muted)', borderBottom: '1px solid var(--admin-border)' }}>
                                    <th style={{ padding: '10px' }}>Producto</th>
                                    <th style={{ padding: '10px' }}>Precio</th>
                                    <th style={{ padding: '10px' }}>Cantidad</th>
                                    <th style={{ padding: '10px', textAlign: 'right' }}>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {pedido.items?.map(item => (
                                    <tr key={item.id} style={{ borderBottom: '1px solid var(--admin-border)' }}>
                                        <td style={{ padding: '10px', color: 'var(--admin-text-main)' }}>
                                            {item.variante?.producto?.nombre || 'Producto Desconocido'}
                                            {item.variante?.sku && <div style={{ fontSize: '12px', color: 'var(--admin-text-muted)' }}>SKU: {item.variante.sku}</div>}
                                        </td>
                                        <td style={{ padding: '10px', color: 'var(--admin-text-muted)' }}>S/ {item.precio_unitario}</td>
                                        <td style={{ padding: '10px', color: 'var(--admin-text-main)', fontWeight: 'bold' }}>{item.cantidad}</td>
                                        <td style={{ padding: '10px', textAlign: 'right', color: 'var(--admin-text-main)', fontWeight: 'bold' }}>S/ {item.subtotal}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div style={{ marginTop: '20px', display: 'flex', flexDirection: 'column', gap: '10px', alignItems: 'flex-end', fontSize: '16px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', width: '200px', color: 'var(--admin-text-muted)' }}>
                            <span>Subtotal:</span>
                            <span>S/ {pedido.subtotal}</span>
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', width: '200px', color: 'var(--admin-text-muted)' }}>
                            <span>Envío:</span>
                            <span>S/ {pedido.costo_envio}</span>
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', width: '200px', fontWeight: 'bold', color: 'var(--admin-text-main)', fontSize: '18px', borderTop: '1px solid var(--admin-border)', paddingTop: '10px' }}>
                            <span>Total:</span>
                            <span>S/ {pedido.total}</span>
                        </div>
                    </div>
                </div>

                {/* Lado derecho: Info y Estado */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
                    <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                        <h2 style={{ fontSize: '18px', fontWeight: 'bold', color: 'var(--admin-text-main)', borderBottom: '1px solid var(--admin-border)', paddingBottom: '10px', marginBottom: '15px' }}>Actualizar Estado</h2>
                        
                        <form onSubmit={updateStatus}>
                            <div style={{ marginBottom: '15px' }}>
                                <label style={{ display: 'block', marginBottom: '5px', color: 'var(--admin-text-muted)' }}>Estado del Pedido</label>
                                <select 
                                    value={data.estado} 
                                    onChange={e => setData('estado', e.target.value)}
                                    style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                >
                                    <option value="pendiente">Pendiente</option>
                                    <option value="procesando">Procesando</option>
                                    <option value="enviado">Enviado</option>
                                    <option value="completado">Completado</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>

                            <div style={{ marginBottom: '15px' }}>
                                <label style={{ display: 'block', marginBottom: '5px', color: 'var(--admin-text-muted)' }}>Código de Tracking</label>
                                <input 
                                    type="text"
                                    value={data.tracking}
                                    onChange={e => setData('tracking', e.target.value)}
                                    placeholder="Ej: SHP-12345"
                                    style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                />
                            </div>

                            <div style={{ marginBottom: '15px' }}>
                                <label style={{ display: 'block', marginBottom: '5px', color: 'var(--admin-text-muted)' }}>Estado de Envío</label>
                                <select 
                                    value={data.estado_envio} 
                                    onChange={e => setData('estado_envio', e.target.value)}
                                    style={{ width: '100%', padding: '10px', borderRadius: '8px', border: '1px solid var(--admin-border)', background: 'transparent', color: 'var(--admin-text-main)' }}
                                >
                                    <option value="">Seleccionar...</option>
                                    <option value="Preparando">Preparando</option>
                                    <option value="Enviado">Enviado / En Tránsito</option>
                                    <option value="Entregado">Entregado</option>
                                </select>
                            </div>
                            
                            <button 
                                type="submit" 
                                disabled={processing}
                                style={{ width: '100%', background: '#1d4ed8', color: 'white', border: 'none', padding: '10px', borderRadius: '8px', fontWeight: 'bold', cursor: processing ? 'not-allowed' : 'pointer' }}
                            >
                                {processing ? 'Actualizando...' : 'Guardar Estado'}
                            </button>
                        </form>
                    </div>

                    <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                        <h2 style={{ fontSize: '18px', fontWeight: 'bold', color: 'var(--admin-text-main)', borderBottom: '1px solid var(--admin-border)', paddingBottom: '10px', marginBottom: '15px' }}>Información del Cliente</h2>
                        {pedido.usuario ? (
                            <>
                                <p style={{ color: 'var(--admin-text-main)', marginBottom: '5px' }}><strong>Nombre:</strong> {pedido.usuario.nombres} {pedido.usuario.apellidos}</p>
                                <p style={{ color: 'var(--admin-text-muted)', marginBottom: '5px' }}><strong>Email:</strong> {pedido.usuario.email}</p>
                                <p style={{ color: 'var(--admin-text-muted)' }}><strong>Teléfono:</strong> {pedido.usuario.telefono || 'N/A'}</p>
                            </>
                        ) : (
                            <p style={{ color: '#3b82f6' }}>Usuario Eliminado</p>
                        )}
                    </div>
                    <div style={{ background: 'var(--admin-bg-panel)', borderRadius: '12px', padding: '20px', boxShadow: '0 4px 6px rgba(0,0,0,0.05)' }}>
                        <h2 style={{ fontSize: '18px', fontWeight: 'bold', color: 'var(--admin-text-main)', borderBottom: '1px solid var(--admin-border)', paddingBottom: '10px', marginBottom: '15px' }}>Acciones de Pago</h2>
                        <p style={{ color: 'var(--admin-text-muted)', marginBottom: '15px', fontSize: '14px' }}>
                            Método de Pago: <strong>{pedido.pago?.metodo_pago || 'Desconocido'}</strong><br/>
                            Estado del Pago: <strong>{pedido.pago?.estado || 'Desconocido'}</strong>
                        </p>
                        
                        {pedido.pago?.estado === 'completado' && pedido.estado !== 'cancelado' ? async (
                            <button 
                                onClick={async () => { if(await confirmDialog('¿Estás seguro que deseas reembolsar y cancelar este pedido? Esta acción no se puede deshacer.')) {
                                        router.post(`/admin/pedidos/${pedido.id}/reembolsar`);
                                    }
                                }}
                                style={{ width: '100%', background: '#3b82f6', color: 'white', border: 'none', padding: '10px', borderRadius: '8px', fontWeight: 'bold', cursor: 'pointer' }}
                            >
                                Reembolsar Pedido
                            </button>
                        ) : (
                            <p style={{ color: '#3b82f6', fontSize: '14px' }}>No es posible reembolsar este pedido actualmente.</p>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
