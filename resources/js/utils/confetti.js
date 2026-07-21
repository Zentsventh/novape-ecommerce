/**
 * Dispara un efecto de rayos (lightning) desde la posición exacta del cursor.
 * @param {MouseEvent} event - El evento del click
 */
export const fireConfetti = (event) => {
    // Coordenadas de origen
    const originX = event && event.clientX ? event.clientX : window.innerWidth / 2;
    const originY = event && event.clientY ? event.clientY : window.innerHeight / 2;

    // Crear un canvas temporal a pantalla completa
    const canvas = document.createElement('canvas');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    canvas.style.position = 'fixed';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.pointerEvents = 'none';
    canvas.style.zIndex = '99999';
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    
    // Configuración del rayo
    const maxBolts = 6;
    const segments = 15;
    const color = '#00B4FF'; // Azul eléctrico NovaPe
    const coreColor = '#FFFFFF';

    let alpha = 1;

    // Generar las rutas de los rayos
    const bolts = [];
    for (let i = 0; i < maxBolts; i++) {
        const angle = (Math.PI * 2 / maxBolts) * i + (Math.random() * 0.5 - 0.25);
        let currentX = originX;
        let currentY = originY;
        const path = [{ x: currentX, y: currentY }];
        
        for (let j = 0; j < segments; j++) {
            const length = 20 + Math.random() * 30;
            const branchAngle = angle + (Math.random() * 1.5 - 0.75); // Zig zag
            currentX += Math.cos(branchAngle) * length;
            currentY += Math.sin(branchAngle) * length;
            path.push({ x: currentX, y: currentY });
        }
        bolts.push(path);
    }

    // Función de renderizado
    const render = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (alpha <= 0) {
            canvas.remove();
            return;
        }

        // Efecto de parpadeo (flicker) típico de un rayo
        const currentAlpha = Math.random() > 0.2 ? alpha : alpha * 0.3;

        ctx.lineCap = 'round';
        ctx.lineJoin = 'miter';

        // Dibujar el resplandor (Glow) exterior
        ctx.shadowBlur = 20;
        ctx.shadowColor = color;
        ctx.strokeStyle = `rgba(0, 180, 255, ${currentAlpha})`;
        ctx.lineWidth = 4;
        
        ctx.beginPath();
        bolts.forEach(path => {
            ctx.moveTo(path[0].x, path[0].y);
            for (let j = 1; j < path.length; j++) {
                ctx.lineTo(path[j].x, path[j].y);
            }
        });
        ctx.stroke();

        // Dibujar el núcleo brillante interior
        ctx.shadowBlur = 10;
        ctx.shadowColor = coreColor;
        ctx.strokeStyle = `rgba(255, 255, 255, ${currentAlpha})`;
        ctx.lineWidth = 2;

        ctx.beginPath();
        bolts.forEach(path => {
            ctx.moveTo(path[0].x, path[0].y);
            for (let j = 1; j < path.length; j++) {
                ctx.lineTo(path[j].x, path[j].y);
            }
        });
        ctx.stroke();

        alpha -= 0.04; // Velocidad de desaparición
        requestAnimationFrame(render);
    };

    // Agregar un "flash" de luz inicial a la pantalla (opcional pero impactante)
    const flash = document.createElement('div');
    flash.style.position = 'fixed';
    flash.style.top = '0';
    flash.style.left = '0';
    flash.style.width = '100vw';
    flash.style.height = '100vh';
    flash.style.backgroundColor = 'rgba(255, 255, 255, 0.4)';
    flash.style.pointerEvents = 'none';
    flash.style.zIndex = '99998';
    flash.style.transition = 'opacity 0.2s ease-out';
    document.body.appendChild(flash);
    
    setTimeout(() => {
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 200);
    }, 50);

    render();
};
