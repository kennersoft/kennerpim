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

namespace Pim\Entities;

use Espo\Core\Templates\Entities\Base;

/**
 * Class ProductFamily
 *
 * @author r.ratsun@treolabs.com
 */
class ProductFamily extends Base
{
    /**
     * @var string
     */
    protected $entityType = 'ProductFamily';

    /**
     * @return array
     */
    public function _getProductsIds(): array
    {
        $data = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->select(['id'])
            ->where(['productFamilyId' => $this->get('id')])
            ->find()
            ->toArray();

        return array_column($data, 'id');
    }
}
