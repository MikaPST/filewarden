"""Small synthetic document packages; no real user files or runtime dependencies."""
import io
import zipfile


def package(members):
    output = io.BytesIO()
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_STORED) as archive:
        for name, content in members.items():
            archive.writestr(name, content)
    return output.getvalue()


def document_fixtures():
    fixtures = {
        "txt": (b"A synthetic text document\n", "text/plain"),
        "csv": (b"name,value\nfixture,12\n", "text/csv"),
    }
    parts = {
        "docx": ("word/document.xml", "wordprocessingml.document", '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>FileWarden fixture</w:t></w:r></w:p></w:body></w:document>'),
        "xlsx": ("xl/workbook.xml", "spreadsheetml.sheet", '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Fixture" sheetId="1" r:id="rId1"/></sheets></workbook>'),
        "pptx": ("ppt/presentation.xml", "presentationml.presentation", '<p:presentation xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><p:sldIdLst><p:sldId id="256" r:id="rId1"/></p:sldIdLst><p:sldSz cx="9144000" cy="6858000"/><p:notesSz cx="6858000" cy="9144000"/></p:presentation>'),
    }
    relationship_ns = "http://schemas.openxmlformats.org/package/2006/relationships"
    office_ns = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/"
    for extension, (part, subtype, xml) in parts.items():
        mime = "application/vnd.openxmlformats-officedocument." + subtype
        overrides = '<Override PartName="/' + part + '" ContentType="' + mime + '.main+xml"/>'
        members = {
            "_rels/.rels": '<Relationships xmlns="' + relationship_ns + '"><Relationship Id="rId1" Type="' + office_ns + 'officeDocument" Target="' + part + '"/></Relationships>',
            part: xml,
        }
        if extension == "xlsx":
            members["xl/worksheets/sheet1.xml"] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>FileWarden fixture</t></is></c></row></sheetData></worksheet>'
            members["xl/_rels/workbook.xml.rels"] = '<Relationships xmlns="' + relationship_ns + '"><Relationship Id="rId1" Type="' + office_ns + 'worksheet" Target="worksheets/sheet1.xml"/></Relationships>'
            overrides += '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        if extension == "pptx":
            members["ppt/slides/slide1.xml"] = '<p:sld xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><p:cSld><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr/></p:spTree></p:cSld></p:sld>'
            members["ppt/_rels/presentation.xml.rels"] = '<Relationships xmlns="' + relationship_ns + '"><Relationship Id="rId1" Type="' + office_ns + 'slide" Target="slides/slide1.xml"/></Relationships>'
            overrides += '<Override PartName="/ppt/slides/slide1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>'
        members["[Content_Types].xml"] = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>' + overrides + '</Types>'
        fixtures[extension] = (package(members), mime)
    for extension, subtype, body in [
        ("odt", "text", '<office:text><text:p>FileWarden fixture</text:p></office:text>'),
        ("ods", "spreadsheet", '<office:spreadsheet><table:table table:name="Fixture"><table:table-row><table:table-cell office:value-type="string"><text:p>FileWarden fixture</text:p></table:table-cell></table:table-row></table:table></office:spreadsheet>'),
        ("odp", "presentation", '<office:presentation><draw:page draw:name="Fixture"/></office:presentation>'),
    ]:
        mime = "application/vnd.oasis.opendocument." + subtype
        xml = '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" office:version="1.2"><office:body>' + body + '</office:body></office:document-content>'
        fixtures[extension] = (package({"mimetype": mime, "content.xml": xml, "META-INF/manifest.xml": '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2"><manifest:file-entry manifest:full-path="/" manifest:media-type="' + mime + '"/><manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/></manifest:manifest>'}), mime)
    fixtures["zip"] = (package({"readme.txt": "Preserved downloadable archive\n"}), "application/zip")
    return fixtures
