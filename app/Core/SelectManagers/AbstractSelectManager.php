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

namespace Pim\Core\SelectManagers;

use Espo\Core\Services\Base as BaseService;
use PDO;

/**
 * Class of AbstractSelectManager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractSelectManager extends \Treo\Core\SelectManagers\Base
{

    /**
     * @var array
     */
    protected $selectData = [];

    /**
     * Get select params
     *
     * @param array $params
     * @param bool  $withAcl
     * @param bool  $checkWherePermission
     *
     * @return array
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        // set select data
        $this->selectData = $params;

        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }

    /**
     * OnlyActive filter
     *
     * @param array $result
     */
    protected function boolFilterOnlyActive(&$result)
    {
        $result['whereClause'][] = array(
            'isActive' => true
        );
    }

    /**
     * NotEntity filter
     *
     * @param array $result
     */
    protected function boolFilterNotEntity(&$result)
    {
        foreach ($this->getSelectData('where') as $key => $row) {
            if ($row['type'] == 'bool' && !empty($row['data']['notEntity'])) {
                // prepare value
                $value = (array)$row['data']['notEntity'];
                // prepare where clause
                foreach ($value as $id) {
                    $result['whereClause'][] = [
                        'id!=' => (string)$id
                    ];
                }
            }
        }
    }

    /**
     * Get select data
     *
     * @param string $key
     *
     * @return array
     */
    protected function getSelectData($key = '')
    {
        $result = [];

        if (empty($key)) {
            $result = $this->selectData;
        } elseif (isset($this->selectData[$key])) {
            $result = $this->selectData[$key];
        }

        return $result;
    }

    /**
     * Is has bool filter with defined key name
     *
     * @param string $key
     *
     * @return bool
     */
    protected function hasBoolFilter(string $key): bool
    {
        // prepare result
        $result = false;

        foreach ($this->getSelectData('where') as $row) {
            if ($row['type'] == 'bool'
                && !empty($row['value'])
                && is_array($row['value'])
                && in_array($key, $row['value'])) {
                // prepare result
                $result = true;

                break;
            }
        }

        return $result;
    }

    /**
     * Get Condition for boolFilter
     *
     * @param string $filterName
     *
     * @return mixed
     */
    protected function getSelectCondition(string $filterName)
    {
        foreach ($this->getSelectData('where') as $key => $row) {
            if ($row['type'] == 'bool' && !empty($row['data'][$filterName])) {
                $condition = $row['data'][$filterName];
            }
        }

        return $condition ?? false;
    }

    /**
     * Create Service
     *
     * @param string $name
     *
     * @return BaseService
     */
    protected function createService(string $name): BaseService
    {
        return $this
            ->getEntityManager()
            ->getContainer()
            ->get('serviceFactory')
            ->create($name);
    }

    protected function fetchColumn(string $sql): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }
}
