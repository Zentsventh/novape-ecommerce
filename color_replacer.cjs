const fs = require('fs');
const path = require('path');

const directoryPath = path.join(__dirname, 'resources/js/Pages/Admin');

const colorMap = {
    // Reds
    '#ef4444': '#3b82f6', '#EF4444': '#3b82f6',
    'rgba(239,68,68,0.1)': 'rgba(59,130,246,0.1)',
    'rgba(239, 68, 68, 0.1)': 'rgba(59, 130, 246, 0.1)',
    'rgba(239,68,68,0.2)': 'rgba(59,130,246,0.2)',
    // Yellows
    '#eab308': '#60a5fa', '#EAB308': '#60a5fa',
    'rgba(234,179,8,0.1)': 'rgba(96,165,250,0.1)',
    // Greens
    '#10b981': '#2563eb', '#10B981': '#2563eb',
    '#22c55e': '#2563eb', '#22C55E': '#2563eb',
    'rgba(16,185,129,0.1)': 'rgba(37,99,235,0.1)',
    'rgba(34,197,94,0.1)': 'rgba(37,99,235,0.1)',
    'rgba(34,197,94,0.2)': 'rgba(37,99,235,0.2)',
    // Purples
    '#8a2be2': '#1d4ed8', '#8A2BE2': '#1d4ed8',
    'rgba(138,43,226,0.1)': 'rgba(29,78,216,0.1)',
    'rgba(138, 43, 226, 0.3)': 'rgba(29, 78, 216, 0.3)',
    '#4c1d95': '#1e3a8a',
    // Oranges
    '#f59e0b': '#1e40af', '#F59E0B': '#1e40af',
    'rgba(245,158,11,0.2)': 'rgba(30,64,175,0.2)',
    // Pinks
    '#ec4899': '#1e3a8a', '#EC4899': '#1e3a8a'
};

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        const dirPath = path.join(dir, f);
        if (f !== 'Dashboard.jsx' && f !== 'Index.jsx' && !dirPath.includes('Pos')) {
            // we already processed dashboard and POS Index. (Actually Pos Index still has some reds maybe? Let's process it too but maybe it's fine).
            // Let's just process all files, but Dashboard and Pos/Index are already handled mostly. 
            // Wait, let's process ALL files in Admin.
        }
        const isDirectory = fs.statSync(dirPath).isDirectory();
        isDirectory ? walkDir(dirPath, callback) : callback(path.join(dir, f));
    });
}

walkDir(directoryPath, function(filePath) {
    if (filePath.endsWith('.jsx') || filePath.endsWith('.css')) {
        let content = fs.readFileSync(filePath, 'utf8');
        let modified = false;
        
        for (const [oldColor, newColor] of Object.entries(colorMap)) {
            if (content.includes(oldColor)) {
                content = content.split(oldColor).join(newColor);
                modified = true;
            }
        }
        
        if (modified) {
            fs.writeFileSync(filePath, content, 'utf8');
            console.log('Updated: ' + filePath);
        }
    }
});
