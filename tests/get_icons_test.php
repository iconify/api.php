<?php

use \SimpleSVG\WebsiteIcons\Collection;

class GetIconsTest extends \PHPUnit\Framework\TestCase {
    public function testSeveralIcons()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />'
                ],
                'icon2' => [
                    'body'  => '<icon2 />'
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
                ]
            ],
            'width' => 20,
            'height'    => 20
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);
        $this->assertEquals([
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />',
                    'width' => 20,
                    'height'    => 20
                ],
                'icon3' => [
                    'body'  => '<icon3 />',
                    'width' => 20,
                    'height'    => 20
                ]
            ],
            'aliases'   => []
        ], $collection->getIcons(['icon1', 'icon3', 'icon20']));
    }

    public function testIconsAndAliases()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />'
                ],
                'icon2' => [
                    'body'  => '<icon2 />'
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
                ]
            ],
            'width' => 20,
            'height'    => 20
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);
        $this->assertEquals([
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />',
                    'width' => 20,
                    'height'    => 20
                ],
                'icon2' => [
                    'body'  => '<icon2 />',
                    'width' => 20,
                    'height'    => 20
                ]
            ],
            'aliases'   => [
                'alias1'    => [
                    'parent'    => 'icon1',
                    'rotate'    => 1
                ]
            ]
        ], $collection->getIcons(['icon2', 'alias1']));
    }

    public function testAliasesOnly()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />'
                ],
                'icon2' => [
                    'body'  => '<icon2 />'
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
                ]
            ],
            'width' => 20,
            'height'    => 20
        ];

        $collection = new Collection();
        $collection->loadJSON($data);

        $this->assertTrue($collection->loaded);
        $this->assertEquals([
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />',
                    'width' => 20,
                    'height'    => 20
                ]
            ],
            'aliases'   => [
                'alias1'    => [
                    'parent'    => 'icon1',
                    'rotate'    => 1
                ]
            ]
        ], $collection->getIcons(['icon20', 'alias1']));
    }

    public function testEmptyResponse()
    {
        $data = [
            'prefix'    => 'foo',
            'icons' => [
                'icon1' => [
                    'body'  => '<icon1 />'
                ],
                'icon2' => [
                    'body'  => '<icon2 />'
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
            'prefix'    => 'foo',
            'icons' => [],
            'aliases'   => []
        ], $collection->getIcons(['icon20', 'alias10']));
    }
}
