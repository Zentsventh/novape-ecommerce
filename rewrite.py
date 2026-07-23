import os
import glob
import re

def rewrite_files():
    files = glob.glob('resources/js/**/*.jsx', recursive=True)
    
    for fpath in files:
        if 'ConfirmContext.jsx' in fpath:
            continue
            
        try:
            with open(fpath, 'r', encoding='utf-8') as f:
                content = f.read()
        except UnicodeDecodeError:
            with open(fpath, 'r', encoding='latin-1') as f:
                content = f.read()
            
        if 'confirm(' not in content:
            continue
            
        print(f"Rewriting {fpath}")
        
        # 1. Add import
        import_stmt = "import { useConfirm } from '@/Contexts/ConfirmContext';\n"
        if import_stmt not in content:
            import_match = list(re.finditer(r'^import .*;?$', content, re.MULTILINE))
            if import_match:
                last_import = import_match[-1]
                content = content[:last_import.end()] + "\n" + import_stmt + content[last_import.end():]
            else:
                content = import_stmt + content
            
        # 2. Add const confirmDialog = useConfirm();
        if 'const confirmDialog = useConfirm();' not in content:
            comp_match = re.search(r'export default function \w+\(.*?\)\s*{|const \w+\s*=\s*\(.*?\)\s*=>\s*{', content)
            if comp_match:
                insert_pos = comp_match.end()
                content = content[:insert_pos] + "\n    const confirmDialog = useConfirm();\n" + content[insert_pos:]
            
        # 3. Replace confirm( with await confirmDialog(
        content = content.replace('confirm(', 'await confirmDialog(')
        
        # Make enclosing arrows async
        content = re.sub(r'(\([^\)]*\)\s*=>\s*{\s*(?:if\s*\()?await confirmDialog)', r'async \1', content)
        content = re.sub(r'(\([^\)]*\)\s*=>\s*if\s*\(await confirmDialog)', r'async \1', content)
        
        # Fix duplicate async if it was already async
        content = content.replace('async async', 'async')
        
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)

rewrite_files()
