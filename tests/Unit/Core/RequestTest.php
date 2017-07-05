<?php
/**
 * This file is part of OXID eShop Community Edition.
 *
 * OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2017
 * @version       OXID eShop CE
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

class RequestTest extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * @var \OxidEsales\Eshop\Core\Request
     */
    protected $request;

    public function setUp()
    {
        parent::setUp();
        $this->request = oxNew(\OxidEsales\EshopCommunity\Core\Request::class);
    }

    public function testGetRequestEscapedParameter()
    {
        $_POST['postKey'] = '&test';

        $this->assertSame('&amp;test', $this->request->getRequestEscapedParameter('postKey'));
    }

    public function testGetRequestEscapedParameterWhenParameterNotFound()
    {
        $this->assertSame(null, $this->request->getRequestEscapedParameter('notExistingPostKey'));
    }

    public function testGetRequestEscapedParameterWhenParameterNotFoundAndDefaultValueIsProvided()
    {
        $this->assertSame('defaultValue', $this->request->getRequestEscapedParameter('notExistingPostKey', 'defaultValue'));
    }

    public function testGetRequestRawParameterFromPost()
    {
        $_POST['postKey'] = 'testValue';

        $this->assertSame('testValue', $this->request->getRequestParameter('postKey'));
    }

    public function testGetRequestRawParameterFromGet()
    {
        $_GET['getKey'] = 'testValue';

        $this->assertSame('testValue', $this->request->getRequestParameter('getKey'));
    }

    public function testGetRequestRawParameterWhenRequestParametersNotFound()
    {
        $this->assertSame('defaultValue', $this->request->getRequestParameter('nonExisting', 'defaultValue'));
    }

    /**
     * Testing request uri getter
     */
    public function testGetRequestUrl()
    {
        $_SERVER["REQUEST_METHOD"] = 'GET';
        $_SERVER['REQUEST_URI'] = 'test.php?param1=value1&param2=value2';

        $this->assertEquals('index.php?param1=value1&amp;param2=value2', $this->request->getRequestUrl());
    }

    /**
     * Testing request uri getter
     */
    public function testGetRequestUrlEmptyParams()
    {
        $_SERVER["REQUEST_METHOD"] = 'GET';
        $_SERVER['REQUEST_URI'] = $sUri = '/shop/';

        $this->assertEquals('', $this->request->getRequestUrl());
    }

    /**
     * Testing request uri getter
     */
    public function testGetRequestUrlSubfolder()
    {
        $_SERVER["REQUEST_METHOD"] = 'GET';
        $_SERVER['SCRIPT_URI'] = '/shop/?cl=details';

        $this->assertEquals('index.php?cl=details', $this->request->getRequestUrl());
    }

    /**
     * Testing request removing sid from link
     */
    public function testGetRequestUrl_removingSID()
    {
        $_SERVER["REQUEST_METHOD"] = 'GET';
        $_SERVER['REQUEST_URI'] = 'test.php?param1=value1&sid=zzz&sysid=vvv&param2=ttt';
        $this->assertEquals('index.php?param1=value1&amp;sysid=vvv&amp;param2=ttt', $this->request->getRequestUrl());

        $_SERVER['REQUEST_URI'] = 'test.php?sid=zzz&param1=value1&sysid=vvv&param2=ttt';
        $this->assertEquals('index.php?param1=value1&amp;sysid=vvv&amp;param2=ttt', $this->request->getRequestUrl());

        $_SERVER['REQUEST_URI'] = 'test.php?param1=value1&sysid=vvv&param2=ttt&sid=zzz';
        $this->assertEquals('index.php?param1=value1&amp;sysid=vvv&amp;param2=ttt', $this->request->getRequestUrl());
    }

    /**
     * @return array
     */
    public function providerCheckParamSpecialChars_newLineExist_newLineChanged()
    {
        return array(
            array("\r", '&#13;'),
            array("\n", '&#10;'),
            array("\r\n", '&#13;&#10;'),
            array("\n\r", '&#10;&#13;'),
        );
    }

    /**
     * @dataProvider providerCheckParamSpecialChars_newLineExist_newLineChanged
     */
    public function testCheckParamSpecialChars_newLineExist_newLineChanged($newLineCharacter, $escapedNewLineCharacter)
    {
        $anObject = new \stdClass();
        $anObject->xxx = "text" . $newLineCharacter;
        $anArray = array("text" . $newLineCharacter);
        $aString = "text" . $newLineCharacter;

        // test object
        $this->assertEquals($anObject, $this->request->replaceSpecialChars($anObject));
        $this->assertEquals($anObject, $this->request->checkParamSpecialChars($anObject));

        // test array
        $this->assertEquals(array("text" . $escapedNewLineCharacter), $this->request->replaceSpecialChars($anArray));
        $this->assertEquals(array("text" . $escapedNewLineCharacter), $this->request->checkParamSpecialChars($anArray));

        // test string
        $this->assertEquals("text" . $escapedNewLineCharacter, $this->request->replaceSpecialChars($aString));
        $this->assertEquals("text" . $escapedNewLineCharacter, $this->request->checkParamSpecialChars($aString));
    }

    /**
     * @return array
     */
    public function providerReplaceSpecialCharsAndCheckParamSpecialChars()
    {
        $stdClass = new \stdClass();
        $stdClass->xxx = 'yyy';

        return [
            'string'                       => [
                'expectedResult'       => '&amp;&#092;o&lt;x&gt;i&quot;&#039;d',
                'dataWithSpecialChars' => '&\\o<x>i"\'d' . chr(0),
                'raw'                  => null
            ],
            'array'                        => [
                'expectedResult'       => ["&amp;&#092;o&lt;x&gt;i&quot;&#039;d"],
                'dataWithSpecialChars' => ['&\\o<x>i"\'d' . chr(0)],
                'raw'                  => null
            ],
            'object'                       => [
                'expectedResult'       => $stdClass,
                'dataWithSpecialChars' => $stdClass,
                'raw'                  => null
            ],
            'returnRawValueForArrayValues' => [
                'expectedResult'       => [
                    'first'  => 'first char &',
                    'second' => 'second char &amp;',
                    'third'  => 'third char &'
                ],
                'dataWithSpecialChars' => [
                    'first'  => 'first char &',
                    'second' => 'second char &',
                    'third'  => 'third char &'
                ],
                'raw'                  => ['first', 'third']
            ],
            'arrayKeys1'                   => [
                'expectedResult'       => ['asd&amp;' => 'a%&amp;'],
                'dataWithSpecialChars' => ['asd&' => 'a%&'],
                'raw'                  => null
            ],
            'string2'                      => [
                'expectedResult'       => ['asd&amp;'],
                'dataWithSpecialChars' => ['asd&'],
                'raw'                  => null
            ]
        ];
    }

    /**
     * Both methods checkParamSpecialChars and replaceSpecialChars should behave the same, except that
     * replaceSpecialChars does not modify the parameter by reference.
     *
     * @dataProvider providerReplaceSpecialCharsAndCheckParamSpecialChars
     *
     */
    public function testReplaceSpecialCharsAndCheckParamSpecialChars($containerWithSpecialChars, $containerWithReplaceSpecialChars, $raw = null)
    {
        $this->assertEquals(
            $containerWithSpecialChars,
            $this->request->replaceSpecialChars($containerWithReplaceSpecialChars, $raw)
        );
        $this->assertEquals(
            $containerWithSpecialChars,
            $this->request->checkParamSpecialChars($containerWithReplaceSpecialChars, $raw)
        );
    }

    /*
     * Both methods checkParamSpecialChars and replaceSpecialChars should behave the same, except that
     * replaceSpecialChars does not modify the parameter by reference.
     */
    public function testReplaceSpecialCharsDoesNotModifyByReference()
    {
        $stringWithSpecialChars = '&\\o<x>i"\'d' . chr(0);
        $originalStringWithSpecialChars = '&\\o<x>i"\'d' . chr(0);

        $this->request->replaceSpecialChars($stringWithSpecialChars);
        $this->assertEquals($originalStringWithSpecialChars, $stringWithSpecialChars);
    }

    /*
     * Both methods checkParamSpecialChars and replaceSpecialChars should behave the same, except that
     * replaceSpecialChars does not modify the parameter by reference.
     */
    public function testCheckParamSpecialCharsDoesModifyByReference()
    {
        $stringWithSpecialChars = '&\\o<x>i"\'d' . chr(0);
        $stringWithReplacedSpecialChars = '&amp;&#092;o&lt;x&gt;i&quot;&#039;d';

        $this->request->checkParamSpecialChars($stringWithSpecialChars);
        $this->assertEquals($stringWithReplacedSpecialChars, $stringWithSpecialChars);
    }
}
