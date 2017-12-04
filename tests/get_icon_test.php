<?php

use \SimpleSVG\WebsiteIcons\Collection;

class GetIconTest extends \PHPUnit\Framework\TestCase {
    public function testSimpleIcon()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />'
                ],
                'icon2' => [
                    'body'  => '<icon2 />',
                    'width' => 30,
                    'left'  => -10,
                    'top'   => -5
                ],
                'icon3' => [
                    'body'  => '<icon3 />'
                ],
                'icon4' => [
                    'body'  => '<icon4 />'
                ]
            ],
            'width' => 20,
            'height'    => 20
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);
        $this->assertEquals([
            'body'  => '<icon1 />',
            'left'  => 0,
            'top'   => 0,
            'width' => 20,
            'height'    => 20,
            'inlineHeight'  => 20,
            'hFlip' => false,
            'vFlip' => false,
            'rotate'    => 0,
            'inlineTop' => 0,
            'verticalAlign' => -0.125
        ], $collection->getIcon('icon1'));

        $this->assertEquals([
            'body'  => '<icon2 />',
            'left'  => -10,
            'top'   => -5,
            'width' => 30,
            'height'    => 20,
            'inlineHeight'  => 20,
            'hFlip' => false,
            'vFlip' => false,
            'rotate'    => 0,
            'inlineTop' => -5,
            'verticalAlign' => -0.125
        ], $collection->getIcon('icon2'));
    }

    public function testAlias()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />'
                ],
                'icon2' => [
                    'body'  => '<icon2 />',
                    'rotate'    => 3,
                    'hFlip' => true,
                    'vFlip' => true,
                    'top'   => -3
                ],
                'icon3' => [
                    'body'  => '<icon3 />'
                ],
                'icon4' => [
                    'body'  => '<icon4 />'
                ]
            ],
            'aliases'   => [
                'alias1'    => [
                    'parent'    => 'icon1',
                    'rotate'    => 1
                ],
                'alias2'    => [
                    'parent'    => 'icon2',
                    'rotate'    => 2,
                    'hFlip' => true,
                    'width' => 30,
                    'height'    => 28 // verticalAlign should be -1/7
                ],
                'alias3'    => [
                    'parent'    => 'missing-icon'
                ],
                'alias4'    => [
                    'parent'    => 'alias5'
                ],
                'alias5'    => [
                    'parent'    => 'alias4'
                ]
            ],
            'width' => 20,
            'height'    => 20
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);

        // Simple alias
        $this->assertEquals([
            'body'  => '<icon1 />',
            'parent'    => 'icon1', // Leftover from merging objects
            'left'  => 0,
            'top'   => 0,
            'width' => 20,
            'height'    => 20,
            'inlineHeight'  => 20,
            'hFlip' => false,
            'vFlip' => false,
            'rotate'    => 1,
            'inlineTop' => 0,
            'verticalAlign' => -0.125
        ], $collection->getIcon('alias1'));

        // Alias with overwritten properties
        $this->assertEquals([
            'body'  => '<icon2 />',
            'parent'    => 'icon2', // Leftover from merging objects
            'left'  => 0,
            'top'   => -3,
            'width' => 30,
            'height'    => 28,
            'inlineHeight'  => 28, // same as height
            'hFlip' => false,
            'vFlip' => true,
            'rotate'    => 5,
            'inlineTop' => -3, // same as top
            'verticalAlign' => -0.143
        ], $collection->getIcon('alias2'));

        // Alias that has no parent
        $this->assertNull($collection->getIcon('alias3'));

        // Infinite loop
        $this->assertNull($collection->getIcon('alias4'));

        // No such icon/alias
        $this->assertNull($collection->getIcon('whatever'));
    }
}
