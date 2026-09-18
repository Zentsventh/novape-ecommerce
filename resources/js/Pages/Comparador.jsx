import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import Header from '../Components/Home/Header';
import Footer from '../Components/Home/Footer';

export default function Comparador() {
    const [productos, setProductos] = useState([]);

    return (
        <div className="layout-container">
            <Head title="Comparador" />
            <Header />
            <div style={{ maxWidth: '1200px', margin: '40px auto', padding: '20px', minHeight: '60vh' }}>
                <h1 style={{ fontSize: '28px', fontWeight: 'bold', marginBottom: '20px' }}>Comparador de Productos</h1>
                {productos.length > 0 ? (
                    <div className="comparador-table" style={{ overflowX: 'auto' }}>
                        {/* Aquí iría la tabla de comparación real */}
                        <p>Comparando {productos.length} productos...</p>
                    </div>
                ) : (
                    <div style={{ textAlign: 'center', padding: '60px 40px', background: '#f9fafb', borderRadius: '16px', border: '1px dashed #cbd5e1' }}>
                        <svg style={{ width: '64px', height: '64px', margin: '0 auto', color: '#94a3b8' }} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p style={{ fontSize: '18px', color: '#475569', marginTop: '16px' }}>No tienes productos en tu lista de comparación.</p>
                        <Link href="/catalogo" style={{ display: 'inline-block', marginTop: '24px', padding: '12px 24px', background: '#0073D8', color: 'white', borderRadius: '8px', textDecoration: 'none', fontWeight: '500' }}>
                            Añadir Productos
                        </Link>
                    </div>
                )}
            </div>
            <Footer />
        </div>
    );
}
