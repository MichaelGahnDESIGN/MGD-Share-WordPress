from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo

root = Path('mgd-share-wordpress')
target = Path('dist') / 'mgd-share-wordpress-2.1.1.zip'
target.parent.mkdir(exist_ok=True)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(root.rglob('*')):
        if path.is_file() and path.suffix != '.log' and path.name != '.DS_Store':
            # Rechte fix 644, sonst kommen Dateien vom SMB-Laufwerk mit 700 an
            info = ZipInfo.from_file(path, path.as_posix())
            info.external_attr = (0o100644 << 16)
            info.compress_type = ZIP_DEFLATED
            archive.writestr(info, path.read_bytes())
print(target)
