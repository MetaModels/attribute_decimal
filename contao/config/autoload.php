<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package     MetaModels
 * @subpackage  AttributeDecimal
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Andreas Isaak <andy.jared@googlemail.com>
 * @author      Cliff Parnitzky <github@cliff-parnitzky.de>
 * @author      Sven Baumann <baumann.sv@gmail.com>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

/**
 * Register the templates
 */
\Contao\TemplateLoader::addFiles(
    [
        'mm_attr_decimal' => 'system/modules/metamodelsattribute_decimal/templates',
    ]
);
