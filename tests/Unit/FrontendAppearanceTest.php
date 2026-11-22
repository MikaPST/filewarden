<?php
use FileWarden\FrontendAppearance;
use PHPUnit\Framework\TestCase;

final class FrontendAppearanceTest extends TestCase {
    public function test_public_stylesheet_cannot_select_an_arbitrary_path(): void {
        $this->expectException(InvalidArgumentException::class);
        FrontendAppearance::stylesheet_url('../wp-config');
    }
    public function test_manual_colors_are_classified_as_custom_even_without_javascript(): void {
        $input=FrontendAppearance::defaults();$input['colors']['button']='#334155';
        self::assertSame('custom',FrontendAppearance::validate($input)['preset']);
        $input['preset']='custom';$input['colors']=FrontendAppearance::presets()['filewarden'];
        self::assertSame('custom',FrontendAppearance::validate($input)['preset']);
    }
    public function test_all_presets_are_complete_and_pass_actual_text_pairs(): void {
        foreach (FrontendAppearance::presets() as $name => $colors) {
            $input=FrontendAppearance::defaults();
            $input['preset']=$name;
            $input['colors']=$colors;
            self::assertSame($input,FrontendAppearance::validate($input));
            self::assertSame([],FrontendAppearance::contrast_failures($colors));
        }
    }

    public function test_css_expressions_and_missing_colors_are_rejected(): void {
        foreach (['url(https://example.test/track)','red','#fff','#ffffff;display:none','</style>','#gggggg',[],null] as $value) {
            $input=FrontendAppearance::defaults();$input['colors']['background']=$value;
            try { FrontendAppearance::validate($input); self::fail('Unsafe color accepted'); }
            catch (InvalidArgumentException $error) { self::assertSame('Invalid appearance color.',$error->getMessage()); }
        }
    }

    public function test_invisible_text_and_invisible_hover_are_rejected(): void {
        foreach (['button','hover'] as $key) {
            $input=FrontendAppearance::defaults();$input['colors'][$key]='#ffffff';
            try { FrontendAppearance::validate($input); self::fail('Low-contrast state accepted'); }
            catch (InvalidArgumentException $error) { self::assertSame('Insufficient appearance contrast.',$error->getMessage()); }
        }
    }

    public function test_name_is_plain_text_and_visibility_is_explicit(): void {
        $input=FrontendAppearance::defaults();$input['name']='Espace documentaire';$input['show_logo']='0';
        self::assertFalse(FrontendAppearance::validate($input)['show_logo']);
        $input['name']=str_repeat('é',64);
        self::assertSame($input['name'],FrontendAppearance::validate($input)['name']);
        foreach (['<img src=x onerror=alert(1)>',"Name\nInjected",str_repeat('a',65),str_repeat('é',65)] as $name) {
            $input['name']=$name;
            try { FrontendAppearance::validate($input); self::fail('Unsafe public name accepted'); }
            catch (InvalidArgumentException $error) { self::assertSame('Invalid public name.',$error->getMessage()); }
        }
    }

    public function test_empty_visible_name_and_arbitrary_visibility_are_rejected(): void {
        $input=FrontendAppearance::defaults();$input['name']='';
        try { FrontendAppearance::validate($input); self::fail('Empty visible name accepted'); }
        catch (InvalidArgumentException $error) { self::assertSame('Invalid public name.',$error->getMessage()); }
        $input['show_name']=false;
        self::assertSame('',FrontendAppearance::validate($input)['name']);
        $input['show_logo']='yes';
        $this->expectException(InvalidArgumentException::class);
        FrontendAppearance::validate($input);
    }

    public function test_nonce_is_request_stable_and_unpredictable_length(): void {
        $nonce=FrontendAppearance::nonce();
        self::assertMatchesRegularExpression('/^[a-f0-9]{48}$/D',$nonce);
        self::assertSame($nonce,FrontendAppearance::nonce());
    }
}
