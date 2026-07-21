import { useState, useEffect, useRef } from 'react';

/* Botón flotante de chatbot con efecto de radar al hacer scroll. */
export default function ChatBot() {
    const [isOpen, setIsOpen] = useState(false);
    const [message, setMessage] = useState('');
    const wrapperRef = useRef(null);
    const posRef = useRef({ target: 0, current: 0, velocity: 0 });
    const rafRef = useRef(null);
    const delayRef = useRef(null);

    useEffect(() => {
        const gravity = 0.02;
        const damping = 0.86;
        const bottomOffset = 36;
        const delayMs = 350;

        const getTargetPos = () => window.scrollY + window.innerHeight - bottomOffset;

        const scheduleTarget = () => {
            clearTimeout(delayRef.current);
            delayRef.current = setTimeout(() => {
                posRef.current.target = getTargetPos();
            }, delayMs);
        };

        const animate = () => {
            const pos = posRef.current;
            const diff = pos.target - pos.current;
            pos.velocity += diff * gravity;
            pos.velocity *= damping;
            pos.current += pos.velocity;

            if (wrapperRef.current) {
                const baseY = window.scrollY + window.innerHeight - bottomOffset;
                wrapperRef.current.style.transform = `translateY(${pos.current - baseY}px)`;
            }
            rafRef.current = requestAnimationFrame(animate);
        };

        const init = getTargetPos();
        posRef.current.target = init;
        posRef.current.current = init;

        window.addEventListener('scroll', scheduleTarget, { passive: true });
        rafRef.current = requestAnimationFrame(animate);

        return () => {
            window.removeEventListener('scroll', scheduleTarget);
            clearTimeout(delayRef.current);
            if (rafRef.current) cancelAnimationFrame(rafRef.current);
        };
    }, []);

    return (
        <>
            <div ref={wrapperRef} className="efe-chatbot-wrapper">
                {/* Anillos de radar */}
                {!isOpen && (
                    <>
                        <span className="efe-chatbot-radar-ring" />
                        <span className="efe-chatbot-radar-ring" />
                    </>
                )}

                {isOpen ? (
                    <button
                        className="efe-chatbot-btn--close"
                        onClick={() => setIsOpen(false)}
                        aria-label="Cerrar chat"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2.5" strokeLinecap="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                ) : (
                    <button
                        className="efe-chatbot-btn"
                        onClick={() => setIsOpen(true)}
                        aria-label="Abrir chat"
                    >
                        <img
                            src="/images/chatbot_novape.png"
                            alt="Chatbot Novape"
                        />
                    </button>
                )}
            </div>

            {isOpen && (
                <div className="efe-chat-panel">
                    <div className="efe-chat-header">
                        <div className="efe-chat-header-left">
                            <div className="efe-chat-logo">
                                <img
                                    src="/images/chatbot_novape.png"
                                    alt="Novape"
                                />
                            </div>
                            <div className="efe-chat-header-text">
                                <h4>Novape</h4>
                                <div className="efe-chat-online">
                                    <span className="efe-chat-online-dot" />
                                    En línea
                                </div>
                            </div>
                        </div>
                        <button className="efe-chat-minimize" onClick={() => setIsOpen(false)}>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </button>
                    </div>

                    <div className="efe-chat-body">
                        <div className="efe-chat-welcome">
                            <div className="efe-chat-welcome-icon">
                                <img
                                    src="/images/chatbot_novape.png"
                                    alt="Novape Bot"
                                />
                            </div>
                            <h5 className="efe-chat-welcome-title">¡Hola! Soy Novape</h5>
                            <p className="efe-chat-welcome-text">
                                Tu asistente virtual de EFE. Estoy aquí para ayudarte con tus compras, consultas y más.
                            </p>
                        </div>

                        <div className="efe-chat-suggestions">
                            <span className="efe-chat-suggestions-label">Preguntas frecuentes</span>
                            <button className="efe-chat-chip">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                                    <line x1="12" y1="22.08" x2="12" y2="12" />
                                </svg>
                                Estado de mi pedido
                            </button>
                            <button className="efe-chat-chip">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                    <rect x="1" y="3" width="15" height="13" rx="2" />
                                    <path d="M16 8h4l3 3v5h-7V8z" />
                                    <circle cx="5.5" cy="18.5" r="2.5" />
                                    <circle cx="18.5" cy="18.5" r="2.5" />
                                </svg>
                                Información de envío
                            </button>
                            <button className="efe-chat-chip">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                    <rect x="1" y="4" width="22" height="16" rx="2" />
                                    <line x1="1" y1="10" x2="23" y2="10" />
                                </svg>
                                Métodos de pago
                            </button>
                            <button className="efe-chat-chip">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                    <polyline points="23 4 23 10 17 10" />
                                    <polyline points="1 20 1 14 7 14" />
                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                                </svg>
                                Cambios y devoluciones
                            </button>
                        </div>
                    </div>

                    <div className="efe-chat-footer">
                        <div className="efe-chat-input-row">
                            <input
                                type="text"
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                placeholder="Escribe tu mensaje..."
                                className="efe-chat-input"
                            />
                            <button className="efe-chat-send" disabled={!message.trim()}>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13" />
                                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                                </svg>
                            </button>
                        </div>
                        <span className="efe-chat-powered">Powered by Novape</span>
                    </div>
                </div>
            )}
        </>
    );
}

