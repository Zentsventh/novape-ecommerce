import { Head } from '@inertiajs/react';
import Header from '../Components/Home/Header';
import Footer from '../Components/Home/Footer';

import '../../css/home/base.css';
import '../../css/home/header.css';
import '../../css/home/info-pages.css';
import '../../css/home/footer.css';

export default function InfoPage({ title, sections = [], logoUrl }) {
    return (
        <div className="efe-home">
            <Head title={title} />

            <Header minimal={true} logoUrl={logoUrl} />

            <main className="info-page">
                <div className="info-container">
                    <h1 className="info-title">{title}</h1>
                    <div className="info-card">
                        {sections.length === 0 ? (
                            <p className="info-text">Contenido en preparacion.</p>
                        ) : (
                            sections.map((section) => (
                                <div key={section.heading} className="info-section">
                                    <h2 className="info-section-title">{section.heading}</h2>
                                    <p className="info-text">{section.body}</p>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </main>

            <Footer />
        </div>
    );
}
