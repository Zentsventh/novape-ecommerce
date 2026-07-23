import os
import glob
import re

files = glob.glob('resources/js/**/*.jsx', recursive=True)

for fpath in files:
    try:
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()
    except UnicodeDecodeError:
        with open(fpath, 'r', encoding='latin-1') as f:
            content = f.read()
        
    if 'await confirmDialog' not in content:
        continue
        
    parts = content.split('await confirmDialog')
    
    new_content = parts[0]
    for i in range(1, len(parts)):
        matches = list(re.finditer(r'const\s+(\w+)\s*=\s*(\([^\)]*\))\s*=>\s*\{', new_content))
        if matches:
            last_match = matches[-1]
            if 'async' not in new_content[last_match.start()-10:last_match.start()]:
                start = last_match.start()
                end = last_match.end()
                new_content = new_content[:start] + f"const {last_match.group(1)} = async {last_match.group(2)} => {{" + new_content[end:]
                
        new_content += 'await confirmDialog' + parts[i]
        
    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(new_content)

print("Done")
