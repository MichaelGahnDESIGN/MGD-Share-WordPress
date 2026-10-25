from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

root = Path('mgd-share-wordpress')
target = Path('dist') / 'mgd-share-wordpress-0.1.5.zip'
target.parent.mkdir(exist_ok=True)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(root.rglob('*')):
        if path.is_file() and path.suffix != '.log':
            archive.write(path, path.as_posix())
print(target)
