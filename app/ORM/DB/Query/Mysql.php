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

namespace Pim\ORM\DB\Query;

use Espo\ORM\DB\Query\Mysql as EspoMysql;
use Espo\ORM\IEntity;
use Pim\Entities\ProductAttributeValue as Entity;

/**
 * Class of Mysql
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Mysql extends EspoMysql
{

    /**
     * Get where
     *
     * @param IEntity $entity
     * @param array   $whereClause
     * @param string  $sqlOp
     * @param array   $params
     * @param int     $level
     *
     * @return string
     */
    public function getWhere(IEntity $entity, $whereClause, $sqlOp = 'AND', &$params = array(), $level = 0)
    {
        // prepare result
        $result = parent::getWhere($entity, $whereClause, $sqlOp, $params, $level);

        /**
         * Injection for Attribute filtering
         */
        if (get_class($entity) == Entity::class) {
            // prepare numeric
            $result = $this->prepareNumeric($result);
        }

        return $result;
    }

    /**
     * Prepare for numeric
     *
     * @param string $result
     *
     * @return string
     */
    protected function prepareNumeric(string $result): string
    {
        // prepare pattern
        $pattern = '/^(product_attribute_value\.value)(>|<|>=|<=)\'(.*)\'$/';

        // prepare str
        $str = preg_replace('/\s+/', '', $result);

        if (preg_match_all($pattern, $str, $matches) && isset($matches[3][0]) && is_numeric($matches[3][0])) {
            $result = str_replace("'", "", $result);
        }

        return $result;
    }
}
