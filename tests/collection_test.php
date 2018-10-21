<?php

use \Iconify\API\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase {
    public function testWithPrefix()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'baz90'    => [
                    'parent'    => 'baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);
        $this->assertEquals('foo', $collection->prefix);

        $items = $collection->getItems();
        $this->assertEquals($data['icons']['bar'], $items['icons']['bar']);
    }

    public function testWithoutPrefix()
    {
        $data = [
            'icons' => [
                'foo-bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'foo-baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'foo-baz90'    => [
                    'parent'    => 'foo-baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection('foo');
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);
        $this->assertEquals('foo', $collection->prefix);

        $items = $collection->getItems();
        $this->assertEquals(['bar', 'baz'], array_keys($items['icons']));
    }

    public function testWithoutDetectablePrefix()
    {
        $data = [
            'icons' => [
                'foo-bar' => [
                    'body'  => '<bar />',
                    'width' => 20,
                    'height'    => 20
                ],
                'foo-baz' => [
                    'body'  => '<baz />',
                    'width' => 30,
                    'height'    => 40
                ]
            ],
            'aliases'   => [
                'foo-baz90'    => [
                    'parent'    => 'foo-baz',
                    'rotate'    => 1
                ]
            ]
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertFalse($collection->loaded);
        $this->assertNull($collection->prefix);
    }

    public function testOptimizedCollection()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'bar' => [
                    'body'  => '<bar />',
                    'height'    => 20
                ],
                'baz' => [
                    'body'  => '<baz />'
                ]
            ],
            'aliases'   => [
                'baz90'    => [
                    'parent'    => 'baz',
                    'rotate'    => 1
                ]
            ],
            'width' => 30,
            'height'    => 40
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);
        $this->assertEquals('foo', $collection->prefix);

        $items = $collection->getItems();
        $this->assertEquals($items['icons']['bar'], [
            'body'  => '<bar />',
            'height'    => 20,
            'width' => 30
        ]);
        $this->assertEquals($items['icons']['baz'], [
            'body'  => '<baz />',
            'width' => 30,
            'height'    => 40
        ]);
    }
}
