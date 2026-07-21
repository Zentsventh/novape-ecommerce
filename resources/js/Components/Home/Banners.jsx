import { SHIPPING_BANNER, UNICA_BANNER, BOTTOM_BANNER } from './constants';

/* Renderiza el banner de envío gratuito. */
export function ShippingBanner() {
    return (
        <div className="efe-banner-full" style={{ maxHeight: '38px', overflow: 'hidden', backgroundColor: '#0b243b' }}>
            <img src={SHIPPING_BANNER} alt="Envío Gratis a todo el Perú" style={{ width: '100%', height: '38px', objectFit: 'cover', objectPosition: 'center', display: 'block' }} />
        </div>
    );
}

/* Renderiza el banner de tarjeta Única. */
export function UnicaBanner() {
    return (
        <div className="efe-banner-full">
            <img src={UNICA_BANNER} alt="Tarjeta Única" />
        </div>
    );
}

/* Renderiza el banner inferior promocional. */
export function BottomBanner() {
    return (
        <div className="efe-banner-full">
            <img src={BOTTOM_BANNER} alt="Compra Hoy Recógelo Hoy" />
        </div>
    );
}
