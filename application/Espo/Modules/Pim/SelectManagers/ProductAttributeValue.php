<?php
/**
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
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

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * ProductAttributeValue select manager
 *
 * @author r.zablodskiy@treolabs.com
 */
class ProductAttributeValue extends AbstractSelectManager
{
    /**
     * @inheritdoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        $arrayWhereTypes = ['arrayAnyOf', 'arrayNoneOf', 'arrayIsEmpty', 'arrayIsNotEmpty'];

        if (isset($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $k => $v) {
                if (is_array($v['value']) && !empty($v['value'])) {
                    foreach ($v['value'] as $key => $value) {
                        if (isset($value['type']) && in_array($value['type'], $arrayWhereTypes)) {
                            $params['where'][$k]['value'][$key] = $this->prepareWhere($value);
                        }
                    }
                }
            }
        }

        return parent::getSelectParams($params, $withAcl, $checkWherePermission);
    }

    /**
     * Prepare where for array attributes
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareWhere(array $data): array
    {
        $where = [];

        switch ($data['type']) {
            case 'arrayAnyOf':
            case 'arrayNoneOf':
                $where = [
                    'type'  => $data['type'] == 'arrayAnyOf' ? 'or' : 'and',
                    'value' => []
                ];

                foreach ($data['value'] as $value) {
                    $where['value'][] = [
                        'type'      => $data['type'] == 'arrayAnyOf' ? 'contains' : 'notContains',
                        'attribute' => 'value',
                        'value'     => "\"$value\""
                    ];
                }
                break;
            case 'arrayIsEmpty':
                $where = [
                    'type'  => 'or',
                    'value' => [
                        [
                            'type'      => 'isNull',
                            'attribute' => 'value'
                        ],
                        [
                            'type'      => 'equals',
                            'attribute' => 'value',
                            'value'     => '[]'
                        ]
                    ]
                ];
                break;
            case 'arrayIsNotEmpty':
                $where = [
                    'type'  => 'and',
                    'value' => [
                        [
                            'type'      => 'isNotNull',
                            'attribute' => 'value'
                        ],
                        [
                            'type'      => 'notEquals',
                            'attribute' => 'value',
                            'value'     => '[]'
                        ]
                    ]
                ];
                break;
        }

        return $where;
    }
}