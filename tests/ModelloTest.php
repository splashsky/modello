<?php

namespace Splashsky\Tests;

use PHPUnit\Framework\TestCase;
use Splashsky\Modello;

class ModelloTest extends TestCase
{
    private Modello $modello;

    protected function setUp(): void
    {
        $this->modello = new Modello(__DIR__.'/views/', __DIR__.'/cache/views/');
        $this->modello->setCacheEnabled(false);
    }

    public function testView()
    {
        $output = $this->modello->view('example', ['name' => 'John']);
        $this->assertSame('Hello, John!', trim($output));
    }

    public function testSetViews()
    {
        $this->modello->setViews(__DIR__.'/other-views/');
        $this->assertSame(__DIR__.'/other-views/', $this->modello->setViews(__DIR__.'/other-views/'));
    }

    public function testSetCache()
    {
        $this->modello->setCache(__DIR__.'/other-cache/');
        $this->assertSame(__DIR__.'/other-cache/', $this->modello->setCache(__DIR__.'/other-cache/'));
    }

    public function testSetCacheEnabled()
    {
        $this->modello->setCacheEnabled(true);
        $this->assertTrue($this->modello->setCacheEnabled(true));
    }

    public function testSetExtension()
    {
        $this->modello->setExtension('.custom.php');
        $this->assertSame('.custom.php', $this->modello->setExtension('.custom.php'));
    }

    public function testParse()
    {
        $string = 'Hello, {{ name }}!';
        $data = ['name' => 'John'];
        $output = Modello::parse($string, $data);
        $this->assertSame('Hello, John!', $output);
    }
}
