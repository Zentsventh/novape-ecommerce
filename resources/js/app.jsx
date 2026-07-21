import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import ChatBot from './Components/Home/ChatBot';
import '../css/home/chatbot.css';

/* Wrapper global que muestra el ChatBot en páginas públicas (no admin). */
function GlobalLayout({ children, pageName = '' }) {
    const isAdmin = typeof pageName === 'string' && (pageName.startsWith('Admin/') || pageName.startsWith('Auth/'));

    return (
        <>
            {children}
            {!isAdmin && <ChatBot />}
        </>
    );
}

createInertiaApp({
    title: (title) => title ? `${title} - Novape` : 'Novape',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        let page = pages[`./Pages/${name}.jsx`];
        return page.default || page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <GlobalLayout pageName={props?.initialPage?.component || ''}>
                <App {...props} />
            </GlobalLayout>
        );
    },
});

