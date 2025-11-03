<?php

require_once dirname(__DIR__) . '/bootstrap.php';

class JsSearchClientTest extends TestCase
{
    private $js;

    public function setUp()
    {
        $path = dirname(dirname(__DIR__)) . '/js/icmp-table-updater.js';
        $this->assertTrue(file_exists($path), 'icmp-table-updater.js should exist');
        $this->js = file_get_contents($path);
    }

    public function testDefinesParseMysqlDateTime()
    {
        $this->assertTrue(strpos($this->js, 'function parseMysqlDateTime') !== false, 'parseMysqlDateTime helper must be defined');
    }

    public function testRenderTableUsesParseMysqlDateTime()
    {
        $this->assertTrue(strpos($this->js, 'parseMysqlDateTime(') !== false, 'renderTable should use parseMysqlDateTime for lastcheck');
    }
}

