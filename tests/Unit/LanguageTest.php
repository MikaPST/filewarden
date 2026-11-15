<?php
namespace FileWarden\Tests;

use FileWarden\Language;
use PHPUnit\Framework\TestCase;

/** Locale fallback must be deterministic and never accept an arbitrary catalog path. */
final class LanguageTest extends TestCase {
    public function test_site_variants_manual_override_and_unsupported_fallback(): void {
        foreach (array(
            array('fr_FR', 'auto', 'fr_FR'),
            array('fr_CA', 'auto', 'fr_FR'),
            array('fr-BE', 'auto', 'fr_FR'),
            array('en_GB', 'auto', 'en_US'),
            array('de_DE', 'auto', 'en_US'),
            array('', 'auto', 'en_US'),
            array(array('fr_FR'), 'auto', 'en_US'),
            array('fr_FR', 'en_US', 'en_US'),
            array('en_US', 'fr_FR', 'fr_FR'),
            array('en_US', '../../private', 'en_US'),
        ) as [$site,$choice,$expected]) {
            $this->assertSame($expected, Language::resolve($site,$choice));
        }
    }
}
