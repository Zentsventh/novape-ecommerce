import React, { useState } from 'react';
import { useStripe, useElements, PaymentElement } from '@stripe/react-stripe-js';

export default function StripePaymentForm({ clientSecret, onSuccess, onFail }) {
    const stripe = useStripe();
    const elements = useElements();
    const [isProcessing, setIsProcessing] = useState(false);
    const [isSuccess, setIsSuccess] = useState(false);
    const [message, setMessage] = useState(null);

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!stripe || !elements) {
            return;
        }

        setIsProcessing(true);

        // Generamos Idempotency Key para el frontend si fuera necesario,
        // aunque Stripe SDK ya maneja retries automáticos.
        
        const { error, paymentIntent } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                // Return URL para redirección si 3D Secure lo requiere
                return_url: `${window.location.origin}/checkout/success`,
            },
            redirect: "if_required"
        });

        if (error) {
            setMessage(error.message);
            onFail(error.message);
            setIsProcessing(false);
        } else if (paymentIntent && paymentIntent.status === 'succeeded') {
            setIsProcessing(false);
            setIsSuccess(true);
            setTimeout(() => {
                onSuccess(paymentIntent);
            }, 2500);
        } else {
            setMessage("Procesando pago, por favor espera.");
            setIsProcessing(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} style={{ width: '100%', marginTop: '10px' }}>
            {isProcessing && !isSuccess && (
                <div style={{ 
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, 
                    background: 'rgba(15, 23, 42, 0.8)', 
                    backdropFilter: 'blur(8px)',
                    zIndex: 9999, 
                    display: 'flex', flexDirection: 'column', justifyContent: 'center', alignItems: 'center' 
                }}>
                    <div style={{ width: '60px', height: '60px', border: '5px solid rgba(255,255,255,0.2)', borderTop: '5px solid white', borderRadius: '50%', animation: 'spin 1s linear infinite' }}></div>
                    <style>{`@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`}</style>
                    <p style={{ marginTop: '24px', fontSize: '20px', fontWeight: 'bold', color: 'white', textShadow: '0 2px 4px rgba(0,0,0,0.5)' }}>Procesando pago seguro...</p>
                    <p style={{ fontSize: '15px', color: '#cbd5e1', marginTop: '8px' }}>Por favor, no cierres ni actualices esta ventana.</p>
                </div>
            )}

            {isSuccess && (
                <div style={{ 
                    position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, 
                    background: 'rgba(255, 255, 255, 0.7)', 
                    backdropFilter: 'blur(8px)',
                    zIndex: 9999, 
                    display: 'flex', flexDirection: 'column', justifyContent: 'center', alignItems: 'center',
                    animation: 'fadeIn 0.3s ease-out'
                }}>
                    <style>{`
                        @keyframes popIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
                        @keyframes drawCheck { 0% { stroke-dashoffset: 50; } 100% { stroke-dashoffset: 0; } }
                    `}</style>
                    <div style={{
                        background: 'linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%)',
                        padding: '50px 60px',
                        borderRadius: '24px',
                        boxShadow: '0 25px 50px -12px rgba(37, 99, 235, 0.4)',
                        display: 'flex', flexDirection: 'column', alignItems: 'center',
                        color: 'white',
                        textAlign: 'center',
                        animation: 'popIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards'
                    }}>
                        <div style={{ 
                            width: '80px', height: '80px', backgroundColor: 'rgba(255,255,255,0.15)', 
                            borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', 
                            marginBottom: '20px', border: '2px solid rgba(255,255,255,0.3)',
                        }}>
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" style={{ strokeDasharray: 50, strokeDashoffset: 50, animation: 'drawCheck 0.5s ease-out 0.3s forwards' }}>
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <h2 style={{ fontSize: '32px', fontWeight: '800', marginBottom: '10px', letterSpacing: '-0.5px' }}>¡Pago Exitoso!</h2>
                        <p style={{ fontSize: '16px', color: 'white', opacity: 0.9, marginBottom: '30px' }}>Tu compra se ha procesado con total seguridad.</p>
                        
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', background: 'rgba(0,0,0,0.2)', padding: '10px 24px', borderRadius: '99px', fontSize: '15px', fontWeight: '500', color: 'white' }}>
                            <div style={{ width: '20px', height: '20px', border: '3px solid rgba(255,255,255,0.2)', borderTop: '3px solid white', borderRadius: '50%', animation: 'spin 1s linear infinite' }}></div>
                            Preparando tu orden...
                        </div>
                    </div>
                </div>
            )}
            
            <div style={{ 
                border: '1px solid #d1d5db', 
                borderRadius: '8px', 
                padding: '20px', 
                backgroundColor: '#f9fafb',
                marginBottom: '20px',
                boxShadow: 'inset 0 1px 2px rgba(0,0,0,0.05)'
            }}>
                <PaymentElement />
            </div>

            {message && (
                <div style={{ 
                    color: '#ef4444', 
                    fontSize: '13px', 
                    marginBottom: '15px', 
                    display: 'flex', 
                    alignItems: 'center', 
                    gap: '5px',
                    backgroundColor: '#fef2f2',
                    padding: '10px',
                    borderRadius: '6px'
                }}>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    {message}
                </div>
            )}
            
            <button 
                type="submit" 
                disabled={!stripe || isProcessing}
                style={{
                    width: '100%',
                    backgroundColor: isProcessing ? '#9ca3af' : '#2563eb',
                    color: 'white',
                    padding: '14px',
                    borderRadius: '30px',
                    border: 'none',
                    fontWeight: 'bold',
                    fontSize: '16px',
                    cursor: isProcessing ? 'not-allowed' : 'pointer',
                    transition: 'all 0.3s ease',
                    boxShadow: isProcessing ? 'none' : '0 4px 6px -1px rgba(37, 99, 235, 0.4)',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    gap: '10px'
                }}
            >
                {isProcessing ? (
                    <>
                        <svg className="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                        Procesando pago seguro...
                    </>
                ) : (
                    <>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Confirmar Pago Seguro
                    </>
                )}
            </button>
            <p style={{ textAlign: 'center', fontSize: '11px', color: '#6b7280', marginTop: '10px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '4px' }}>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Tus datos están protegidos y encriptados.
            </p>
        </form>
    );
}
