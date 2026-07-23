import { Link, usePage, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { useConfirm } from '@/Contexts/ConfirmContext';


export default function Index({ reviews }) {
    const confirmDialog = useConfirm();

    const { flash } = usePage().props;

    const toggleAprobacion = async (id) => {
        if (await confirmDialog('¿Estás seguro de cambiar el estado de esta reseña?')) {
            router.post(`/admin/reviews/${id}/toggle`);
        }
    };

    const handleDelete = async (id) => {
        if (await confirmDialog('¿Estás seguro de que deseas eliminar esta reseña permanentemente?')) {
            router.delete(`/admin/reviews/${id}`);
        }
    };

    const renderStars = (calificacion) => {
        let stars = [];
        for(let i = 1; i <= 5; i++) {
            stars.push(
                <svg key={i} width="16" height="16" viewBox="0 0 24 24" fill={i <= calificacion ? "#FBBF24" : "none"} stroke={i <= calificacion ? "#FBBF24" : "#D1D5DB"} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
            );
        }
        return <div style={{display: 'flex', gap: '2px'}}>{stars}</div>;
    };

    return (
        <AdminLayout>
            <div className="admin-header-row">
                <h1 className="admin-page-title">Moderación de Reseñas</h1>
            </div>

            {flash.success && (
                <div style={{ background: '#ecfdf5', color: '#065f46', padding: '12px', borderRadius: '8px', marginBottom: '20px' }}>
                    {flash.success}
                </div>
            )}

            <div className="admin-table-container">
                <table className="admin-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cliente</th>
                            <th>Calificación</th>
                            <th>Comentario</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {reviews.data.map((review) => (
                            <tr key={review.id}>
                                <td>
                                    <div style={{ fontWeight: '600' }}>{review.producto?.nombre}</div>
                                </td>
                                <td>
                                    <div>{review.usuario?.nombres} {review.usuario?.apellidos}</div>
                                    <div style={{ fontSize: '12px', color: '#6B7280' }}>{review.usuario?.email}</div>
                                </td>
                                <td>{renderStars(review.calificacion)}</td>
                                <td style={{ maxWidth: '250px' }}>
                                    <div style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }} title={review.comentario}>
                                        {review.comentario}
                                    </div>
                                </td>
                                <td>{new Date(review.created_at).toLocaleDateString()}</td>
                                <td>
                                    <span style={{
                                        padding: '4px 8px',
                                        borderRadius: '12px',
                                        fontSize: '12px',
                                        fontWeight: '600',
                                        background: review.aprobado ? '#D1FAE5' : '#FEF3C7',
                                        color: review.aprobado ? '#065F46' : '#92400E'
                                    }}>
                                        {review.aprobado ? 'Aprobada' : 'Pendiente'}
                                    </span>
                                </td>
                                <td>
                                    <div style={{ display: 'flex', gap: '8px' }}>
                                        <button 
                                            onClick={() => toggleAprobacion(review.id)}
                                            style={{
                                                padding: '6px 12px',
                                                borderRadius: '6px',
                                                border: 'none',
                                                background: review.aprobado ? '#F3F4F6' : '#2563eb',
                                                color: review.aprobado ? '#374151' : 'white',
                                                cursor: 'pointer',
                                                fontSize: '13px',
                                                fontWeight: '500'
                                            }}
                                        >
                                            {review.aprobado ? 'Ocultar' : 'Aprobar'}
                                        </button>
                                        <button 
                                            onClick={() => handleDelete(review.id)}
                                            style={{
                                                padding: '6px 12px',
                                                borderRadius: '6px',
                                                border: 'none',
                                                background: '#3b82f6',
                                                color: 'white',
                                                cursor: 'pointer',
                                                fontSize: '13px',
                                                fontWeight: '500'
                                            }}
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {reviews.data.length === 0 && (
                            <tr>
                                <td colSpan="7" style={{ textAlign: 'center', padding: '20px', color: '#6B7280' }}>
                                    No hay reseñas registradas en el sistema.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {/* Paginación */}
            {reviews.links && reviews.links.length > 3 && (
                <div style={{ display: 'flex', justifyContent: 'center', gap: '5px', marginTop: '20px' }}>
                    {reviews.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url}
                            className={`admin-page-link ${link.active ? 'active' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            style={{
                                padding: '8px 12px',
                                border: '1px solid #E5E7EB',
                                borderRadius: '6px',
                                background: link.active ? 'var(--admin-primary)' : 'white',
                                color: link.active ? 'white' : '#374151',
                                textDecoration: 'none'
                            }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
