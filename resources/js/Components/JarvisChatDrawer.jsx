import { useState, useEffect, useRef } from 'react';
import jarvisVoice from '../lib/jarvisVoice';
import axios from 'axios';
import '../../css/jarvis.css';

export default function JarvisChatDrawer({ mode = 'public' }) {
  const [open, setOpen] = useState(false);
  const [msg, setMsg] = useState('');
  const [history, setHistory] = useState([
    { from: 'jarvis', text: '¡Hola! Soy Jarvis, asistente de Inteligencia Artificial de Novape. ¿En qué te ayudo?' }
  ]);
  const [listening, setListening] = useState(false);
  const [continuousMode, setContinuousMode] = useState(false);
  const historyRef = useRef(null);

  useEffect(() => {
    if (historyRef.current) {
      historyRef.current.scrollTop = historyRef.current.scrollHeight;
    }
  }, [history]);

  const send = async (textToSend) => {
    const text = textToSend || msg;
    if (!text.trim()) return;
    
    setHistory(prev => [...prev, { from: 'user', text }]);
    setMsg('');

    try {
      const endpoint = mode === 'admin' ? '/api/jarvis/admin/message' : '/api/jarvis/public/message';
      const res = await axios.post(endpoint, { message: text });
      
      setHistory(prev => [...prev, { from: 'jarvis', text: res.data.voice, ui: res.data.ui }]);
      
      jarvisVoice.speak(res.data.voice, () => {
         if (continuousMode) {
             setTimeout(startListening, 500);
         }
      });
    } catch (e) {
      setHistory(prev => [...prev, { from: 'jarvis', text: 'Ocurrió un error al conectar con mis sistemas centrales.' }]);
      if (continuousMode) setTimeout(startListening, 1000);
    }
  };

  const startListening = () => {
    if (listening) return;
    setListening(true);
    jarvisVoice.listen(text => {
      setListening(false);
      if(text) {
          send(text);
      } else {
          if (continuousMode) setTimeout(startListening, 500);
      }
    }, () => {
        setListening(false);
        if (continuousMode && !msg) setTimeout(startListening, 500);
    });
  };

  const toggleContinuous = () => {
      if (!continuousMode) {
          setContinuousMode(true);
          startListening();
      } else {
          setContinuousMode(false);
          setListening(false);
      }
  };

  return (
    <>
      {/* Floating Toggle Button */}
      <button 
        onClick={() => setOpen(!open)} 
        className={`jarvis-chat-toggle ${open ? 'hidden' : ''}`}
        title="Hablar con Jarvis"
      >
        <span className="icon">🤖</span>
      </button>

      {/* Drawer */}
      <div className={`jarvis-drawer ${open ? 'open' : ''}`}>
        <div className="jarvis-header">
          <div className="title">
            <span className="orb-small"></span> JARVIS {mode === 'admin' ? 'ADMIN' : 'AI'}
          </div>
          <div className="flex gap-2 items-center">
             <button onClick={toggleContinuous} className={`text-xs px-2 py-1 rounded ${continuousMode ? 'bg-red-500 text-white' : 'bg-gray-700 text-gray-300'}`} title="Modo Conversación Continua">
                {continuousMode ? 'Parar Mic' : 'Modo Continuo'}
             </button>
             <button onClick={() => setOpen(false)} className="close-btn">×</button>
          </div>
        </div>

        <div className="jarvis-history" ref={historyRef}>
          {history.map((h, i) => (
            <div key={i} className={`message-row ${h.from}`}>
              <div className="bubble">
                {h.text}
              </div>
              {/* Render UI elements if any */}
              {h.ui && h.ui.type === 'product_list' && (
                 <div className="ui-cards-container">
                   {h.ui.items.map((item, j) => (
                     <div key={j} className="ui-card">
                       {item.image && <img src={item.image} alt={item.title} />}
                       <h4>{item.title}</h4>
                       <p className="price">{item.subtitle}</p>
                       {item.action?.type === 'view_product' && (
                         <a href={`/producto/${item.action.slug}`} className="ui-btn">Ver Detalle</a>
                       )}
                     </div>
                   ))}
                 </div>
              )}
            </div>
          ))}
          {listening && (
            <div className="message-row jarvis">
              <div className="bubble listening-indicator">
                <span className="dot"></span><span className="dot"></span><span className="dot"></span>
              </div>
            </div>
          )}
        </div>

        <div className="jarvis-input-area">
          <input 
            type="text" 
            value={msg} 
            onChange={e => setMsg(e.target.value)} 
            onKeyDown={e => e.key === 'Enter' && send()}
            placeholder="Escribe o habla..."
            disabled={listening}
          />
          <button onClick={() => send()} className="send-btn" disabled={!msg.trim()}>➤</button>
          <button onClick={startListening} className={`mic-btn ${listening ? 'active' : ''}`} title="Hablar">🎤</button>
        </div>
      </div>
    </>
  );
}
