import { Link, usePage } from '@inertiajs/react';

/* Renderiza el pie de página con enlaces, contacto y redes sociales. */
export default function Footer() {
    const { globalConfig } = usePage().props;

    const aboutLinks = [
        { label: 'Quiénes somos', href: '/nosotros' },
        { label: 'Trabaja con nosotros', href: '/trabaja-con-nosotros' },
        { label: 'Términos y condiciones', href: '/terminos' },
        { label: 'Políticas de privacidad', href: '/privacidad' }
    ];
    const helpLinks = [
        { label: 'Centro de ayuda', href: '/ayuda' },
        { label: 'Seguimiento de pedido', href: '/seguimiento' },
        { label: 'Devoluciones', href: '/devoluciones' },
        { label: 'Preguntas frecuentes', href: '/faq' }
    ];
    const socials = [
        { label: 'Facebook', href: globalConfig?.facebook_url || 'https://facebook.com' },
        { label: 'Instagram', href: globalConfig?.instagram_url || 'https://instagram.com' }
    ];

    return (
        <footer className="efe-footer">
            <div className="efe-footer-grid">
                <div>
                    <h4 className="efe-footer-title">Sobre Nosotros</h4>
                    {aboutLinks.map(item => (
                        <Link key={item.href} href={item.href} className="efe-footer-link">{item.label}</Link>
                    ))}
                </div>

                <div>
                    <h4 className="efe-footer-title">Ayuda</h4>
                    {helpLinks.map(item => (
                        <Link key={item.href} href={item.href} className="efe-footer-link">{item.label}</Link>
                    ))}
                </div>

                <div>
                    <h4 className="efe-footer-title">Contáctanos</h4>
                    <p className="efe-footer-contact">
                        📞 {globalConfig?.telefono_contacto || '(01) 619-3535'}<br />
                        📧 {globalConfig?.email_contacto || 'contacto@novape.com'}<br />
                        📍 Lima, Perú
                    </p>
                </div>

                <div>
                    <h4 className="efe-footer-title">Síguenos</h4>
                    <div className="efe-footer-socials">
                        {socials.map(social => (
                            <a key={social.href} href={social.href} className="efe-footer-social" target="_blank" rel="noreferrer">
                                {social.label[0]}
                            </a>
                        ))}
                    </div>
                </div>
            </div>

            <div className="efe-footer-divider">
                © {new Date().getFullYear()} NOVAPE. Todos los derechos reservados.
            </div>
        </footer>
    );
}
