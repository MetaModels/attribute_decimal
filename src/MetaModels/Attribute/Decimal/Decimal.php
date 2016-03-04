<?php

/**
 * This file is part of MetaModels/attribute_decimal.
 *
 * (c) 2012-2015 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeDecimal
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Andreas Isaak <andy.jared@googlemail.com>
 * @author     Cliff Parnitzky <github@cliff-parnitzky.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Attribute\Decimal;

use MetaModels\Attribute\BaseSimple;

/**
 * This is the MetaModelAttribute class for handling decimal fields.
 */
class Decimal extends BaseSimple
{
    /**
     * {@inheritDoc}
     */
    public function getSQLDataType()
    {
        return 'double NULL default NULL';
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            array(
                'mandatory',
                'filterable',
                'searchable',
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType']    = 'text';
        $arrFieldDef['eval']['rgxp'] = 'digit';

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function filterGreaterThan($varValue, $blnInclusive = false)
    {
        return $this->getIdsFiltered($varValue, ($blnInclusive) ? '>=' : '>');
    }

    /**
     * {@inheritdoc}
     */
    public function filterLessThan($varValue, $blnInclusive = false)
    {
        return $this->getIdsFiltered($varValue, ($blnInclusive) ? '<=' : '<');
    }

    /**
     * {@inheritdoc}
     */
    public function filterNotEqual($varValue)
    {
        return $this->getIdsFiltered($varValue, '!=');
    }

    /**
     * Search all items that match the given expression.
     *
     * Base implementation, perform string matching search.
     * The standard wildcards * (many characters) and ? (a single character) are supported.
     *
     * @param string $strPattern The text to search for. This may contain wildcards.
     *
     * @return int[] the ids of matching items.
     */
    public function searchFor($strPattern)
    {
        // If search with wildcard => parent implementation with "LIKE" search.
        if (false !== strpos($strPattern, '*') || false !== strpos($strPattern, '?')) {
            return parent::searchFor($strPattern);
        }

        // Not with wildcard but also not numeric, impossible to get decimal results.
        if (!is_numeric($strPattern)) {
            return array();
        }

        // Do a simple search on given column.
        $query = $this->getMetaModel()->getServiceContainer()->getDatabase()
            ->prepare(
                sprintf(
                    'SELECT id FROM %s WHERE %s=?',
                    $this->getMetaModel()->getTableName(),
                    $this->getColName()
                )
            )
            ->execute($strPattern);

        return $query->fetchEach('id');
    }
    
    /**
     * Filter all values by specified operation.
     *
     * @param int    $varValue     The value to use as upper end.
     *
     * @param string $strOperation The specified operation like greater than, lower than etc.
     *
     * @return string[] The list of item ids of all items matching the condition.
     */
    private function getIdsFiltered($varValue, $strOperation)
    {
        $strSql = sprintf(
            'SELECT id FROM %s WHERE %s %s %f',
            $this->getMetaModel()->getTableName(),
            $this->getColName(),
            $strOperation,
            floatval($varValue)
        );

        $objIds = $this->getMetaModel()->getServiceContainer()->getDatabase()->execute($strSql);

        return $objIds->fetchEach('id');
    }        
}
