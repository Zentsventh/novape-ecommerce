import { useState, useEffect } from 'react';
import jarvisVoice from '../lib/jarvisVoice';
import axios from 'axios';
import '../../css/jarvis.css';

export default function JarvisHUD() {
  const [active, setActive] = useState(false);
  const [listening, setListening] = useState(false);
  const [continuousMode, setContinuousMode] = useState(false);
  const [responseUi, setResponseUi] = useState(null);

  useEffect(() => {
    if (active && window.Echo) {
      window.Echo.private('admin.jarvis')
        .listen('.JarvisAlert', e => {
          jarvisVoice.speak(e.message);
          // Opcional: mostrar un toast o alert
        });
    }
  }, [active]);

  const startListening = () => {
    if (listening) return;
    setListening(true);
    jarvisVoice.listen(async (text) => {
      setListening(false);
      if (text) {
         try {
            const res = await axios.post('/api/jarvis/admin/message', { message: text });
            if (res.data.ui) {
              setResponseUi(res.data.ui);
            }
            
            // Speak and conditionally keep listening if continuous mode is active
            jarvisVoice.speak(res.data.voice, () => {
               if (continuousMode) {
                  setTimeout(startListening, 500); // Wait a bit before listening again
               }
            });
         } catch(e) {
            console.error(e);
            jarvisVoice.speak("Error al procesar la solicitud.", () => {
               if (continuousMode) setTimeout(startListening, 500);
            });
         }
      } else {
         // Empty transcript, just retry if continuous
         if (continuousMode) setTimeout(startListening, 500);
      }
    }, () => {
        setListening(false);
        if (continuousMode) setTimeout(startListening, 500);
    });
  };

  const enableVoice = () => {
    if (!active) {
      setActive(true);
      jarvisVoice.speak('Sistemas en línea, Administrador. Monitor de operaciones Novape activo.');
    } else {
      // Si está activo, un click inicia o detiene el modo continuo
      if (!continuousMode) {
          setContinuousMode(true);
          jarvisVoice.speak("Modo de conversación continua activado.", () => {
              startListening();
          });
      } else {
          setContinuousMode(false);
          setListening(false);
          jarvisVoice.speak("Modo continuo desactivado.");
      }
    }
  };

  return (
    <>
      <div className={`jarvis-orb ${continuousMode ? 'continuous-mode' : ''}`} onClick={enableVoice} title="Activar Jarvis (Clic para modo continuo)">
        <span className={`pulse ${active ? 'active' : ''} ${listening ? 'listening' : ''}`}></span>
        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" className="jarvis-svg">
           <circle cx="50" cy="50" r="40" stroke="#0ff" strokeWidth="4" fill="none" opacity="0.8"/>
           <circle cx="50" cy="50" r="20" fill="#0ff" opacity="0.6"/>
        </svg>
      </div>

      {responseUi && (
        <div className="jarvis-hud-overlay">
           <div className="jarvis-hud-content">
             <button className="close-btn" onClick={() => setResponseUi(null)}>×</button>
             {responseUi.title && <h3>{responseUi.title}</h3>}
             {responseUi.value && <div className="text-3xl text-cyan-400 my-2">{responseUi.value}</div>}
             {responseUi.subtitle && <p className="text-gray-400">{responseUi.subtitle}</p>}
             
             {responseUi.type === 'table' && responseUi.rows && (
               <table className="w-full text-left border-collapse mt-4">
                 <thead>
                   <tr>
                     {responseUi.columns.map((col, i) => <th key={i} className="border-b border-cyan-800 py-2">{col}</th>)}
                   </tr>
                 </thead>
                 <tbody>
                   {responseUi.rows.map((row, i) => (
                     <tr key={i}>
                       {Object.values(row).map((val, j) => <td key={j} className="py-2 border-b border-cyan-900/50">{val}</td>)}
                     </tr>
                   ))}
                 </tbody>
               </table>
             )}

             {responseUi.type === 'list' && responseUi.items && (
               <ul className="mt-4 text-left">
                  {responseUi.items.map((item, i) => (
                    <li key={i} className="mb-2">
                       <strong className="text-cyan-300">{item.title}</strong>
                       <br/><span className="text-gray-400 text-sm">{item.subtitle}</span>
                    </li>
                  ))}
               </ul>
             )}

             {responseUi.type === 'status' && responseUi.items && (
               <ul className="mt-4 text-left space-y-2">
                  {responseUi.items.map((item, i) => (
                    <li key={i} className="flex justify-between items-center bg-gray-800/50 p-2 rounded">
                       <span>{item.label}</span>
                       {item.status ? (
                         <span className={item.status === 'ok' ? 'text-green-400' : 'text-red-400'}>
                           {item.status.toUpperCase()}
                         </span>
                       ) : (
                         <span className="text-cyan-400 font-bold">{item.value}</span>
                       )}
                    </li>
                  ))}
               </ul>
             )}
           </div>
        </div>
      )}
    </>
  );
}
