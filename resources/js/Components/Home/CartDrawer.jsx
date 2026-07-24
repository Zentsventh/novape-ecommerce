import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import LoginModal from './LoginModal';
import { DEFAULT_IMAGE } from './constants';

const formatPrice = (price) =>
    new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        price
    );

export default function CartDrawer({ cart, isOpen, onClose }) {
    const { auth, flash } = usePage().props;
    const [isLoginOpen, setIsLoginOpen] = useState(false);

    const FREE_SHIPPING_THRESHOLD = 299;
    const currentTotal = cart?.total || 0;
    const amountLeftForFreeShipping = Math.max(0, FREE_SHIPPING_THRESHOLD - currentTotal);
    const progressPercentage = Math.min(100, (currentTotal / FREE_SHIPPING_THRESHOLD) * 100);

    const handleUpdate = (productoId, currentQty, amount) => {
        const newQty = currentQty + amount;
        if (newQty < 1 || newQty > 5) return;
        router.post(
            '/cart/update',
            { producto_id: productoId, cantidad: newQty },
            { preserveScroll: true }
        );
    };

    const handleRemove = (productoId) => {
        router.post('/cart/remove', { producto_id: productoId }, { preserveScroll: true });
    };

    return (
        <>
            <div className={`efe-cart-overlay ${isOpen ? 'is-open' : ''}`} onClick={onClose} />
            <div className={`efe-cart-drawer ${isOpen ? 'is-open' : ''}`}>
                <div className="efe-cart-header">
                    <h2>
                        <button className="efe-cart-close" onClick={onClose}>
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                            >
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                        </button>
                        Carrito
                    </h2>
                    {cart?.items?.length > 0 && (
                        <button
                            className="efe-cart-clear-btn"
                            onClick={() => router.post('/cart/clear')}
                        >
                            Limpiar carrito
                        </button>
                    )}
                </div>

                {flash?.error && (
                    <div
                        style={{
                            margin: '10px 20px',
                            padding: '10px',
                            backgroundColor: '#fef2f2',
                            border: '1px solid #f87171',
                            color: '#dc2626',
                            borderRadius: '4px',
                            fontSize: '14px',
                        }}
                    >
                        {flash.error}
                    </div>
                )}

                <div className="efe-cart-body">
                    {!cart?.items?.length ? (
                        <div className="efe-cart-empty">
                            <svg
                                width="64"
                                height="64"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="1.5"
                                strokeLinecap="round"
                            >
                                <circle cx="9" cy="21" r="1" />
                                <circle cx="20" cy="21" r="1" />
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                            </svg>
                            <h3>Tu carrito está vacío</h3>
                            <p>¡Agrega productos increíbles!</p>
                        </div>
                    ) : (
                        <div className="efe-cart-items">
                            {cart.items.map((item) => (
                                <div key={item.id} className="efe-cart-item">
                                    <div className="efe-cart-item-header">
                                        <div className="efe-cart-item-img">
                                            <img
                                                src={item.imagen || DEFAULT_IMAGE}
                                                alt={item.nombre}
                                            />
                                        </div>
                                        <div className="efe-cart-item-info">
                                            <span className="efe-cart-item-brand">
                                                {item.marca || 'GENÉRICO'}
                                            </span>
                                            <h4 className="efe-cart-item-title">{item.nombre}</h4>
                                            <div className="efe-cart-item-price-row">
                                                <span className="efe-cart-item-price-current">
                                                    S/ {formatPrice(item.precio)}
                                                </span>
                                            </div>

                                            <div className="efe-cart-qty-ctrl">
                                                <button
                                                    className="efe-cart-qty-btn"
                                                    onClick={() =>
                                                        handleUpdate(item.id, item.cantidad, -1)
                                                    }
                                                >
                                                    -
                                                </button>
                                                <span className="efe-cart-qty-val">
                                                    {item.cantidad}
                                                </span>
                                                <button
                                                    className="efe-cart-qty-btn"
                                                    onClick={() =>
                                                        handleUpdate(item.id, item.cantidad, 1)
                                                    }
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                        <button
                                            className="efe-cart-remove-btn"
                                            onClick={() => handleRemove(item.id)}
                                        >
                                            <svg
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                                strokeLinecap="round"
                                            >
                                                <polyline points="3 6 5 6 21 6" />
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {cart?.items?.length > 0 && (
                    <div className="efe-cart-footer">
                        <div className="efe-cart-total-row">
                            <span className="efe-cart-total-label">Total:</span>
                            <span className="efe-cart-total-val">S/ {formatPrice(cart.total)}</span>
                        </div>
                        <div className="efe-cart-savings-row">
                            <span className="efe-cart-savings-label">Ahorraste:</span>
                            <span className="efe-cart-savings-val">S/ 0.00</span>
                        </div>
                        <div style={{ display: 'flex', gap: '10px', marginTop: '10px' }}>
                            <button
                                className="efe-cart-continue-btn"
                                onClick={onClose}
                                style={{
                                    flex: 1,
                                    padding: '12px',
                                    background: '#f3f4f6',
                                    color: '#374151',
                                    border: '1px solid #d1d5db',
                                    borderRadius: '6px',
                                    fontWeight: '600',
                                    cursor: 'pointer',
                                    fontSize: '14px',
                                    transition: 'all 0.2s ease',
                                    textAlign: 'center',
                                }}
                                onMouseOver={(e) => {
                                    e.currentTarget.style.background = '#e5e7eb';
                                }}
                                onMouseOut={(e) => {
                                    e.currentTarget.style.background = '#f3f4f6';
                                }}
                            >
                                Seguir comprando
                            </button>
                            <button
                                className="efe-cart-checkout-btn"
                                style={{
                                    flex: 1,
                                    margin: 0,
                                    padding: '12px',
                                    borderRadius: '6px',
                                    fontSize: '14px',
                                    fontWeight: '600',
                                }}
                                onClick={() => {
                                    if (!auth.user) {
                                        setIsLoginOpen(true);
                                    } else {
                                        onClose();
                                        router.get('/checkout');
                                    }
                                }}
                            >
                                Ir a Pagar
                            </button>
                        </div>
                    </div>
                )}
            </div>

            <LoginModal
                isOpen={isLoginOpen}
                onClose={() => setIsLoginOpen(false)}
                onSuccessCallback={() => {
                    setIsLoginOpen(false);
                    onClose();
                    router.get('/checkout');
                }}
            />
        </>
    );
}
