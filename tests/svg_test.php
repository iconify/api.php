<?php

use \SimpleSVG\WebsiteIcons\SVG;

class SVGTest extends \PHPUnit\Framework\TestCase {
    protected function addMissingAttributes($data)
    {
        $defaultAttributes = [
            'left' => 0,
            'top' => 0,
            'rotate' => 0,
            'hFlip' => false,
            'vFlip' => false
        ];
        foreach ($defaultAttributes as $attr => $value) {
            if (!isset($data[$attr])) {
                $data[$attr] = $value;
            }
        }
        if (!isset($data['inlineHeight'])) {
            $data['inlineHeight'] = $data['height'];
        }
        if (!isset($data['inlineTop'])) {
            $data['inlineTop'] = $data['top'];
        }
        if (!isset($data['verticalAlign'])) {
            $data['verticalAlign'] = $data['height'] % 7 === 0 && $data['height'] % 8 !== 0 ? -0.143 : -0.125;
        }
        return $data;
    }

    public function testSimpleIcon()
    {
        $data = $this->addMissingAttributes([
            'body'  => '<body />',
            'width' => 24,
            'height'    => 24
        ]);
        $svg = new SVG($data);

        $result = $svg->parse();
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><body /></svg>', $result);

        // Custom dimensions
        $result = $svg->parse([
            'width' => 48
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="48" height="48" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><body /></svg>', $result);

        $result = $svg->parse([
            'height' => 32
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="32" height="32" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><body /></svg>', $result);
    }

    public function testColorsAndInline()
    {
        $data = $this->addMissingAttributes([
            'body'  => '<path d="whatever" fill="currentColor" />',
            'width' => 20,
            'height'    => 24,
            'inlineHeight' => 28,
            'inlineTop' => -2
        ]);
        $svg = new SVG($data);

        $result = $svg->parse();
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="0.84em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 24"><path d="whatever" fill="currentColor" /></svg>', $result);

        $result = $svg->parse([
            'width' => '48',
            'color' => 'red'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="48" height="57.6" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 24"><path d="whatever" fill="red" /></svg>', $result);

        $result = $svg->parse([
            'height' => '100%',
            'inline' => 'true'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="71.43%" height="100%" style="vertical-align: -0.125em;-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 -2 20 28"><path d="whatever" fill="currentColor" /></svg>', $result);
    }

    public function testCustomAlignment()
    {
        $data = $this->addMissingAttributes([
            'body'  => '<path d="whatever" fill="currentColor" />',
            'width' => 20,
            'height'    => 24,
            'inlineHeight' => 28,
            'inlineTop' => -2
        ]);
        $svg = new SVG($data);

        $result = $svg->parse([
            'align' => 'top',
            'width' => '50',
            'height' => '50'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMin meet" viewBox="0 0 20 24"><path d="whatever" fill="currentColor" /></svg>', $result);

        $result = $svg->parse([
            'align' => 'left,bottom',
            'width' => '50',
            'height' => '50'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMinYMax meet" viewBox="0 0 20 24"><path d="whatever" fill="currentColor" /></svg>', $result);

        $result = $svg->parse([
            'align' => 'right,middle,crop',
            'width' => '50',
            'height' => '50'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMaxYMid slice" viewBox="0 0 20 24"><path d="whatever" fill="currentColor" /></svg>', $result);
    }

    public function testTransformations()
    {
        $data = $this->addMissingAttributes([
            'body'  => '<body />',
            'width' => 20,
            'height'    => 24
        ]);
        $svg = new SVG($data);

        $result = $svg->parse([
            'rotate' => 1
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1.2em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 20"><g transform="rotate(90 12 12)"><body /></g></svg>', $result);

        $result = $svg->parse([
            'rotate' => '180deg'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="0.84em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 24"><g transform="rotate(180 10 12)"><body /></g></svg>', $result);

        $result = $svg->parse([
            'rotate' => '3'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1.2em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 20"><g transform="rotate(-90 10 10)"><body /></g></svg>', $result);

        $result = $svg->parse([
            'rotate' => '75%'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1.2em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 20"><g transform="rotate(-90 10 10)"><body /></g></svg>', $result);

        $result = $svg->parse([
            'flip' => 'Horizontal'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="0.84em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 24"><g transform="translate(20 0) scale(-1 1)"><body /></g></svg>', $result);

        $result = $svg->parse([
            'rotate' => '3'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1.2em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 20"><g transform="rotate(-90 10 10)"><body /></g></svg>', $result);

        $result = $svg->parse([
            'flip' => 'ignored, Vertical space-works-as-comma'
        ]);
        $this->assertEquals('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="0.84em" height="1em" style="-ms-transform: rotate(360deg); -webkit-transform: rotate(360deg); transform: rotate(360deg);" preserveAspectRatio="xMidYMid meet" viewBox="0 0 20 24"><g transform="translate(0 24) scale(1 -1)"><body /></g></svg>', $result);
    }
}
