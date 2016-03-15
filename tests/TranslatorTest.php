<?php namespace Znck\Tests\States;

use Illuminate\Filesystem\Filesystem;
use Znck\Countries\FileLoader;
use Znck\Countries\Translator;

class TranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_works()
    {
        $translator = new Translator(new FileLoader(new Filesystem(), dirname(__DIR__).'/data'), 'en');

        $this->assertEquals('India', $translator->getName('in'));
    }

    public function test_it_works_too()
    {
        $translator = new Translator(new FileLoader(new Filesystem(), dirname(__DIR__).'/data'), 'en');

        $this->assertEquals('India', $translator->get('in'));
        $this->assertEquals('India', $translator->get('IN'));

        $this->assertEquals('lk', $translator->get('lk'));
    }
}
