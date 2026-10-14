#!/usr/bin/env python3
"""Build clean WordPress.org and updater-enabled direct distributions."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import shutil
import tempfile
import zipfile

def header(source, name):
    match = re.search(r'^'+re.escape(name)+r':\s*(.+)$', source, re.M)
    if not match:
        raise ValueError('Missing '+name)
    return match[1].strip()

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--channel',choices=['wordpress-org','direct','all'],default='wordpress-org')
    parser.add_argument('--root',type=Path,default=Path(__file__).resolve().parents[1])
    args=parser.parse_args()
    root=args.root.resolve(); source=root/'theme';slug=header((source/'style.css').read_text(),'Text Domain')
    style=(source/'style.css').read_text();version=header(style,'Version')
    assert re.fullmatch(r'[a-z0-9]+(?:-[a-z0-9]+)*',slug)
    assert re.fullmatch(r'\d+(?:\.\d+){1,3}',version)
    assert 'Update URI:' not in style and not (source/'inc/gt-theme-updater.php').exists(), 'Canonical theme must remain updater-free'
    output=root/'build';output.mkdir(exist_ok=True)
    channels=['wordpress-org','direct'] if args.channel=='all' else [args.channel]
    manifest={}
    for channel in channels:
        with tempfile.TemporaryDirectory(prefix=slug+'-package-') as temp:
            staged=Path(temp)/slug
            shutil.copytree(source,staged,ignore=shutil.ignore_patterns('.DS_Store','.git','node_modules','*.map','__pycache__'))
            if channel=='direct':
                updater=(root/'distribution/gt-theme-updater.php').read_text().replace('__GT_THEME_TEXT_DOMAIN__',slug)
                (staged/'inc').mkdir(exist_ok=True)
                (staged/'inc/gt-theme-updater.php').write_text(updater)
                (staged/'style.css').write_text(style.replace('Version: '+version, 'Version: '+version+'\nUpdate URI: https://gauravtiwari.org/product/'+slug+'/'))
                functions=(staged/'functions.php').read_text()
                assert not functions.rstrip().endswith('?>')
                functions += "\n// Free updates for the direct-download distribution.\nrequire_once __DIR__ . '/inc/gt-theme-updater.php';\n\\Gatilab\\ThemeUpdates\\Client::boot();\n"
                (staged/'functions.php').write_text(functions)
                readme=(staged/'readme.txt').read_text()
                readme += '\n== Updates in this download ==\n\nThis direct-download build checks https://gauravtiwari.org/wp-json/gt-theme-updates/v1/themes/'+slug+' for free theme updates. No account, email, or license key is required. The updater sends the theme slug and WordPress version; it does not send your site URL or account details. Automatic installation follows your WordPress auto-update preference.\n\nInstall this build once to receive its updater. A separate WordPress.org build omits the updater and uses the directory update channel.\n'
                (staged/'readme.txt').write_text(readme)
            target=output/(slug+'-'+version+'.zip') if channel=='wordpress-org' else output/'direct'/(slug+'-'+version+'-updater.zip')
            target.parent.mkdir(exist_ok=True)
            with zipfile.ZipFile(target,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as archive:
                for path in sorted(staged.rglob('*')):
                    if path.is_file():
                        info=zipfile.ZipInfo(str(path.relative_to(staged.parent)),(2026,1,1,0,0,0))
                        info.compress_type=zipfile.ZIP_DEFLATED; info.external_attr=0o100644<<16
                        archive.writestr(info,path.read_bytes())
            with zipfile.ZipFile(target) as archive:
                names=archive.namelist()
                assert {n.split('/')[0] for n in names}=={slug}
                for required in ['style.css','functions.php','theme.json','readme.txt','screenshot.png']:
                    assert slug+'/'+required in names
                assert slug+'/templates/index.html' in names or slug+'/index.php' in names
                assert header(archive.read(slug+'/style.css').decode(),'Version')==version
                has_updater=slug+'/inc/gt-theme-updater.php' in names
                assert has_updater==(channel=='direct')
                if channel=='wordpress-org':
                    assert b'Update URI:' not in archive.read(slug+'/style.css')
                    assert b'Gatilab\\ThemeUpdates' not in archive.read(slug+'/functions.php')
            manifest[channel]={'path':str(target),'filename':target.name,'theme':slug,'version':version,'size':target.stat().st_size,'sha256':hashlib.sha256(target.read_bytes()).hexdigest(),'files':len(names),'requires':header(style,'Requires at least'),'requires_php':header(style,'Requires PHP'),'tested':header(style,'Tested up to')}
            print(json.dumps(manifest[channel]),flush=True)
    (output/('manifest-'+args.channel+'.json')).write_text(json.dumps(manifest,indent=2))

if __name__=='__main__': main()
