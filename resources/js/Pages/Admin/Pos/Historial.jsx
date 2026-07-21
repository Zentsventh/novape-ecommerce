import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { FileText, ArrowLeft, Search, Receipt } from 'lucide-react';
import AdminLayout from '../../../Layouts/AdminLayout';
import '../../../../css/admin/admin.css';

export default function HistorialPos({ historial, filters, logoUrl }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/pos/historial', { search: searchTerm }, { preserveState: true });
    };

    const formatearFecha = (fecha) => {
        if (!fecha) return '-';
        return new Date(fecha).toLocaleString('es-PE', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        });
    };

    return (
        <AdminLayout logoUrl={logoUrl}>
            <Head title="Historial POS" />
            
            <div className="admin-header-flex">
                <h1 className="admin-title">
                    <FileText size={24} style={{ marginRight: '10px' }} />
                    Historial de Ventas POS
                </h1>
                <Link href="/admin/pos" className="admin-btn-secondary" style={{ display: 'flex', alignItems: 'center', gap: '8px', width: 'fit-content', textDecoration: 'none' }}>
                    <ArrowLeft size={16} /> Volver al POS
                </Link>
            </div>

            <div className="admin-card">
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '20px' }}>
                    <form onSubmit={handleSearch} style={{ display: 'flex', gap: '10px', width: '100%', maxWidth: '400px', alignItems: 'center' }}>
                        <div style={{ position: 'relative', flex: 1 }}>
                            <Search size={18} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--admin-text-muted)' }} />
                            <input 
                                type="text"
                                style={{ 
                                    padding: '10px 15px 10px 40px', 
                                    width: '100%', 
                                    borderRadius: '9999px', 
                                    border: '1px solid var(--admin-border)', 
                                    fontSize: '14px', 
                                    outline: 'none',
                                    boxSizing: 'border-box',
                                    transition: 'border-color 0.3s ease'
                                }}
                                placeholder="Buscar por código o cliente..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                onFocus={(e) => e.target.style.borderColor = 'var(--admin-primary)'}
                                onBlur={(e) => e.target.style.borderColor = 'var(--admin-border)'}
                            />
                        </div>
                        <button type="submit" className="admin-btn-primary" style={{ whiteSpace: 'nowrap' }}>Buscar</button>
                    </form>
                </div>

                <div className="admin-table-container">
                    <table className="admin-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Código Ticket</th>
                                <th>Cajero</th>
                                <th>Cliente</th>
                                <th>Comprobante</th>
                                <th>Método de Pago</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {historial.data.length > 0 ? (
                                historial.data.map(venta => (
                                    <tr key={venta.id}>
                                        <td>{formatearFecha(venta.created_at)}</td>
                                        <td><span className="admin-badge">{venta.codigo_ticket}</span></td>
                                        <td>{venta.cajero_nombre || 'Desconocido'}</td>
                                        <td>{venta.cliente_nombre || 'Público en General'}</td>
                                        <td>{venta.tipo_comprobante ? venta.tipo_comprobante.toUpperCase() : 'TICKET'}</td>
                                        <td>{venta.metodo_pago || '-'}</td>
                                        <td style={{ fontWeight: 'bold' }}>S/ {Number(venta.total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        <td>
                                            <a 
                                                href={`/admin/pos/ticket/${venta.id}`} 
                                                target="_blank" 
                                                className="admin-action-btn print" 
                                                title="Imprimir Ticket"
                                            >
                                                <Receipt size={18} />
                                            </a>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="8" style={{ textAlign: 'center', padding: '20px' }}>
                                        No se encontraron ventas en el historial.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Paginación simple */}
                {historial.links && historial.links.length > 3 && (
                    <div style={{ marginTop: '20px', display: 'flex', gap: '5px', justifyContent: 'center' }}>
                        {historial.links.map((link, i) => (
                            <Link 
                                key={i}
                                href={link.url || '#'}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                style={{
                                    padding: '5px 12px',
                                    border: '1px solid var(--admin-border)',
                                    borderRadius: '5px',
                                    textDecoration: 'none',
                                    background: link.active ? 'var(--admin-primary)' : 'white',
                                    color: link.active ? 'white' : 'inherit',
                                    pointerEvents: link.url ? 'auto' : 'none',
                                    opacity: link.url ? 1 : 0.5
                                }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
