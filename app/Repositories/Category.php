<?php
/**
 * Pim
 * Free Extension
 * Copyright (c) 2020 Kenner Soft Service GmbH
 * Website: https://kennersoft.de
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "KennerPIM"
 * word.
 */

declare(strict_types=1);

namespace Pim\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class Category
 *
 * @author Roman Ratsun <r.ratsun@treolabs.com>
 */
class Category extends Base
{
    /**
     * @inheritDoc
     */
    public function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($entity->get('_position'))) {
            $this->updateSortOrderInTree($entity);
        }

        parent::afterSave($entity, $options);
    }

    /**
     * @param Entity $entity
     */
    protected function updateSortOrderInTree(Entity $entity): void
    {
        // prepare sort order
        $sortOrder = 0;

        // prepare data
        $data = [];

        if ($entity->get('_position') == 'after') {
            // prepare sort order
            $sortOrder = $this->select(['sortOrder'])->where(['id' => $entity->get('_target')])->findOne()->get('sortOrder');

            // get collection
            $data = $this
                ->select(['id'])
                ->where(
                    [
                        'id!='             => [$entity->get('_target'), $entity->get('id')],
                        'sortOrder>='      => $sortOrder,
                        'categoryParentId' => $entity->get('categoryParentId')
                    ]
                )
                ->order('sortOrder')
                ->find()
                ->toArray();

            // increase sort order
            $sortOrder = $sortOrder + 10;

        } elseif ($entity->get('_position') == 'inside') {
            // get collection
            $data = $this
                ->select(['id'])
                ->where(
                    [
                        'id!='             => $entity->get('id'),
                        'sortOrder>='      => $sortOrder,
                        'categoryParentId' => $entity->get('categoryParentId')
                    ]
                )
                ->order('sortOrder')
                ->find()
                ->toArray();
        }

        // prepare data
        $data = array_merge([$entity->get('id')], array_column($data, 'id'));

        // prepare sql
        $sql = '';
        foreach ($data as $id) {
            // prepare sql
            $sql .= "UPDATE category SET sort_order=$sortOrder WHERE id='$id';";

            // increase sort order
            $sortOrder = $sortOrder + 10;
        }

        // execute sql
        $this->getEntityManager()->nativeQuery($sql);
    }
}
