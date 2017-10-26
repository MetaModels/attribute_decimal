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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Test\Attribute\Decimal;

use Contao\Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use MetaModels\Attribute\Decimal\Decimal;
use MetaModels\Helper\TableManipulator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class Decimal.
 */
class DecimalTest extends TestCase
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
     * @return \MetaModels\IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockBuilder('MetaModels\IMetaModel')->getMock();

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

        return $metaModel;
    }

    /**
     * Mock the Contao database.
     *
     * @param string|null   expectedQuery The query to expect.
     *
     * @param callable|null $callback     Callback which gets mocked statement passed.
     *
     * @return Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConnection(callable $callback = null, $expectedQuery = null, $queryMethod = 'prepare')
    {
        $mockDb = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $statement = $this
            ->getMockBuilder(Statement::class)
            ->getMock();

        $mockDb->method('prepare')->willReturn($statement);
        $mockDb->method('query')->willReturn($statement);

        if ($callback) {
            call_user_func($callback, $statement);
        }

        if (!$expectedQuery || $expectedQuery === 'prepare') {
            $mockDb->expects($this->never())->method('query');
        }

        if (!$expectedQuery || $expectedQuery === 'query') {
            $mockDb->expects($this->never())->method('prepare');
        }

        if (!$expectedQuery) {
            return $mockDb;
        }

        $mockDb
            ->expects($this->once())
            ->method($queryMethod)
            ->with($expectedQuery);

        if ($queryMethod === 'prepare') {
            $statement
                ->expects($this->once())
                ->method('execute')
                ->willReturn(true);
        }

        return $mockDb;
    }

    /**
     * Mock the table manipulator.
     *
     * @param Connection $connection The database connection mock.
     *
     * @return TableManipulator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiation()
    {
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $text = new Decimal($this->mockMetaModel('en', 'en'), [], $connection, $manipulator);
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
        $connection   = $this->mockConnection();
        $manipulator  = $this->mockTableManipulator($connection);
        $queryBuilder = new QueryBuilder($connection);

        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $statement = $this->getMockBuilder(Statement::class)->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN, 'id')
            ->willReturn(array(1,2));

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

        $decimal = new Decimal(
            $this->mockMetaModel(
                'en',
                'en'
            ),
            array('colname' => 'test'),
            $connection,
            $manipulator
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
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $queryBuilder = new QueryBuilder($connection);

        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $statement = $this->getMockBuilder(Statement::class)->getMock();
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN, 'id')
            ->willReturn(array(1,2));

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

        $decimal = new Decimal(
            $this->mockMetaModel('en', 'en'),
            array('colname' => 'test'),
            $connection,
            $manipulator
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
        $connection  = $this->mockConnection();
        $manipulator = $this->mockTableManipulator($connection);

        $decimal = new Decimal(
            $this->mockMetaModel(
                'en',
                'en'
            ),
            array('colname' => 'test'),
            $connection,
            $manipulator
        );

        $this->assertEquals(array(), $decimal->searchFor('abc'));
    }
}
