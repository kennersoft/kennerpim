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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "KennerPIM"
 * word.
 */

namespace Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class BrandController
 *
 * @author r.ratsun@treolabs.com
 */
class BrandController extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeActionDelete(Event $event)
    {
        // get data
        $data = $event->getArguments();

        if (empty($data['data']->force) && !empty($data['params']['id'])) {
            $this->validRelationsWithProduct([$data['params']['id']]);
        }
    }

    /**
     * @param Event $event
     */
    public function beforeActionMassDelete(Event $event)
    {
        // get data
        $data = $event->getArgument('data');

        if (empty($data->force) && !empty($data->ids)) {
            $this->validRelationsWithProduct($data->ids);
        }
    }


    /**
     * @param array $idsBrand
     *
     * @throws BadRequest
     */
    protected function validRelationsWithProduct(array $idsBrand): void
    {
        if ($this->hasProducts($idsBrand)) {
            throw new BadRequest(
                $this->getLanguage()->translate(
                    'Brand is used in products. Please, update products first',
                    'exceptions',
                    'Brand'
                )
            );
        }
    }

    /**
     * Is brand used in Products
     *
     * @param array $idsBrand
     *
     * @return bool
     */
    protected function hasProducts(array $idsBrand): bool
    {
        $count = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['brandId' => $idsBrand])
            ->count();

        return !empty($count);
    }
}
