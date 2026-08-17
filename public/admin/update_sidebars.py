import os, glob, re

files = glob.glob('*.php')
sidebar_code = "<?php include 'sidebar.php'; ?>"

for f in files:
    if f == 'sidebar.php': continue
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    if '<!-- SIDEBAR -->' in content and '<!-- MAIN CONTENT -->' in content:
        new_content = re.sub(r'<!-- SIDEBAR -->.*?<!-- MAIN CONTENT -->', f'{sidebar_code}\n\n<!-- MAIN CONTENT -->', content, flags=re.DOTALL)
        with open(f, 'w', encoding='utf-8') as file:
            file.write(new_content)
        print(f'Updated {f}')
