<?php
/**
 * This file is part of OXID eSales Demo Data Installer.
 *
 * OXID eSales Demo Data Installer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales Demo Data Installer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales Demo Data Installer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2017
 */

namespace OxidEsales\DemoDataInstaller\Tests\Unit;

use org\bovigo\vfs\vfsStream;
use OxidEsales\DemoDataInstaller\DemoDataInstaller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class DemoDataInstallerTest extends \PHPUnit_Framework_TestCase
{
    public function testNoDemoDataFound()
    {
        $structure = [
            'source' => [
                'out' => []
            ],
            'vendor' => [
                'demodata-directory' => []
            ]
        ];

        vfsStream::setup('root', null, $structure);
        $outPath = $this->getOutPath();
        $demoDataInstaller = $this->buildDemoDataInstaller();

        $this->assertSame(0, $demoDataInstaller->execute());
        $this->assertSame(0, $this->countFiles($outPath), 'Directory is not empty.');
    }

    public function testDemoDataExist()
    {
        $structure = [
            'source' => [
                'out' => [
                    'file1',
                    'file2',
                    'file3'
                ]
            ],
            'vendor' => [
                'demodata-directory' => []
            ]
        ];

        vfsStream::setup('root', null, $structure);
        $outPath = $this->getOutPath();
        $demoDataInstaller = $this->buildDemoDataInstaller();

        $this->assertSame(0, $demoDataInstaller->execute());
        $this->assertSame(3, $this->countFiles($outPath), 'Files have not been copied.');
    }

    public function testErrorOccurs()
    {
        $filesystem = $this->getMock('Filesystem', ['mirror']);
        $filesystem->expects($this->any())->method('mirror')->willThrowException(new IOException('Test'));
        $facts = $this->getMock('Facts', ['getVendorPath', 'getOutPath']);
        $demoDataPathSelector = $this->getMock('DemodataPathSelector', ['getPath']);

        $demoDataInstaller = new DemoDataInstaller($facts, $demoDataPathSelector, $filesystem);

        $this->assertSame(1, $demoDataInstaller->execute());
    }

    private function getOutPath()
    {
        return vfsStream::url('root/source/out');
    }

    private function buildDemoDataInstaller()
    {
        $outPath = $this->getOutPath();
        $demoDataPath = vfsStream::url('root/vendor/demodata-directory');

        $filesystem = new Filesystem();

        $facts = $this->getMock('Facts', ['getVendorPath', 'getOutPath']);
        $facts->expects($this->any())->method('getOutPath')->willReturn($outPath);

        $demoDataPathSelector = $this->getMock('DemodataPathSelector', ['getPath']);
        $demoDataPathSelector->expects($this->any())->method('getPath')->willReturn($demoDataPath);

        return new DemoDataInstaller($facts, $demoDataPathSelector, $filesystem);
    }

    private function countFiles($path)
    {
        return count(array_diff(scandir($path), ['.', '..']));
    }
}
