<?php
namespace FileWarden\Tests;

use FileWarden\FileValidator;
use PHPUnit\Framework\TestCase;

/** Exercise hostile content against the actual upload validator. */
final class FileValidatorTest extends TestCase {
    private array $files = array();
    private function file( string $bytes ): string {
        $path = tempnam( sys_get_temp_dir(), 'fw-format-' );
        $this->files[] = $path;
        file_put_contents( $path, $bytes );
        return $path;
    }
    private function zip( array $members ): string {
        $path = $this->file( '' );
        $zip = new \ZipArchive();
        $this->assertTrue( $zip->open( $path, \ZipArchive::OVERWRITE ) );
        foreach ( $members as $name => $bytes ) { $zip->addFromString( $name, $bytes ); }
        $zip->close();
        return $path;
    }
    protected function tearDown(): void { foreach ( $this->files as $file ) { if ( is_file( $file ) ) unlink( $file ); } }

    public function test_plain_text_and_csv_are_validated(): void {
        $this->assertSame( 'text/plain', FileValidator::validate( $this->file( "FileWarden document\n" ), 'txt' ) );
        $this->assertSame( 'text/csv', FileValidator::validate( $this->file( "name,value\nfixture,12\n" ), 'csv' ) );
    }
    public function test_active_text_is_refused(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->file( '<?php echo 1;' ), 'txt' );
    }
    public function test_spreadsheet_formula_is_refused(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->file( "name,value\nfixture,=HYPERLINK(\"bad\")\n" ), 'csv' );
    }
    public function test_bom_cannot_hide_an_active_csv_formula(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->file( "\xEF\xBB\xBF=1+1\n" ), 'csv' );
    }
    public function test_zip_is_inspected_and_accepted_without_extraction(): void {
        $path = $this->zip( array( 'documents/fixture.txt' => "A safe attachment\n", 'table.csv' => "name,value\nsample,1\n" ) );
        $before = hash_file( 'sha256', $path );
        $this->assertSame( 'application/zip', FileValidator::validate( $path, 'zip' ) );
        $this->assertSame( $before, hash_file( 'sha256', $path ) );
    }
    public function test_traversal_is_refused(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->zip( array( '../outside.txt' => 'outside' ) ), 'zip' );
    }
    public function test_windows_paths_are_refused(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->zip( array( 'C:\\outside.txt' => 'outside' ) ), 'zip' );
    }
    public function test_script_disguised_as_text_is_refused_inside_zip(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->zip( array( 'fixture.txt' => '<script>alert(1)</script>' ) ), 'zip' );
    }
    public function test_executable_member_is_refused(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->zip( array( 'payload.php' => '<?php echo 1;' ) ), 'zip' );
    }
    public function test_nested_zip_is_refused(): void {
        $inner = $this->zip( array( 'safe.txt' => 'A safe document' ) );
        $outer = $this->zip( array( 'inner.zip' => file_get_contents( $inner ) ) );
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $outer, 'zip' );
    }
    public function test_expansion_ratio_is_bounded(): void {
        $this->expectException( \InvalidArgumentException::class );
        FileValidator::validate( $this->zip( array( 'bomb.txt' => str_repeat( 'A', 200000 ) ) ), 'zip' );
    }
    public function test_symbolic_link_is_refused(): void {
        $path = $this->zip( array( 'link.txt' => '/private/file' ) );
        $zip = new \ZipArchive(); $zip->open( $path );
        $zip->setExternalAttributesName( 'link.txt', \ZipArchive::OPSYS_UNIX, 0120777 << 16 ); $zip->close();
        $this->expectException( \InvalidArgumentException::class ); FileValidator::validate( $path, 'zip' );
    }
    public function test_office_packages_are_checked_for_the_correct_format(): void {
        foreach ( array( 'docx' => array('word/document.xml','document','wordprocessingml'), 'xlsx' => array('xl/workbook.xml','workbook','spreadsheetml'), 'pptx' => array('ppt/presentation.xml','presentation','presentationml') ) as $extension => [$part,$root,$namespace] ) {
            $path = $this->zip( array( '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>', $part => '<' . $root . ' xmlns="http://schemas.openxmlformats.org/' . $namespace . '/2006/main"/>' ) );
            $this->assertSame( FileValidator::MIMES[$extension], FileValidator::validate( $path, $extension ) );
        }
        foreach ( array( 'odt', 'ods', 'odp' ) as $extension ) {
            $path = $this->zip( array( 'mimetype' => FileValidator::MIMES[$extension], 'content.xml' => '<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"/>' ) );
            $this->assertSame( FileValidator::MIMES[$extension], FileValidator::validate( $path, $extension ) );
        }
    }
    public function test_office_macro_content_is_refused(): void {
        $path = $this->zip( array( '[Content_Types].xml' => '<Types/>', 'word/document.xml' => '<document/>', 'word/vbaProject.bin' => 'macro' ) );
        $this->expectException( \InvalidArgumentException::class ); FileValidator::validate( $path, 'docx' );
    }
    public function test_office_external_relations_are_refused(): void {
        $path = $this->zip( array( '[Content_Types].xml' => '<Types/>', 'word/document.xml' => '<document/>', 'word/_rels/document.xml.rels' => '<Relationship TargetMode="External"/>' ) );
        $this->expectException( \InvalidArgumentException::class ); FileValidator::validate( $path, 'docx' );
    }
    public function test_budget_is_shared_with_nested_office_containers(): void {
        $office = $this->zip( array( '[Content_Types].xml' => '<Types/>', 'word/document.xml' => '<document>' . bin2hex( random_bytes( 500 ) ) . '</document>' ) );
        $outer = $this->zip( array( 'document.docx' => file_get_contents( $office ) ) );
        $budget = array( 'bytes' => filesize( $office ) + 10, 'entries' => 100 );
        $this->expectException( \InvalidArgumentException::class ); FileValidator::validate( $outer, 'zip', false, $budget );
    }
    public function test_malformed_office_xml_is_refused(): void {
        $path = $this->zip(array('[Content_Types].xml'=>'<Types/>','word/document.xml'=>'<document>broken'));
        $this->expectException(\InvalidArgumentException::class); FileValidator::validate($path,'docx');
    }
    public function test_aliased_odf_scripts_are_refused(): void {
        $path = $this->zip(array('mimetype'=>FileValidator::MIMES['odt'],'content.xml'=>'<o:document-content xmlns:o="urn:oasis:names:tc:opendocument:xmlns:office:1.0"><o:scripts/></o:document-content>'));
        $this->expectException(\InvalidArgumentException::class); FileValidator::validate($path,'odt');
    }
    public function test_word_dde_fields_are_refused(): void {
        $path = $this->zip(array('[Content_Types].xml'=>'<Types/>','word/document.xml'=>'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:instrText>DDEAUTO command</w:instrText></w:document>'));
        $this->expectException(\InvalidArgumentException::class); FileValidator::validate($path,'docx');
    }
    public function test_utf16_xml_cannot_hide_external_relationships(): void {
        $xml = '<?xml version="1.0" encoding="UTF-16"?><Relationships><Relationship TargetMode="External"/></Relationships>';
        $encoded = "\xFF\xFE" . mb_convert_encoding($xml,'UTF-16LE','UTF-8');
        $path = $this->zip(array('[Content_Types].xml'=>'<Types/>','word/document.xml'=>'<document/>','_rels/.rels'=>$encoded));
        $this->expectException(\InvalidArgumentException::class); FileValidator::validate($path,'docx');
    }
}
