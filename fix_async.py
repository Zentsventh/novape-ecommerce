import os
import glob
import re

files_to_check = [
    'resources/js/Pages/Auth/Profile.jsx',
    'resources/js/Pages/Admin/Ajustes/Permisos.jsx',
    'resources/js/Pages/Admin/Pedidos/Show.jsx',
    'resources/js/Pages/Admin/Pos/Index.jsx',
    'resources/js/Pages/Admin/Products/Form.jsx'
]

# We will just replace specific patterns that we know failed.
for fpath in files_to_check:
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Pattern: const myFunc = (e) => {
    # followed by some code and then await confirmDialog
    
    # Let's just make all `const XYZ = (e) => {` async if they contain await confirmDialog inside their body? No, easier to just do it with regex.
    # Instead, let's fix it manually.
    
    # 1. Profile.jsx
    content = content.replace('const submitDeleteAccount = (e) => {', 'const submitDeleteAccount = async (e) => {')
    
    # 2. Permisos.jsx
    content = content.replace('const deleteRole = (id, nombre) => {', 'const deleteRole = async (id, nombre) => {')
    
    # 3. Pedidos/Show.jsx
    # 181:39 is probably a function
    content = content.replace('const handleCancel = () => {', 'const handleCancel = async () => {')
    # Maybe it's onClick={() => { ... if(await... ? Let's make () => { async
    content = re.sub(r'onClick={\(\) => {\s*if\(await confirmDialog', r'onClick={async () => { if(await confirmDialog', content)
    
    # 4. Pos/Index.jsx
    content = content.replace('const clearCart = () => {', 'const clearCart = async () => {')
    content = re.sub(r'onClick={\(\) => {\s*if\(await confirmDialog', r'onClick={async () => { if(await confirmDialog', content)
    
    # 5. Products/Form.jsx
    content = content.replace('const submit = (e) => {', 'const submit = async (e) => {')
    
    # Make sure we didn't miss anything that starts with `onClick={() => {`
    # Let's replace any `() => { ... await confirmDialog` with `async () => { ... await confirmDialog` if it's not already async
    # A bit hard with regex, let's just write back and check if Vite compiles.
    
    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)

print("Fixed async")
