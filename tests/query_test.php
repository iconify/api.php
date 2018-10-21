<?php

use \Iconify\API\Query;
use \Iconify\API\Collection;

class QueryTest extends \PHPUnit\Framework\TestCase {
    protected $_collection1;
    protected $_collection2;

    protected function setUp()
    {
        $this->_collection1 = new Collection('test');
        $this->_collection1->loadJSON([
            'prefix'    => 'test',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 fill="currentColor" />',
                    'width' => 30
                ],
                'icon2' => [
                    'body'  => '<icon2 />'
                ]
            ],
            'aliases'   => [
                'alias1'    => [
                    'parent'    => 'icon2',
                    'hFlip' => true
                ]
            ],
            'width' => 24,
            'height'    => 24
        ]);

        $this->_collection2 = new Collection('test2');
        $this->_collection2->loadJSON([
            'icons' => [
                'test2-icon1' => [
                    'body'  => '<icon1 fill="currentColor" />',
                    'width' => 30
                ],
                'test2-icon2' => [
                    'body'  => '<icon2 />'
                ],
                'test2-icon3'   => [
                    'body'  => '<defs><foo id="bar" /></defs><bar use="url(#bar)" fill="currentColor" stroke="currentColor" />'
                ]
            ],
            'aliases'   => [
                'test2-alias1'    => [
                    'parent'    => 'test2-icon2',
                    'hFlip' => true
                ]
            ],
            'width' => 24,
            'height'    => 24
        ]);
    }

    public function testIconsList()
    {
        $this->assertEquals([
            'type'  => 'application/javascript; charset=utf-8',
            'body'  => 'SimpleSVG._loaderCallback({"prefix":"test","icons":{"icon2":{"body":"<icon2 \\/>","width":24,"height":24}},"aliases":{"alias1":{"parent":"icon2","hFlip":true}}})'
        ], Query::parse($this->_collection1, 'icons', 'js', [
            'icons' => 'alias1'
        ]));

        // Query collection without prefix, json
        $this->assertEquals([
            'type'  => 'application/json; charset=utf-8',
            'body'  => '{"prefix":"test2","icons":{"icon2":{"body":"<icon2 \\/>","width":24,"height":24}},"aliases":{"alias1":{"parent":"icon2","hFlip":true}}}'
        ], Query::parse($this->_collection2, 'icons', 'json', [
            'icons' => 'alias1'
        ]));

        // Custom callback
        $this->assertEquals([
            'type'  => 'application/javascript; charset=utf-8',
            'body'  => 'console.log({"prefix":"test","icons":{"icon1":{"body":"<icon1 fill=\\"currentColor\\" \\/>","width":30,"height":24},"icon2":{"body":"<icon2 \\/>","width":24,"height":24}}})'
        ], Query::parse($this->_collection1, 'icons', 'js', [
            'icons' => 'icon1,icon2',
            'callback'  => 'console.log'
        ]));
    }

    public function testSVG()
    {
        // Simple icon
        $this->assertEquals([
            'filename'  => 'icon1.svg',
            'type'  => 'image/svg+xml; charset=utf-8',
            'body'  => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1.25em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 30 24"><icon1 fill="currentColor" /></svg>'
        ], Query::parse($this->_collection1, 'icon1', 'svg', []));

        // Icon with custom attributes
        $this->assertEquals([
            'filename'  => 'alias1.svg',
            'type'  => 'image/svg+xml; charset=utf-8',
            'body'  => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><g transform="translate(24 0) scale(-1 1)"><icon2 /></g></svg>'
        ], Query::parse($this->_collection1, 'alias1', 'svg', [
            'color' => 'red'
        ]));

        // Icon with id replacement
        $result = Query::parse($this->_collection2, 'icon3', 'svg', [
            'color' => 'red',
            'rotate'    => '90deg'
        ]);
        $result = preg_replace('/IconifyId-[0-9a-f]+-[0-9a-f]+-[0-9]+/', 'some-id', $result['body']);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><g transform="rotate(90 12 12)"><defs><foo id="some-id" /></defs><bar use="url(#some-id)" fill="red" stroke="red" /></g></svg>', $result);
    }
}
