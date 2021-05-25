<?php
/**
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 * Copyright (c) Kenner Soft Service GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Pim\Import\Types\Simple\FieldConverters;

use Import\Types\Simple\FieldConverters\AbstractConverter;

/**
 * Class FloatValue
 *
 * @author v.shamota@kennersoft.de
 */
class FloatValue extends AbstractConverter
{
    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter, string $decimalMark = '.')
    {
        $value = (!empty($config['column']) && $row[$config['column']] != '') ? $row[$config['column']] : $config['default'];

        if (!is_null($value) && filter_var($value, FILTER_VALIDATE_FLOAT, ["options" => ["default" => $decimalMark]]) === false) {
            throw new \Exception("Incorrect value for field '{$config['name']}'");
        }

        $value = str_replace($decimalMark, '.', $value);
        $inputRow->{$config['name']} = $value;
    }
}