import json
import re
import os

log_path = r"C:\Users\eduar\.gemini\antigravity-ide\brain\4a63895d-7941-402a-b0a4-0c1b64324d8f\.system_generated\logs\transcript.jsonl"
base_dir = r"c:\Users\eduar\Music\PROYECTO\Laravel\proyecto"

longest_content = {}

for line in open(log_path, 'r', encoding='utf-8'):
    try:
        data = json.loads(line)
        if 'content' not in data: continue
        content = data['content']
        
        parts = content.split("File Path: `file:///")
        for part in parts[1:]:
            filepath_end = part.find("`")
            if filepath_end == -1: continue
            filepath = part[:filepath_end].replace('/', '\\')
            
            if not filepath.endswith('.jsx'): continue
            
            start_marker = "Please note that any changes targeting the original code should remove the line number, colon, and leading space.\n"
            start_idx = part.find(start_marker)
            if start_idx == -1: continue
            
            end_marker = "\nThe above content shows the entire, complete file contents"
            end_idx = part.find(end_marker, start_idx)
            if end_idx == -1: continue
            
            raw_code = part[start_idx + len(start_marker) : end_idx]
            
            if filepath not in longest_content or len(raw_code) > len(longest_content[filepath]):
                longest_content[filepath] = raw_code
    except Exception as e:
        pass

for filepath, raw_code in longest_content.items():
    if not os.path.exists(filepath) or os.path.getsize(filepath) == 0:
        clean_lines = []
        for cl in raw_code.split('\n'):
            if re.match(r"^\d+: ", cl):
                clean_lines.append(cl.split(": ", 1)[1])
            else:
                if cl.strip() == "":
                    clean_lines.append("")
        
        os.makedirs(os.path.dirname(filepath), exist_ok=True)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write('\n'.join(clean_lines))
        print(f"Recovered {filepath}")

print("Done")
