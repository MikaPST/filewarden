"""Build/check bundled catalogs using standard PO/MO formats and Python's GNU gettext reader.

Extract/update source strings with WordPress CLI first; this tool never translates text.
"""
import argparse
import ast
import gettext
import json
import re
import struct
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def entries(text):
    result = []
    for block in re.split(r'\n\s*\n', text):
        fields, active = {}, None
        for line in block.splitlines():
            if line.startswith('#'):
                continue
            match = re.match(r'(msgid|msgstr|msgid_plural|msgctxt|msgstr\[\d+\])\s+(".*")$', line)
            if match:
                active = match[1]
                fields[active] = ast.literal_eval(match[2])
            elif line.startswith('"') and active:
                fields[active] += ast.literal_eval(line)
        if 'msgid' in fields:
            if '#, fuzzy' in block:
                raise ValueError('Unreviewed fuzzy translation: ' + fields['msgid'])
            result.append(fields)
    return result


def key(entry):
    value = entry['msgid']
    if 'msgctxt' in entry:
        value = entry['msgctxt'] + '\x04' + value
    if 'msgid_plural' in entry:
        value += '\0' + entry['msgid_plural']
    return value


def compile_mo(items):
    encoded = []
    for item in items:
        value = ('\0'.join(item['msgstr[' + str(i) + ']'] for i in range(2))
                 if 'msgid_plural' in item else item['msgstr'])
        encoded.append((key(item).encode('utf-8'), value.encode('utf-8')))
    encoded.sort()
    size = len(encoded)
    key_data, value_data = b'', b''
    key_offsets, value_offsets = [], []
    base = 28 + 16 * size
    for original, translated in encoded:
        key_offsets.append((len(original), base + len(key_data)))
        key_data += original + b'\0'
    for original, translated in encoded:
        value_offsets.append((len(translated), base + len(key_data) + len(value_data)))
        value_data += translated + b'\0'
    return (struct.pack('<7I', 0x950412de, 0, size, 28, 28 + 8 * size, 0, 0)
            + b''.join(struct.pack('<2I', *row) for row in key_offsets + value_offsets)
            + key_data + value_data)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--check', action='store_true')
    parser.add_argument('--pot', type=Path, default=ROOT / 'languages/filewarden.pot')
    args = parser.parse_args()
    french = entries((ROOT / 'languages/filewarden-fr_FR.po').read_text(encoding='utf-8-sig'))
    template = entries(args.pot.read_text(encoding='utf-8-sig'))
    required = {key(item) for item in template if item['msgid']}
    found = {key(item) for item in french if item['msgid']}
    if len(found) != len(french) - 1:
        raise ValueError('Duplicate translation keys')
    if required - found:
        raise ValueError('Missing source strings: ' + repr(sorted(required - found)))
    for item in french:
        values = [item.get('msgstr[0]'), item.get('msgstr[1]')] if 'msgid_plural' in item else [item.get('msgstr')]
        if item['msgid'] and not all(isinstance(value, str) and value for value in values):
            raise ValueError('Missing French translation: ' + item['msgid'])
        if item['msgid']:
            placeholders = re.findall(r'%(?:\d+\$)?[sdf]', item['msgid'])
            for value in values:
                if sorted(placeholders) != sorted(re.findall(r'%(?:\d+\$)?[sdf]', value)):
                    raise ValueError('Changed placeholders: ' + item['msgid'])
    english = []
    for item in french:
        translated = dict(item)
        if not item['msgid']:
            translated['msgstr'] = item['msgstr'].replace('Language: fr_FR', 'Language: en_US').replace('plural=(n > 1)', 'plural=(n != 1)')
        elif 'msgid_plural' in item:
            translated['msgstr[0]'], translated['msgstr[1]'] = item['msgid'], item['msgid_plural']
        else:
            translated['msgstr'] = item['msgid']
        english.append(translated)
    output = []
    for item in english:
        for field in ('msgctxt', 'msgid', 'msgid_plural', 'msgstr', 'msgstr[0]', 'msgstr[1]'):
            if field in item:
                output.append(field + ' ' + json.dumps(item[field], ensure_ascii=False))
        output.append('')
    files = {
        'filewarden-fr_FR.mo': compile_mo(french),
        'filewarden-en_US.mo': compile_mo(english),
        'filewarden-en_US.po': '\n'.join(output).encode('utf-8'),
    }
    for name, data in files.items():
        path = ROOT / 'languages' / name
        if args.check:
            if path.read_bytes() != data:
                raise ValueError('Catalog is not up to date: ' + name)
        else:
            path.write_bytes(data)
        if name.endswith('.mo'):
            with path.open('rb') as stream:
                loaded = gettext.GNUTranslations(stream)
            if name.endswith('fr_FR.mo'):
                assert loaded.gettext('General') == 'Général'
                assert loaded.ngettext('%s document to classify', '%s documents to classify', 1) == '%s document à classer'
                assert loaded.ngettext('%s document to classify', '%s documents to classify', 2) == '%s documents à classer'
    print(str(len(required)) + ' source entries covered; French/English catalogs, placeholders and plurals verified.')


if __name__ == '__main__':
    main()
