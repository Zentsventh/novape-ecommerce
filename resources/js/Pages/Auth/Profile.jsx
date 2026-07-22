import { useState, useEffect, useMemo } from 'react';
import { Head, router, usePage, Link, useForm } from '@inertiajs/react';
import Header from '../../Components/Home/Header';
import Footer from '../../Components/Home/Footer';
import '../../../css/home/base.css';
import ubigeoData from 'ubigeo-peru';

const ubigeo = ubigeoData.reniec;

export default function Profile({ usuario = {}, pedidos = [], direcciones = [], tarjetas = [], datosReembolso = null, listas = [], sesiones = [], activeTabParam = 'home' }) {
    const { auth, flash, errors: pageErrors } = usePage().props;
    const user = auth?.user || usuario;
    const [currentView, setCurrentView] = useState(activeTabParam);
    
    // Modals state
    const [showEditProfile, setShowEditProfile] = useState(false);
    const [showAddAddress, setShowAddAddress] = useState(false);
    const [showAddTarjeta, setShowAddTarjeta] = useState(false);
    const [showDeleteAccount, setShowDeleteAccount] = useState(false);
    const [showAddLista, setShowAddLista] = useState(false);
    const [showEditPassword, setShowEditPassword] = useState(false);

    // Form for profile
    const profileForm = useForm({
        nombres: user.nombres || '',
        apellidos: user.apellidos || '',
        fecha_nacimiento: user.fecha_nacimiento || '',
        dni: user.dni || '',
        telefono: user.telefono || '',
    });

    const addressForm = useForm({
        direccion: '', referencia: '', departamento: '', provincia: '', distrito: '', codigo_postal: '', principal: false
    });

    const tarjetaForm = useForm({
        numero_tarjeta: '', fecha_vencimiento: '', cvv: '', nombre_titular: ''
    });

    const reembolsoForm = useForm({
        tipo_documento: datosReembolso?.tipo_documento || 'DNI',
        numero_documento: datosReembolso?.numero_documento || '',
        nombres_titular: datosReembolso?.nombres_titular || '',
        apellidos_titular: datosReembolso?.apellidos_titular || '',
        telefono_titular: datosReembolso?.telefono_titular || '',
        correo_titular: datosReembolso?.correo_titular || '',
        banco: datosReembolso?.banco || '',
        tipo_cuenta: datosReembolso?.tipo_cuenta || '',
        numero_cuenta: datosReembolso?.numero_cuenta || '',
        cci: datosReembolso?.cci || ''
    });

    const passwordForm = useForm({
        current_password: '', password: '', password_confirmation: ''
    });

    const deleteAccountForm = useForm({
        password: ''
    });

    const [showEditPhone, setShowEditPhone] = useState(false);
    const [phoneOtpStep, setPhoneOtpStep] = useState('phone');
    const [showTooltip, setShowTooltip] = useState(false);

    const phoneForm = useForm({
        telefono: user.telefono || '',
        codigo: ''
    });

    const submitPhoneRequest = (e) => {
        e.preventDefault();
        phoneForm.post('/perfil/celular/solicitar-codigo', {
            preserveScroll: true,
            onSuccess: () => setPhoneOtpStep('otp')
        });
    };

    const submitPhoneVerify = (e) => {
        e.preventDefault();
        phoneForm.post('/perfil/celular/verificar-codigo', {
            preserveScroll: true,
            onSuccess: () => {
                setShowEditPhone(false);
                setPhoneOtpStep('phone');
                phoneForm.reset('codigo');
            }
        });
    };

    const listaForm = useForm({
        nombre: '', es_publica: false
    });

    const [depCode, setDepCode] = useState('');
    const [provCode, setProvCode] = useState('');

    const departamentos = useMemo(() => ubigeo.filter(u => u.provincia === '00' && u.distrito === '00'), []);
    const provincias = useMemo(() => depCode ? ubigeo.filter(u => u.departamento === depCode && u.provincia !== '00' && u.distrito === '00') : [], [depCode]);
    const distritos = useMemo(() => provCode ? ubigeo.filter(u => u.departamento === depCode && u.provincia === provCode && u.distrito !== '00') : [], [depCode, provCode]);

    const handleDepChange = (e) => {
        const dName = e.target.value;
        addressForm.setData('departamento', dName);
        addressForm.setData('provincia', '');
        addressForm.setData('distrito', '');
        const dep = departamentos.find(d => d.nombre === dName);
        setDepCode(dep ? dep.departamento : '');
        setProvCode('');
    };

    const handleProvChange = (e) => {
        const pName = e.target.value;
        addressForm.setData('provincia', pName);
        addressForm.setData('distrito', '');
        const prov = provincias.find(p => p.nombre === pName);
        setProvCode(prov ? prov.provincia : '');
    };

    const submitProfile = (e) => {
        e.preventDefault();
        profileForm.post('/perfil/update', { preserveScroll: true, onSuccess: () => setShowEditProfile(false) });
    };

    const submitAddress = (e) => {
        e.preventDefault();
        addressForm.post('/perfil/direccion', { preserveScroll: true, onSuccess: () => { setShowAddAddress(false); addressForm.reset(); } });
    };

    const submitTarjeta = (e) => {
        e.preventDefault();
        tarjetaForm.post('/perfil/tarjetas', { preserveScroll: true, onSuccess: () => { setShowAddTarjeta(false); tarjetaForm.reset(); } });
    };

    const submitReembolso = (e) => {
        e.preventDefault();
        reembolsoForm.post('/perfil/reembolso', { preserveScroll: true });
    };

    const submitPassword = (e) => {
        e.preventDefault();
        passwordForm.post('/perfil/password', { preserveScroll: true, onSuccess: () => { setShowEditPassword(false); passwordForm.reset(); } });
    };

    const submitDeleteAccount = (e) => {
        e.preventDefault();
        if(confirm("¿Estás completamente seguro de que deseas eliminar tu cuenta? Esta acción no se puede deshacer.")) {
            deleteAccountForm.delete('/perfil/cuenta', { preserveScroll: true });
        }
    };

    const submitLista = (e) => {
        e.preventDefault();
        listaForm.post('/perfil/listas', { preserveScroll: true, onSuccess: () => { setShowAddLista(false); listaForm.reset(); } });
    };

    useEffect(() => {
        if (activeTabParam !== currentView) {
            setCurrentView(activeTabParam);
        }
    }, [activeTabParam]);

    const [filterOrder, setFilterOrder] = useState('');
    const formatCurrency = (val) => 'S/ ' + new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2 }).format(val);

    const changeView = (view) => {
        setCurrentView(view);
        router.get('/perfil', { tab: view }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const renderHome = () => (
        <div style={{ maxWidth: '1000px', margin: '40px auto', padding: '0 20px', width: '100%' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                <h1 style={{ fontSize: '24px', color: '#333', fontWeight: '400' }}>Hola, {user.nombres}</h1>
                <div style={{ background: '#f5f5f5', borderRadius: '8px', padding: '10px 20px', display: 'flex', alignItems: 'center', gap: '15px', cursor: 'pointer', transition: 'background 0.2s' }}>
                    <div style={{ width: '24px', height: '24px', background: '#00B4FF', borderRadius: '50%' }}></div>
                    <div>
                        <div style={{ fontSize: '14px', fontWeight: 'bold', color: '#333' }}>Tienes 0 Puntos</div>
                        <div style={{ fontSize: '11px', color: '#666' }}>Descubre los canjes y beneficios</div>
                    </div>
                    <span style={{ color: '#00B4FF', fontWeight: 'bold' }}>›</span>
                </div>
            </div>

            <div style={{ display: 'flex', gap: '10px', marginBottom: '40px' }}>
                <button onClick={() => changeView('compras')} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', width: '120px', height: '100px', background: 'white', borderRadius: '12px', border: 'none', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', cursor: 'pointer', transition: 'all 0.2s' }}>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#444" strokeWidth="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <span style={{ fontSize: '13px', marginTop: '8px', color: '#444' }}>Mis compras</span>
                </button>
                <button onClick={() => changeView('perfil')} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', width: '120px', height: '100px', background: 'white', borderRadius: '12px', border: 'none', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', cursor: 'pointer', transition: 'all 0.2s' }}>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#444" strokeWidth="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span style={{ fontSize: '13px', marginTop: '8px', color: '#444' }}>Mi perfil</span>
                </button>
                <button style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', width: '120px', height: '100px', background: 'white', borderRadius: '12px', border: 'none', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', cursor: 'pointer', transition: 'all 0.2s' }}>
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#444" strokeWidth="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span style={{ fontSize: '13px', marginTop: '8px', color: '#444' }}>Ayuda</span>
                </button>
            </div>

            <div style={{ marginBottom: '40px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '15px' }}>
                    <h2 style={{ fontSize: '18px', fontWeight: '500', color: '#333' }}>Últimas compras ({Math.min(pedidos.length, 3)})</h2>
                    <span onClick={() => changeView('compras')} style={{ fontSize: '13px', color: '#666', cursor: 'pointer' }}>Revisar todas ›</span>
                </div>
                {pedidos.length === 0 ? (
                    <div style={{ padding: '40px', background: 'white', borderRadius: '12px', textAlign: 'center', color: '#666' }}>Aún no tienes compras.</div>
                ) : (
                    <div style={{ display: 'flex', gap: '20px', overflowX: 'auto', paddingBottom: '10px' }}>
                        {pedidos.slice(0, 3).map(pedido => {
                            const primerItem = pedido.items && pedido.items.length > 0 ? pedido.items[0] : null;
                            const imagenUrl = primerItem?.variante?.producto?.imagenes?.[0]?.ruta || '/img/placeholder.jpg';
                            const nombreProd = primerItem?.variante?.producto?.nombre || 'Producto';
                            
                            return (
                                <div key={pedido.id} onClick={() => changeView('compras')} style={{ minWidth: '280px', flex: 1, background: 'white', borderRadius: '12px', padding: '20px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', display: 'flex', gap: '15px', cursor: 'pointer' }}>
                                    <div style={{ width: '80px', height: '80px', flexShrink: 0, position: 'relative' }}>
                                        <div style={{ position: 'absolute', top: '-10px', left: '-10px', background: pedido.estado === 'Completado' ? '#a3e635' : (pedido.estado === 'Enviado' ? '#38bdf8' : '#cbd5e1'), color: '#000', fontSize: '10px', fontWeight: 'bold', padding: '2px 8px', borderRadius: '4px', zIndex: 2 }}>{pedido.estado}</div>
                                        <img src={imagenUrl} alt={nombreProd} style={{ width: '100%', height: '100%', objectFit: 'contain', border: '1px solid #f1f5f9', borderRadius: '8px' }} />
                                    </div>
                                    <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
                                        <div style={{ fontSize: '14px', color: '#333', fontWeight: '500' }}>{pedido.estado === 'Completado' ? 'Entregado' : 'Estado: ' + pedido.estado} el {new Date(pedido.created_at).toLocaleDateString('es-PE', { weekday: 'long', day: '2-digit', month: 'long'})}</div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            <div style={{ marginBottom: '40px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', marginBottom: '15px' }}>
                    <h2 style={{ fontSize: '18px', fontWeight: '500', color: '#333' }}>Mis listas</h2>
                    <span onClick={() => changeView('listas')} style={{ fontSize: '13px', color: '#666', cursor: 'pointer' }}>Ir a Mis listas ›</span>
                </div>
                <div style={{ display: 'flex', gap: '20px' }}>
                    {listas.slice(0, 3).map(lista => (
                        <div key={lista.id} onClick={() => changeView('listas')} style={{ width: '280px', background: 'white', borderRadius: '12px', padding: '20px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', display: 'flex', flexDirection: 'column', gap: '10px', cursor: 'pointer' }}>
                            <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gridTemplateRows: '1fr 1fr', gap: '5px', height: '140px' }}>
                                <div style={{ gridRow: 'span 2', background: '#f1f5f9', borderRadius: '8px', overflow: 'hidden' }}>
                                    {lista.items?.[0] && <img src={lista.items[0].producto?.imagenes?.[0]?.ruta} style={{width:'100%', height:'100%', objectFit:'cover'}} />}
                                </div>
                                <div style={{ background: '#f1f5f9', borderRadius: '8px', overflow: 'hidden' }}>
                                    {lista.items?.[1] && <img src={lista.items[1].producto?.imagenes?.[0]?.ruta} style={{width:'100%', height:'100%', objectFit:'cover'}} />}
                                </div>
                                <div style={{ background: '#f1f5f9', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#94a3b8', fontSize: '18px', fontWeight: 'bold' }}>
                                    +{(lista.items?.length || 0) > 2 ? lista.items.length - 2 : 0}
                                </div>
                            </div>
                            <div style={{ fontSize: '14px', color: '#333', fontWeight: '500' }}>{lista.nombre}</div>
                        </div>
                    ))}
                    {listas.length === 0 && (
                        <div style={{ padding: '20px', background: 'white', borderRadius: '12px', color: '#666', fontSize: '14px' }}>No tienes listas creadas.</div>
                    )}
                </div>
            </div>
        </div>
    );

    const renderCompras = () => {
        let filtered = pedidos;
        if (filterOrder) filtered = filtered.filter(p => p.codigo.toLowerCase().includes(filterOrder.toLowerCase()));

        return (
            <div style={{ maxWidth: '1000px', margin: '40px auto', padding: '0 20px', width: '100%' }}>
                <div onClick={() => changeView('home')} style={{ color: '#444', fontSize: '13px', fontWeight: '500', marginBottom: '10px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '5px' }}>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="15 18 9 12 15 6"></polyline></svg> Mi cuenta
                </div>
                <h1 style={{ fontSize: '24px', color: '#333', fontWeight: '400', marginBottom: '30px' }}>Mis compras</h1>
                <div style={{ display: 'flex', gap: '15px', marginBottom: '40px' }}>
                    <div style={{ flex: 1, position: 'relative' }}>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2" style={{ position: 'absolute', top: '12px', left: '15px' }}><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" placeholder="Buscar por N° de pedido" value={filterOrder} onChange={(e) => setFilterOrder(e.target.value)} style={{ width: '100%', padding: '12px 15px 12px 45px', borderRadius: '8px', border: '1px solid #e2e8f0', outline: 'none', fontSize: '14px' }} />
                    </div>
                </div>

                {filtered.map(pedido => {
                    const primerItem = pedido.items && pedido.items.length > 0 ? pedido.items[0] : null;
                    const imagenUrl = primerItem?.variante?.producto?.imagenes?.[0]?.ruta || '/img/placeholder.jpg';
                    const vendedor = primerItem?.variante?.producto?.proveedor?.nombre || 'Tienda Principal';

                    return (
                        <div key={pedido.id} style={{ background: 'white', border: '1px solid #e2e8f0', borderRadius: '8px', marginBottom: '20px', overflow: 'hidden' }}>
                            <div style={{ padding: '15px 20px', background: '#f8fafc', borderBottom: '1px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', fontSize: '14px', color: '#333', fontWeight: '500' }}>
                                <span>{new Date(pedido.created_at).toLocaleDateString('es-PE', { day: 'numeric', month: 'long' })}</span>
                                <span>{formatCurrency(pedido.total)}</span>
                            </div>
                            <div style={{ padding: '25px 20px', display: 'flex', justifyContent: 'space-between' }}>
                                <div style={{ display: 'flex', gap: '20px' }}>
                                    <div>
                                        <div style={{ fontSize: '12px', color: '#64748b', marginBottom: '5px' }}>Compras N° {pedido.codigo}</div>
                                        <div style={{ fontSize: '14px', color: '#333', fontWeight: '500', marginBottom: '15px' }}>{pedido.estado === 'Completado' ? 'Entregado' : 'Estado: ' + pedido.estado} el {new Date(pedido.created_at).toLocaleDateString('es-PE', { weekday: 'long', day: '2-digit', month: 'long'})}.</div>
                                        <div style={{ display: 'flex', gap: '15px' }}>
                                            <div style={{ width: '60px', height: '60px', border: '1px solid #e2e8f0', borderRadius: '6px', padding: '5px' }}>
                                                <img src={imagenUrl} alt="Producto" style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
                                            </div>
                                            <div>
                                                <a href={`/seguimiento?codigo=${pedido.codigo}`} style={{ color: '#444', textDecoration: 'underline', fontSize: '13px' }}>Entregado</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div style={{ display: 'flex', flexDirection: 'column', justifyContent: 'center', width: '200px', padding: '0 20px', borderLeft: '1px solid #e2e8f0', borderRight: '1px solid #e2e8f0' }}>
                                    <div style={{ fontSize: '12px', color: '#64748b' }}>Vendido por:</div>
                                    <div style={{ fontSize: '13px', color: '#333' }}>{vendedor}</div>
                                </div>
                                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', justifyContent: 'center', width: '220px', paddingLeft: '20px' }}>
                                    <Link href={`/perfil/compras/${pedido.codigo}`} style={{ width: '100%', padding: '10px 0', background: '#00B4FF', color: 'white', borderRadius: '24px', textAlign: 'center', fontSize: '13px', fontWeight: '600', textDecoration: 'none' }}>Revisar detalle</Link>
                                    <button onClick={() => { router.post('/cart/add', { variante_id: primerItem?.variante_id, cantidad: 1 }, { preserveScroll: true }) }} style={{ width: '100%', padding: '10px 0', background: 'white', border: '1px solid #00B4FF', color: '#00B4FF', borderRadius: '24px', textAlign: 'center', fontSize: '13px', fontWeight: '600', cursor: 'pointer' }}>Comprar de nuevo</button>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    };

    const renderPerfilSidebar = () => {
        const getStyles = (view) => ({
            width: '100%', textAlign: 'left', padding: '15px 20px', background: currentView === view ? '#f0f9ff' : 'transparent', border: 'none', borderLeft: currentView === view ? '3px solid #00B4FF' : '3px solid transparent', fontSize: '14px', color: currentView === view ? '#00B4FF' : '#333', fontWeight: currentView === view ? '600' : '400', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '10px', transition: 'all 0.2s'
        });
        
        return (
            <div style={{ width: '260px', flexShrink: 0, background: 'white', borderRadius: '12px', padding: '10px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)' }}>
                <button onClick={() => changeView('perfil')} style={getStyles('perfil')}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Datos personales
                </button>
                <button onClick={() => changeView('direcciones')} style={getStyles('direcciones')}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Direcciones
                </button>
                <button onClick={() => changeView('tarjetas')} style={getStyles('tarjetas')}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    Tarjetas
                </button>
                <button onClick={() => changeView('reembolso')} style={getStyles('reembolso')}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    Reembolsos / CCI
                </button>
                <button onClick={() => changeView('listas')} style={getStyles('listas')}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                    Mis listas
                </button>
                
                <div style={{ height: '1px', background: '#f1f5f9', margin: '10px 0' }}></div>
                
                <button onClick={() => changeView('sesiones')} style={getStyles('sesiones')}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg>
                    Dispositivos vinculados
                </button>
                <button onClick={() => changeView('configuracion')} style={getStyles('configuracion')}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Configurar cuenta
                </button>
                <button onClick={() => { router.post('/logout') }} style={{ width: '100%', textAlign: 'left', padding: '15px 20px', background: 'transparent', border: 'none', borderLeft: '3px solid transparent', fontSize: '14px', color: '#e11d48', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '10px', transition: 'all 0.2s' }}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Cerrar sesión
                </button>
            </div>
        );
    };

    const renderDatosPersonales = () => (
        <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: '20px', color: '#333', fontWeight: '400', marginBottom: '25px' }}>Datos personales</h2>
            <div style={{ background: 'white', borderRadius: '12px', padding: '0 30px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)' }}>
                <div style={{ padding: '25px 0', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ visibility: 'hidden', height: 0 }}></div>
                </div>
                <div style={{ padding: '25px 0', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                        <div style={{ fontSize: '13px', color: '#94a3b8', marginBottom: '5px' }}>Nombre y apellidos</div>
                        <div style={{ fontSize: '15px', color: '#333' }}>{user.nombres} {user.apellidos}</div>
                    </div>
                    <button onClick={() => setShowEditProfile(true)} style={{ background: 'none', border: 'none', color: '#00B4FF', fontSize: '14px', fontWeight: '600', textDecoration: 'none', cursor: 'pointer' }}>Editar</button>
                </div>
                <div style={{ padding: '25px 0', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                        <div style={{ fontSize: '13px', color: '#94a3b8', marginBottom: '5px' }}>Tipo de documento</div>
                        <div style={{ fontSize: '15px', color: '#333' }}>{user.tipo_documento || 'DNI'} {user.dni}</div>
                    </div>
                </div>
                <div style={{ padding: '25px 0', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                        <div style={{ fontSize: '13px', color: '#94a3b8', marginBottom: '5px' }}>Celular</div>
                        <div style={{ fontSize: '15px', color: '#333' }}>+51 {user.telefono || '-'}</div>
                    </div>
                    <button onClick={() => setShowEditPhone(true)} style={{ background: 'none', border: 'none', color: '#00B4FF', fontSize: '14px', fontWeight: '600', textDecoration: 'none', cursor: 'pointer' }}>Editar</button>
                </div>
                <div style={{ padding: '25px 0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                        <div style={{ fontSize: '13px', color: '#94a3b8', marginBottom: '5px' }}>Correo</div>
                        <div style={{ fontSize: '15px', color: '#333', display: 'flex', alignItems: 'center', gap: '8px' }}>
                            {user.email}
                            <div style={{ position: 'relative', display: 'inline-flex', alignItems: 'center' }}>
                                <svg onMouseEnter={() => setShowTooltip(true)} onMouseLeave={() => setShowTooltip(false)} width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" strokeWidth="2" style={{ cursor: 'help' }}>
                                    <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                {showTooltip && (
                                    <div style={{ position: 'absolute', top: '100%', left: '50%', transform: 'translateX(-50%)', marginTop: '10px', background: '#333', color: 'white', padding: '12px 16px', borderRadius: '6px', fontSize: '13px', width: '250px', zIndex: 10, boxShadow: '0 4px 15px rgba(0,0,0,0.1)' }}>
                                        <div style={{ position: 'absolute', top: '-6px', left: '50%', transform: 'translateX(-50%)', borderLeft: '6px solid transparent', borderRight: '6px solid transparent', borderBottom: '6px solid #333' }}></div>
                                        Por tu seguridad, no es posible editar tu correo. Si necesitas usar otro, crea una nueva cuenta.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );

    const renderDirecciones = () => (
        <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: '20px', color: '#333', fontWeight: '400', marginBottom: '25px' }}>Direcciones</h2>
            {direcciones.map(dir => (
                <div key={dir.id} style={{ background: 'white', borderRadius: '12px', padding: '25px 30px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', marginBottom: '15px', display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '5px' }}>
                            <div style={{ fontSize: '14px', color: '#333', fontWeight: '500' }}>{dir.direccion}</div>
                            {dir.principal ? <span style={{ background: '#e0f2fe', color: '#0369a1', padding: '2px 8px', borderRadius: '4px', fontSize: '11px', fontWeight: 'bold' }}>Principal</span> : null}
                        </div>
                        <div style={{ fontSize: '13px', color: '#64748b' }}>{dir.distrito}, {dir.provincia}</div>
                        <div style={{ fontSize: '13px', color: '#64748b' }}>{dir.departamento}</div>
                        {dir.referencia && <div style={{ fontSize: '13px', color: '#64748b', marginTop: '5px' }}>Ref: {dir.referencia}</div>}
                    </div>
                    <div style={{ display: 'flex', gap: '15px' }}>
                        {!dir.principal && <button onClick={() => router.post(`/perfil/direccion/${dir.id}/principal`, {}, { preserveScroll: true })} style={{ background: 'none', border: 'none', color: '#00B4FF', fontSize: '12px', fontWeight: '600', cursor: 'pointer' }}>Establecer principal</button>}
                        <button onClick={() => {if(confirm("¿Eliminar dirección?")) router.delete(`/perfil/direccion/${dir.id}`, { preserveScroll: true })}} style={{ background: 'none', border: 'none', color: '#e11d48', fontSize: '12px', textDecoration: 'underline', cursor: 'pointer' }}>Eliminar</button>
                    </div>
                </div>
            ))}
            {direcciones.length === 0 && <div style={{ padding: '40px', background: 'white', borderRadius: '12px', textAlign: 'center', color: '#666', marginBottom: '15px' }}>No tienes direcciones.</div>}
            <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '20px' }}>
                <button onClick={() => setShowAddAddress(true)} style={{ padding: '12px 25px', background: '#00B4FF', color: 'white', borderRadius: '24px', fontSize: '14px', fontWeight: '600', border: 'none', cursor: 'pointer' }}>Agregar dirección</button>
            </div>
        </div>
    );

    const renderTarjetas = () => (
        <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: '20px', color: '#333', fontWeight: '400', marginBottom: '25px' }}>Tarjetas</h2>
            {tarjetas.map(t => (
                <div key={t.id} style={{ background: 'white', borderRadius: '12px', padding: '25px 30px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', marginBottom: '15px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div style={{ display: 'flex', gap: '20px', alignItems: 'center' }}>
                        <div style={{ width: '50px', height: '35px', background: '#f1f5f9', borderRadius: '6px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 'bold', fontSize: '12px', color: '#00B4FF' }}>{t.marca}</div>
                        <div>
                            <div style={{ fontSize: '14px', color: '#333', fontWeight: '500' }}>**** **** **** {t.ultimos_digitos}</div>
                            {t.principal ? <span style={{ background: '#e0f2fe', color: '#0369a1', padding: '2px 8px', borderRadius: '4px', fontSize: '11px', fontWeight: 'bold' }}>Principal</span> : null}
                        </div>
                    </div>
                    <button onClick={() => {if(confirm("¿Eliminar tarjeta?")) router.delete(`/perfil/tarjetas/${t.id}`, { preserveScroll: true })}} style={{ background: 'none', border: 'none', color: '#e11d48', fontSize: '12px', textDecoration: 'underline', cursor: 'pointer' }}>Eliminar</button>
                </div>
            ))}
            {tarjetas.length === 0 && <div style={{ padding: '40px', background: 'white', borderRadius: '12px', textAlign: 'center', color: '#666', marginBottom: '15px' }}>No tienes tarjetas guardadas.</div>}
            <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '20px' }}>
                <button onClick={() => setShowAddTarjeta(true)} style={{ padding: '12px 25px', background: '#00B4FF', color: 'white', borderRadius: '24px', fontSize: '14px', fontWeight: '600', border: 'none', cursor: 'pointer' }}>Agregar tarjeta</button>
            </div>
        </div>
    );

    const renderReembolsos = () => (
        <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: '20px', color: '#333', fontWeight: '400', marginBottom: '25px' }}>Datos de Reembolso / CCI</h2>
            <div style={{ background: 'white', borderRadius: '12px', padding: '30px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)' }}>
                <p style={{ fontSize: '13px', color: '#666', marginBottom: '25px', lineHeight: '1.5' }}>Completa los datos de la cuenta bancaria donde deseas recibir tus reembolsos en caso de cancelaciones o devoluciones.</p>
                <form onSubmit={submitReembolso} style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Tipo Documento</label>
                            <select value={reembolsoForm.data.tipo_documento} onChange={e => reembolsoForm.setData('tipo_documento', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }}>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">Carnet de Extranjería</option>
                            </select>
                        </div>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Número Documento</label>
                            <input type="text" value={reembolsoForm.data.numero_documento} onChange={e => reembolsoForm.setData('numero_documento', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required />
                        </div>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Nombres Titular</label>
                            <input type="text" value={reembolsoForm.data.nombres_titular} onChange={e => reembolsoForm.setData('nombres_titular', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required />
                        </div>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Apellidos Titular</label>
                            <input type="text" value={reembolsoForm.data.apellidos_titular} onChange={e => reembolsoForm.setData('apellidos_titular', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required />
                        </div>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Banco</label>
                            <select value={reembolsoForm.data.banco} onChange={e => reembolsoForm.setData('banco', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required>
                                <option value="">Seleccione banco</option>
                                <option value="BCP">BCP</option>
                                <option value="BBVA">BBVA</option>
                                <option value="Interbank">Interbank</option>
                                <option value="Scotiabank">Scotiabank</option>
                                <option value="Banbif">Banbif</option>
                                <option value="Banco de la Nación">Banco de la Nación</option>
                            </select>
                        </div>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Tipo de Cuenta</label>
                            <select value={reembolsoForm.data.tipo_cuenta} onChange={e => reembolsoForm.setData('tipo_cuenta', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required>
                                <option value="">Seleccione tipo</option>
                                <option value="Ahorros">Ahorros</option>
                                <option value="Corriente">Corriente</option>
                            </select>
                        </div>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Número de Cuenta</label>
                            <input type="text" value={reembolsoForm.data.numero_cuenta} onChange={e => reembolsoForm.setData('numero_cuenta', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required />
                        </div>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>CCI (Código de Cuenta Interbancario)</label>
                            <input type="text" value={reembolsoForm.data.cci} onChange={e => reembolsoForm.setData('cci', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required />
                        </div>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Celular Titular</label>
                            <input type="text" value={reembolsoForm.data.telefono_titular} onChange={e => reembolsoForm.setData('telefono_titular', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required />
                        </div>
                        <div>
                            <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Correo Titular</label>
                            <input type="email" value={reembolsoForm.data.correo_titular} onChange={e => reembolsoForm.setData('correo_titular', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none', fontSize: '14px' }} required />
                        </div>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'flex-end', marginTop: '10px' }}>
                        <button type="submit" disabled={reembolsoForm.processing} style={{ padding: '12px 30px', background: '#00B4FF', border: 'none', borderRadius: '24px', color: 'white', fontWeight: '600', cursor: 'pointer' }}>{reembolsoForm.processing ? 'Guardando...' : 'Guardar Datos'}</button>
                    </div>
                </form>
            </div>
        </div>
    );

    const renderListas = () => (
        <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: '20px', color: '#333', fontWeight: '400', marginBottom: '25px' }}>Mis listas</h2>
            
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: '20px' }}>
                {listas.map(lista => (
                    <div key={lista.id} style={{ background: 'white', borderRadius: '12px', padding: '20px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', display: 'flex', flexDirection: 'column', gap: '10px' }}>
                        <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gridTemplateRows: '1fr 1fr', gap: '5px', height: '140px' }}>
                            <div style={{ gridRow: 'span 2', background: '#f1f5f9', borderRadius: '8px', overflow: 'hidden' }}>
                                {lista.items?.[0] && <img src={lista.items[0].producto?.imagenes?.[0]?.ruta} style={{width:'100%', height:'100%', objectFit:'cover'}} />}
                            </div>
                            <div style={{ background: '#f1f5f9', borderRadius: '8px', overflow: 'hidden' }}>
                                {lista.items?.[1] && <img src={lista.items[1].producto?.imagenes?.[0]?.ruta} style={{width:'100%', height:'100%', objectFit:'cover'}} />}
                            </div>
                            <div style={{ background: '#f1f5f9', borderRadius: '8px', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#94a3b8', fontSize: '18px', fontWeight: 'bold' }}>
                                +{(lista.items?.length || 0) > 2 ? lista.items.length - 2 : 0}
                            </div>
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '5px' }}>
                            <div style={{ fontSize: '15px', color: '#333', fontWeight: '600' }}>{lista.nombre}</div>
                            <button onClick={() => {if(confirm("¿Eliminar lista?")) router.delete(`/perfil/listas/${lista.id}`, { preserveScroll: true })}} style={{ background: 'none', border: 'none', color: '#e11d48', cursor: 'pointer' }}><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                        </div>
                        <div style={{ fontSize: '13px', color: '#64748b' }}>{(lista.items?.length || 0)} productos • {lista.es_publica ? 'Pública' : 'Privada'}</div>
                    </div>
                ))}
                
                <div onClick={() => setShowAddLista(true)} style={{ background: 'rgba(0, 180, 255, 0.05)', border: '2px dashed #00B4FF', borderRadius: '12px', padding: '20px', minHeight: '220px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', transition: 'all 0.2s' }}>
                    <div style={{ width: '48px', height: '48px', background: '#00B4FF', color: 'white', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '24px', marginBottom: '15px' }}>+</div>
                    <div style={{ fontSize: '15px', color: '#00B4FF', fontWeight: '600' }}>Crear nueva lista</div>
                </div>
            </div>
        </div>
    );

    const renderSesiones = () => (
        <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: '20px', color: '#333', fontWeight: '400', marginBottom: '25px' }}>Dispositivos vinculados</h2>
            <p style={{ fontSize: '13px', color: '#666', marginBottom: '25px', lineHeight: '1.5' }}>Aquí verás los dispositivos donde has iniciado sesión recientemente.</p>
            
            {sesiones.map(sesion => {
                const isCurrent = sesion.id === usePage().props.session_id; // Need to check how laravel passes current session id
                return (
                    <div key={sesion.id} style={{ background: 'white', borderRadius: '12px', padding: '20px 25px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', marginBottom: '15px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <div style={{ display: 'flex', gap: '15px', alignItems: 'center' }}>
                            <div style={{ width: '40px', height: '40px', background: '#f1f5f9', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#64748b' }}>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect></svg>
                            </div>
                            <div>
                                <div style={{ fontSize: '14px', color: '#333', fontWeight: '600', display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    {sesion.user_agent.substring(0, 40)}...
                                </div>
                                <div style={{ fontSize: '12px', color: '#64748b', marginTop: '3px' }}>
                                    IP: {sesion.ip_address} • Última actividad: {new Date(sesion.last_activity * 1000).toLocaleString()}
                                </div>
                            </div>
                        </div>
                        <button onClick={() => {if(confirm("¿Cerrar sesión en este dispositivo?")) router.delete(`/perfil/sesiones/${sesion.id}`, { preserveScroll: true })}} style={{ background: 'none', border: 'none', color: '#e11d48', fontSize: '13px', fontWeight: '500', cursor: 'pointer' }}>Cerrar sesión</button>
                    </div>
                );
            })}
        </div>
    );

    const renderConfiguracion = () => (
        <div style={{ flex: 1 }}>
            <h2 style={{ fontSize: '20px', color: '#333', fontWeight: '400', marginBottom: '25px' }}>Configurar cuenta</h2>
            
            <div style={{ background: 'white', borderRadius: '12px', padding: '0 30px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)', marginBottom: '20px' }}>
                <div style={{ padding: '25px 0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                        <div style={{ fontSize: '15px', color: '#333', fontWeight: '500', marginBottom: '5px' }}>Contraseña de acceso</div>
                        <div style={{ fontSize: '13px', color: '#64748b' }}>Actualiza tu contraseña para mantener tu cuenta segura.</div>
                    </div>
                    <button onClick={() => setShowEditPassword(true)} style={{ padding: '10px 20px', background: 'white', border: '1px solid #cbd5e1', borderRadius: '24px', color: '#333', fontSize: '13px', fontWeight: '600', cursor: 'pointer' }}>Cambiar contraseña</button>
                </div>
            </div>

            <div style={{ background: '#fff1f2', borderRadius: '12px', padding: '0 30px', border: '1px solid #fecdd3' }}>
                <div style={{ padding: '25px 0', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                        <div style={{ fontSize: '15px', color: '#e11d48', fontWeight: '600', marginBottom: '5px' }}>Eliminar cuenta</div>
                        <div style={{ fontSize: '13px', color: '#9f1239' }}>Esta acción es permanente y eliminará todos tus datos, listas y preferencias.</div>
                    </div>
                    <button onClick={() => setShowDeleteAccount(true)} style={{ padding: '10px 20px', background: '#e11d48', border: 'none', borderRadius: '24px', color: 'white', fontSize: '13px', fontWeight: '600', cursor: 'pointer' }}>Eliminar mi cuenta</button>
                </div>
            </div>
        </div>
    );

    return (
        <div className="efe-home" style={{ background: '#f8fafc', minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
            <Head title="Mi Cuenta" />
            <Header cartCount={0} onOpenCart={() => {}} onOpenCategories={() => {}} logoUrl={null} minimal={true} />
            
            {currentView === 'home' && renderHome()}
            {currentView === 'compras' && renderCompras()}
            
            {['perfil', 'direcciones', 'tarjetas', 'reembolso', 'listas', 'sesiones', 'configuracion'].includes(currentView) && (
                <div style={{ maxWidth: '1000px', margin: '40px auto', padding: '0 20px', width: '100%' }}>
                    <div onClick={() => changeView('home')} style={{ color: '#444', fontSize: '13px', fontWeight: '500', marginBottom: '10px', cursor: 'pointer', display: 'flex', alignItems: 'center', gap: '5px' }}>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="15 18 9 12 15 6"></polyline></svg> Mi cuenta
                    </div>
                    
                    <div style={{ display: 'flex', gap: '40px', marginTop: '20px', flexDirection: 'row', alignItems: 'flex-start' }}>
                        {renderPerfilSidebar()}
                        {currentView === 'perfil' && renderDatosPersonales()}
                        {currentView === 'direcciones' && renderDirecciones()}
                        {currentView === 'tarjetas' && renderTarjetas()}
                        {currentView === 'reembolso' && renderReembolsos()}
                        {currentView === 'listas' && renderListas()}
                        {currentView === 'sesiones' && renderSesiones()}
                        {currentView === 'configuracion' && renderConfiguracion()}
                    </div>
                </div>
            )}

            {/* Modal Editar Perfil */}
            {showEditProfile && (
                <>
                    <div onClick={() => setShowEditProfile(false)} style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(2px)', transition: 'all 0.3s' }}></div>
                    <div style={{ position: 'fixed', top: 0, right: 0, bottom: 0, width: '100%', maxWidth: '400px', background: 'white', zIndex: 100, boxShadow: '-5px 0 25px rgba(0,0,0,0.1)', padding: '30px', overflowY: 'auto', display: 'flex', flexDirection: 'column' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#333', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00B4FF" strokeWidth="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                Editar datos personales
                            </h3>
                            <button onClick={() => setShowEditProfile(false)} style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer', color: '#999' }}>✕</button>
                        </div>
                        <p style={{ fontSize: '13px', color: '#666', marginBottom: '25px', lineHeight: '1.5' }}>Usaremos estos datos en tus compras y en las comunicaciones que te enviemos.</p>
                        
                        <form onSubmit={submitProfile} style={{ display: 'flex', flexDirection: 'column', gap: '20px', flex: 1 }}>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Nombres</label>
                                <input type="text" value={profileForm.data.nombres} onChange={e => profileForm.setData('nombres', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333' }} required />
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Apellidos</label>
                                <input type="text" value={profileForm.data.apellidos} onChange={e => profileForm.setData('apellidos', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333' }} required />
                            </div>
                            <div style={{ marginTop: 'auto', paddingTop: '20px', display: 'flex', gap: '15px', justifyContent: 'flex-end' }}>
                                <button type="button" onClick={() => setShowEditProfile(false)} style={{ padding: '12px 24px', background: 'white', border: 'none', color: '#333', fontWeight: '600', cursor: 'pointer' }}>Cancelar</button>
                                <button type="submit" disabled={profileForm.processing} style={{ padding: '12px 30px', background: '#00B4FF', border: 'none', borderRadius: '24px', color: 'white', fontWeight: '600', cursor: 'pointer', boxShadow: '0 4px 10px rgba(0, 180, 255, 0.2)' }}>{profileForm.processing ? 'Guardando...' : 'Guardar'}</button>
                            </div>
                        </form>
                    </div>
                </>
            )}

            {/* Modal Editar Celular */}
            {showEditPhone && (
                <>
                    <div onClick={() => setShowEditPhone(false)} style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(2px)', transition: 'all 0.3s' }}></div>
                    <div style={{ position: 'fixed', top: 0, right: 0, bottom: 0, width: '100%', maxWidth: '400px', background: 'white', zIndex: 100, boxShadow: '-5px 0 25px rgba(0,0,0,0.1)', padding: '30px', overflowY: 'auto', display: 'flex', flexDirection: 'column' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#333', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#333" strokeWidth="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                Editar celular
                            </h3>
                            <button onClick={() => setShowEditPhone(false)} style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer', color: '#999' }}>✕</button>
                        </div>
                        
                        {phoneOtpStep === 'phone' ? (
                            <form onSubmit={submitPhoneRequest} style={{ display: 'flex', flexDirection: 'column', gap: '20px', flex: 1 }}>
                                <p style={{ fontSize: '13px', color: '#333', marginBottom: '10px', lineHeight: '1.5' }}>
                                    Necesitamos validar tu identidad. Al continuar, <strong>enviaremos un código verificador al correo {user.email.substring(0, 2)}********@{user.email.split('@')[1]}.</strong>
                                </p>
                                
                                <div>
                                    <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Celular</label>
                                    <div style={{ display: 'flex', alignItems: 'flex-end', borderBottom: '1px solid #cbd5e1' }}>
                                        <span style={{ padding: '10px 10px 10px 0', fontSize: '15px', color: '#333' }}>+51</span>
                                        <input type="text" value={phoneForm.data.telefono} onChange={e => phoneForm.setData('telefono', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', outline: 'none', fontSize: '15px', color: '#333' }} placeholder="Ingresa un celular" required />
                                    </div>
                                    {phoneForm.errors.telefono && <div style={{ color: '#e11d48', fontSize: '12px', marginTop: '5px' }}>{phoneForm.errors.telefono}</div>}
                                </div>
                                
                                <div style={{ marginTop: 'auto', paddingTop: '20px', display: 'flex', gap: '15px', justifyContent: 'flex-end' }}>
                                    <button type="button" onClick={() => setShowEditPhone(false)} style={{ padding: '12px 24px', background: 'white', border: 'none', color: '#333', fontWeight: '600', cursor: 'pointer' }}>Cancelar</button>
                                    <button type="submit" disabled={phoneForm.processing} style={{ padding: '12px 30px', background: '#334155', border: 'none', borderRadius: '24px', color: 'white', fontWeight: '600', cursor: 'pointer' }}>{phoneForm.processing ? 'Enviando...' : 'Continuar'}</button>
                                </div>
                            </form>
                        ) : (
                            <form onSubmit={submitPhoneVerify} style={{ display: 'flex', flexDirection: 'column', gap: '20px', flex: 1 }}>
                                <p style={{ fontSize: '13px', color: '#333', marginBottom: '10px', lineHeight: '1.5' }}>
                                    Ingresa el código de 6 dígitos que enviamos a tu correo.
                                </p>
                                
                                <div>
                                    <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Código de verificación</label>
                                    <input type="text" maxLength="6" value={phoneForm.data.codigo} onChange={e => phoneForm.setData('codigo', e.target.value.replace(/\D/g, ''))} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '24px', letterSpacing: '10px', textAlign: 'center', color: '#333' }} placeholder="000000" required />
                                    {phoneForm.errors.codigo && <div style={{ color: '#e11d48', fontSize: '12px', marginTop: '5px' }}>{phoneForm.errors.codigo}</div>}
                                </div>
                                
                                <div style={{ marginTop: 'auto', paddingTop: '20px', display: 'flex', gap: '15px', justifyContent: 'flex-end' }}>
                                    <button type="button" onClick={() => setPhoneOtpStep('phone')} style={{ padding: '12px 24px', background: 'white', border: 'none', color: '#333', fontWeight: '600', cursor: 'pointer' }}>Volver</button>
                                    <button type="submit" disabled={phoneForm.processing} style={{ padding: '12px 30px', background: '#00B4FF', border: 'none', borderRadius: '24px', color: 'white', fontWeight: '600', cursor: 'pointer', boxShadow: '0 4px 10px rgba(0, 180, 255, 0.2)' }}>{phoneForm.processing ? 'Validando...' : 'Verificar'}</button>
                                </div>
                            </form>
                        )}
                    </div>
                </>
            )}

            {/* Modal Agregar Dirección */}
            {showAddAddress && (
                <>
                    <div onClick={() => setShowAddAddress(false)} style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(2px)', transition: 'all 0.3s' }}></div>
                    <div style={{ position: 'fixed', top: 0, right: 0, bottom: 0, width: '100%', maxWidth: '400px', background: 'white', zIndex: 100, boxShadow: '-5px 0 25px rgba(0,0,0,0.1)', padding: '30px', overflowY: 'auto', display: 'flex', flexDirection: 'column' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#333', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00B4FF" strokeWidth="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                Agregar dirección
                            </h3>
                            <button onClick={() => setShowAddAddress(false)} style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer', color: '#999' }}>✕</button>
                        </div>
                        
                        <form onSubmit={submitAddress} style={{ display: 'flex', flexDirection: 'column', gap: '20px', flex: 1 }}>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Departamento</label>
                                <select value={addressForm.data.departamento} onChange={handleDepChange} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333', backgroundColor: 'transparent' }} required>
                                    <option value="" disabled>Selecciona una opción</option>
                                    {departamentos.map(d => <option key={d.departamento} value={d.nombre}>{d.nombre}</option>)}
                                </select>
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Provincia</label>
                                <select value={addressForm.data.provincia} onChange={handleProvChange} disabled={!depCode} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333', backgroundColor: 'transparent' }} required>
                                    <option value="" disabled>Selecciona una opción</option>
                                    {provincias.map(p => <option key={p.provincia} value={p.nombre}>{p.nombre}</option>)}
                                </select>
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Distrito</label>
                                <select value={addressForm.data.distrito} onChange={e => addressForm.setData('distrito', e.target.value)} disabled={!provCode} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333', backgroundColor: 'transparent' }} required>
                                    <option value="" disabled>Selecciona una opción</option>
                                    {distritos.map(d => <option key={d.distrito} value={d.nombre}>{d.nombre}</option>)}
                                </select>
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Dirección exacta</label>
                                <input type="text" value={addressForm.data.direccion} onChange={e => addressForm.setData('direccion', e.target.value)} placeholder="Ingresa calle y número / Mz / Lote" style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333' }} required />
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Referencia (Opcional)</label>
                                <input type="text" value={addressForm.data.referencia} onChange={e => addressForm.setData('referencia', e.target.value)} placeholder="Ej: Frente al parque" style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333' }} />
                            </div>

                            <label style={{ display: 'flex', alignItems: 'center', gap: '10px', marginTop: '10px', cursor: 'pointer' }}>
                                <input type="checkbox" checked={addressForm.data.principal} onChange={e => addressForm.setData('principal', e.target.checked)} style={{ width: '16px', height: '16px', accentColor: '#00B4FF' }} />
                                <span style={{ fontSize: '14px', color: '#333' }}>Guardar como dirección principal.</span>
                            </label>

                            <div style={{ marginTop: 'auto', paddingTop: '20px', display: 'flex', gap: '15px', justifyContent: 'flex-end' }}>
                                <button type="button" onClick={() => setShowAddAddress(false)} style={{ padding: '12px 24px', background: 'white', border: 'none', color: '#333', fontWeight: '600', cursor: 'pointer' }}>Cancelar</button>
                                <button type="submit" disabled={addressForm.processing} style={{ padding: '12px 30px', background: '#00B4FF', border: 'none', borderRadius: '24px', color: 'white', fontWeight: '600', cursor: 'pointer', boxShadow: '0 4px 10px rgba(0, 180, 255, 0.2)' }}>{addressForm.processing ? 'Guardando...' : 'Continuar'}</button>
                            </div>
                        </form>
                    </div>
                </>
            )}

            {/* Modal Editar Contraseña */}
            {showEditPassword && (
                <>
                    <div onClick={() => setShowEditPassword(false)} style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(2px)', transition: 'all 0.3s' }}></div>
                    <div style={{ position: 'fixed', top: 0, right: 0, bottom: 0, width: '100%', maxWidth: '400px', background: 'white', zIndex: 100, boxShadow: '-5px 0 25px rgba(0,0,0,0.1)', padding: '30px', overflowY: 'auto', display: 'flex', flexDirection: 'column' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#333', display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00B4FF" strokeWidth="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Cambiar Contraseña
                            </h3>
                            <button onClick={() => setShowEditPassword(false)} style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer', color: '#999' }}>✕</button>
                        </div>
                        
                        <form onSubmit={submitPassword} style={{ display: 'flex', flexDirection: 'column', gap: '25px', flex: 1 }}>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Contraseña actual</label>
                                <input type="password" value={passwordForm.data.current_password} onChange={e => passwordForm.setData('current_password', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333' }} required />
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Nueva contraseña</label>
                                <input type="password" value={passwordForm.data.password} onChange={e => passwordForm.setData('password', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333' }} required />
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Confirmar nueva contraseña</label>
                                <input type="password" value={passwordForm.data.password_confirmation} onChange={e => passwordForm.setData('password_confirmation', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none', fontSize: '15px', color: '#333' }} required />
                            </div>

                            <div style={{ marginTop: 'auto', paddingTop: '20px', display: 'flex', gap: '15px', justifyContent: 'flex-end' }}>
                                <button type="button" onClick={() => setShowEditPassword(false)} style={{ padding: '12px 24px', background: 'white', border: 'none', color: '#333', fontWeight: '600', cursor: 'pointer' }}>Cancelar</button>
                                <button type="submit" disabled={passwordForm.processing} style={{ padding: '12px 30px', background: '#00B4FF', border: 'none', borderRadius: '24px', color: 'white', fontWeight: '600', cursor: 'pointer', boxShadow: '0 4px 10px rgba(0, 180, 255, 0.2)' }}>{passwordForm.processing ? 'Actualizando...' : 'Actualizar'}</button>
                            </div>
                        </form>
                    </div>
                </>
            )}

            {/* Modal Agregar Tarjeta */}
            {showAddTarjeta && (
                <>
                    <div onClick={() => setShowAddTarjeta(false)} style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(2px)' }}></div>
                    <div style={{ position: 'fixed', top: 0, right: 0, bottom: 0, width: '100%', maxWidth: '400px', background: 'white', zIndex: 100, boxShadow: '-5px 0 25px rgba(0,0,0,0.1)', padding: '30px', overflowY: 'auto', display: 'flex', flexDirection: 'column' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#333' }}>Agregar tarjeta</h3>
                            <button onClick={() => setShowAddTarjeta(false)} style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer' }}>✕</button>
                        </div>
                        <form onSubmit={submitTarjeta} style={{ display: 'flex', flexDirection: 'column', gap: '20px', flex: 1 }}>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Número de tarjeta</label>
                                <input type="text" maxLength="16" value={tarjetaForm.data.numero_tarjeta} onChange={e => tarjetaForm.setData('numero_tarjeta', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none' }} required />
                            </div>
                            <div style={{ display: 'flex', gap: '20px' }}>
                                <div>
                                    <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Vencimiento (MM/AA)</label>
                                    <input type="text" value={tarjetaForm.data.fecha_vencimiento} onChange={e => tarjetaForm.setData('fecha_vencimiento', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none' }} required />
                                </div>
                                <div>
                                    <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>CVV</label>
                                    <input type="text" maxLength="4" value={tarjetaForm.data.cvv} onChange={e => tarjetaForm.setData('cvv', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none' }} required />
                                </div>
                            </div>
                            <div>
                                <label style={{ fontSize: '12px', fontWeight: '600', color: '#94a3b8', display: 'block', marginBottom: '5px' }}>Nombre del titular</label>
                                <input type="text" value={tarjetaForm.data.nombre_titular} onChange={e => tarjetaForm.setData('nombre_titular', e.target.value)} style={{ width: '100%', padding: '10px 0', border: 'none', borderBottom: '1px solid #cbd5e1', outline: 'none' }} required />
                            </div>
                            <div style={{ marginTop: 'auto', paddingTop: '20px', display: 'flex', gap: '15px', justifyContent: 'flex-end' }}>
                                <button type="submit" disabled={tarjetaForm.processing} style={{ padding: '12px 30px', background: '#00B4FF', border: 'none', borderRadius: '24px', color: 'white', fontWeight: '600' }}>Guardar</button>
                            </div>
                        </form>
                    </div>
                </>
            )}

            {/* Modal Agregar Lista */}
            {showAddLista && (
                <>
                    <div onClick={() => setShowAddLista(false)} style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(2px)' }}></div>
                    <div style={{ position: 'fixed', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: '100%', maxWidth: '400px', background: 'white', zIndex: 100, borderRadius: '12px', padding: '30px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                            <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#333' }}>Nueva Lista</h3>
                            <button onClick={() => setShowAddLista(false)} style={{ background: 'none', border: 'none', fontSize: '20px', cursor: 'pointer' }}>✕</button>
                        </div>
                        <form onSubmit={submitLista} style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
                            <div>
                                <label style={{ fontSize: '13px', fontWeight: '600', color: '#64748b', display: 'block', marginBottom: '5px' }}>Nombre de la lista</label>
                                <input type="text" value={listaForm.data.nombre} onChange={e => listaForm.setData('nombre', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none' }} required placeholder="Ej: Favoritos, Para Navidad" />
                            </div>
                            <label style={{ display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer' }}>
                                <input type="checkbox" checked={listaForm.data.es_publica} onChange={e => listaForm.setData('es_publica', e.target.checked)} style={{ accentColor: '#00B4FF' }} />
                                <span style={{ fontSize: '14px', color: '#333' }}>Hacer lista pública</span>
                            </label>
                            <button type="submit" disabled={listaForm.processing} style={{ padding: '12px', background: '#00B4FF', border: 'none', borderRadius: '8px', color: 'white', fontWeight: '600', width: '100%' }}>Crear lista</button>
                        </form>
                    </div>
                </>
            )}

            {/* Modal Eliminar Cuenta */}
            {showDeleteAccount && (
                <>
                    <div onClick={() => setShowDeleteAccount(false)} style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, zIndex: 99, background: 'rgba(0,0,0,0.4)', backdropFilter: 'blur(2px)' }}></div>
                    <div style={{ position: 'fixed', top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: '100%', maxWidth: '400px', background: 'white', zIndex: 100, borderRadius: '12px', padding: '30px' }}>
                        <h3 style={{ fontSize: '18px', fontWeight: 'bold', color: '#e11d48', marginBottom: '15px' }}>Eliminar Cuenta</h3>
                        <p style={{ fontSize: '14px', color: '#666', marginBottom: '20px' }}>Por favor ingresa tu contraseña para confirmar que deseas eliminar tu cuenta permanentemente.</p>
                        <form onSubmit={submitDeleteAccount} style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
                            <div>
                                <input type="password" value={deleteAccountForm.data.password} onChange={e => deleteAccountForm.setData('password', e.target.value)} style={{ width: '100%', padding: '10px', border: '1px solid #cbd5e1', borderRadius: '6px', outline: 'none' }} required placeholder="Tu contraseña" />
                            </div>
                            <div style={{ display: 'flex', gap: '10px' }}>
                                <button type="button" onClick={() => setShowDeleteAccount(false)} style={{ flex: 1, padding: '10px', background: '#f1f5f9', border: 'none', borderRadius: '8px', color: '#333', fontWeight: '600' }}>Cancelar</button>
                                <button type="submit" disabled={deleteAccountForm.processing} style={{ flex: 1, padding: '10px', background: '#e11d48', border: 'none', borderRadius: '8px', color: 'white', fontWeight: '600' }}>Eliminar</button>
                            </div>
                        </form>
                    </div>
                </>
            )}

            <Footer />
        </div>
    );
}
