from pathlib import Path
import re
import sys

errors = []
for markdown in Path('.').rglob('*.md'):
    for target in re.findall(r'\]\(([^)#]+)', markdown.read_text(encoding='utf-8')):
        if target.startswith(('http://', 'https://', 'mailto:')):
            continue
        if not (markdown.parent / target).exists():
            errors.append(f'{markdown}: fehlendes Ziel {target}')
if errors:
    print('\n'.join(errors)); sys.exit(1)
print('Dokumentation geprüft.')
