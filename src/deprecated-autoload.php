<?php

/**
 * This file is part of MetaModels/attribute_decimal.
 *
 * (c) 2012-2023 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_decimal
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2023 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_decimal/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

use MetaModels\AttributeDecimalBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeDecimalBundle\Attribute\Decimal;

// This hack is to load the "old locations" of the classes.
\spl_autoload_register(
    static function ($class) {
        static $classes = [
            'MetaModels\Attribute\Decimal\Decimal' => Decimal::class,
            'MetaModels\Attribute\Decimal\AttributeTypeFactory' => AttributeTypeFactory::class
        ];

        if (isset($classes[$class])) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @trigger_error('Class "' . $class . '" has been renamed to "' . $classes[$class] . '"', E_USER_DEPRECATED);
            // @codingStandardsIgnoreEnd

            if (!\class_exists($classes[$class])) {
                \spl_autoload_call($class);
            }

            \class_alias($classes[$class], $class);
        }
    }
);
