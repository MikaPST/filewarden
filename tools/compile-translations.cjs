/** Compile the plugin's single-line, non-plural PO entries into a GNU MO catalog. */
const fs = require('node:fs');
const path = require('node:path');
const input = path.join(__dirname, '..', 'languages', 'filewarden-fr_FR.po');
const source = fs.readFileSync(input, 'utf8');
const matches = [...source.matchAll(/msgid ("(?:[^"\\]|\\.)*")\r?\nmsgstr ("(?:[^"\\]|\\.)*")/g)];
if (matches.length !== (source.match(/^msgid /gm) || []).length || /^msgid_plural|^msgstr\[|^"/m.test(source)) {
    throw new Error('Unsupported PO syntax: use a full gettext compiler for multiline or plural entries.');
}
const entries = matches.map(m => [Buffer.from(JSON.parse(m[1])), Buffer.from(JSON.parse(m[2]))])
    .sort((a, b) => Buffer.compare(a[0], b[0]));
const count = entries.length;
const offset = 28 + count * 16;
const header = Buffer.alloc(offset);
[0x950412de, 0, count, 28, 28 + count * 8, 0, 0].forEach((value, i) => header.writeUInt32LE(value, i * 4));
let cursor = offset;
const contents = [];
for (let column = 0; column < 2; column++) {
    entries.forEach((entry, i) => {
        const bytes = entry[column];
        header.writeUInt32LE(bytes.length, 28 + (column * count + i) * 8);
        header.writeUInt32LE(cursor, 32 + (column * count + i) * 8);
        contents.push(bytes, Buffer.from([0]));
        cursor += bytes.length + 1;
    });
}
fs.writeFileSync(input.replace(/\.po$/, '.mo'), Buffer.concat([header, ...contents]));
console.log('Compiled ' + count + ' French translations.');
