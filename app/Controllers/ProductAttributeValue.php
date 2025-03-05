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

namespace Pim\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Controllers\Base;
use Slim\Http\Request;
use Espo\Core\Utils\Json;
use Treo\Core\EventManager\Event;
use Treo\Core\Utils\Util;

/**
 * Class ProductAttributeValue
 *
 * @author r.ratsun@treolabs.com
 */
class ProductAttributeValue extends Base
{
    /**
     * @ApiDescription(description="Mass update Product Attribute Values")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ProductAttributeValue/action/savePAVs")
     * @ApiReturn(sample="'bool'")
     *
     * @param array $params
     * @param array $data
     * @param \Treo\Core\Slim\Http\Request $request
     *
     * @throws BadRequest
     */
    public function actionSavePAVs(array $params, \stdClass $data, Request $request): bool
    {
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }
        if (!($data->attributes instanceof \stdClass) || !count(get_object_vars($data->attributes))) {
            throw new Exceptions\BadRequest();
        }
        if (!$this->getAcl()->check('ProductAttributeValue', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        $ids = [];
        foreach ($data->attributes as $id => $attr) {
            $attr = (array)$attr;
            $repository = $this->getEntityManager()->getRepository('ProductAttributeValue');
            $entity = $repository->where(['id' => $id])->findOne();
            if (!$entity) {
                $entity = $repository->get();
                $entity->id = Util::generateId();
            }
            if (is_array($attr['value'])) {
                $attr['value'] = Json::encode($attr['value']);
            }
            $entity->set($attr);
            $this->getEntityManager()->saveEntity($entity, ['skipMassUpdateHook' => true]);
            $ids[] = $id;
        }

        $this
            ->getContainer()
            ->get('eventManager')
            ->dispatch('ProductAttributeValueEntity', 'actionAfterSaveAttributeValues', new Event(['ids' => $ids]));

        return true;
    }
}
