<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package    MetaModels
 * @subpackage Tests
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Test\Attribute\Decimal;

use Contao\Database;
use MetaModels\Attribute\Decimal\Decimal;
use MetaModels\MetaModelsServiceContainer;

/**
 * Unit tests to test class Decimal.
 */
class DecimalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Mock the Contao database.
     *
     * @param string     $expectedQuery The query to expect.
     *
     * @param null|array $result        The resulting datasets.
     *
     * @return Database|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDatabase($expectedQuery = '', $result = null)
    {
        $mockDb = $this
            ->getMockBuilder('Contao\Database')
            ->disableOriginalConstructor()
            ->setMethods(array('__destruct'))
            ->getMockForAbstractClass();

        $mockDb->method('createStatement')->willReturn(
            $statement = $this
                ->getMockBuilder('Contao\Database\Statement')
                ->disableOriginalConstructor()
                ->setMethods(array('debugQuery', 'createResult'))
                ->getMockForAbstractClass()
        );

        if (!$expectedQuery) {
            $statement->expects($this->never())->method('prepare_query');

            return $mockDb;
        }

        $statement
            ->expects($this->once())
            ->method('prepare_query')
            ->with($expectedQuery)
            ->willReturnArgument(0);

        if ($result === null) {
            $result = array('ignored');
        } else {
            $result = (object) $result;
        }

        $statement->method('execute_query')->willReturn($result);
        $statement->method('createResult')->willReturnCallback(
            function ($resultData) {
                $index = 0;

                $resultData = (array) $resultData;

                $resultSet = $this
                    ->getMockBuilder('Contao\Database\Result')
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();

                $resultSet->method('fetch_row')->willReturnCallback(function () use (&$index, $resultData) {
                    return array_values($resultData[$index++]);
                });
                $resultSet->method('fetch_assoc')->willReturnCallback(function () use (&$index, $resultData) {
                    if (!isset($resultData[$index])) {
                        return false;
                    }
                    return $resultData[$index++];
                });
                $resultSet->method('num_rows')->willReturnCallback(function () use ($index, $resultData) {
                    return count($resultData);
                });
                $resultSet->method('num_fields')->willReturnCallback(function () use ($index, $resultData) {
                    return count($resultData[$index]);
                });
                $resultSet->method('fetch_field')->willReturnCallback(function ($field) use ($index, $resultData) {
                    $data = array_values($resultData[$index]);
                    return $data[$field];
                });
                $resultSet->method('data_seek')->willReturnCallback(function ($newIndex) use (&$index, $resultData) {
                    $index = $newIndex;
                });

                return $resultSet;
            }
        );

        return $mockDb;
    }

    /**
     * Mock a MetaModel.
     *
     * @param string   $language         The language.
     *
     * @param string   $fallbackLanguage The fallback language.
     *
     * @param Database $database         The database to use.
     *
     * @return \MetaModels\IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage, $database)
    {
        $metaModel = $this->getMock(
            'MetaModels\MetaModel',
            array(),
            array(array())
        );

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue('mm_unittest'));

        $metaModel
            ->expects($this->any())
            ->method('getActiveLanguage')
            ->will($this->returnValue($language));

        $metaModel
            ->expects($this->any())
            ->method('getFallbackLanguage')
            ->will($this->returnValue($fallbackLanguage));

        $serviceContainer = new MetaModelsServiceContainer();
        $serviceContainer->setDatabase($database);

        $metaModel
            ->method('getServiceContainer')
            ->willReturn($serviceContainer);

        return $metaModel;
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $text = new Decimal($this->mockMetaModel('en', 'en', $this->mockDatabase()));
        $this->assertInstanceOf('MetaModels\Attribute\Decimal\Decimal', $text);
    }

    /**
     * Test provider for testSearchFor().
     *
     * @return array
     */
    public function searchForProvider()
    {
        return array(
            array('10'),
            array('10.0'),
            array(10),
            array(10.5),
        );
    }

    /**
     * Test the searchFor() method.
     *
     * @param string|int|float $value The value to search.
     *
     * @return void
     *
     * @dataProvider searchForProvider
     */
    public function testSearchFor($value)
    {
        $decimal = new Decimal(
            $this->mockMetaModel(
                'en',
                'en',
                $this->mockDatabase(
                    'SELECT id FROM mm_unittest WHERE test=?',
                    array(array('id' => 1), array('id' => 2))
                )
            ),
            array('colname' => 'test')
        );

        $this->assertEquals(array(1, 2), $decimal->searchFor($value));
    }

    /**
     * Test the searchFor() method with a wildcard.
     *
     * @return void
     */
    public function testSearchForWithWildcard()
    {
        $decimal = new Decimal(
            $this->mockMetaModel(
                'en',
                'en',
                $this->mockDatabase(
                    'SELECT id FROM mm_unittest WHERE test LIKE ?',
                    array(array('id' => 1), array('id' => 2))
                )
            ),
            array('colname' => 'test')
        );

        $this->assertEquals(array(1, 2), $decimal->searchFor('10*'));
    }

    /**
     * Test the searchFor() method with a non numeric value.
     *
     * @return void
     */
    public function testSearchForWithNonNumeric()
    {
        $decimal = new Decimal(
            $this->mockMetaModel(
                'en',
                'en',
                $this->mockDatabase()
            ),
            array('colname' => 'test')
        );

        $this->assertEquals(array(), $decimal->searchFor('abc'));
    }
}
