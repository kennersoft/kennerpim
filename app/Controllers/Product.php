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

use Espo\Core\Exceptions;
use Slim\Http\Request;
use stdClass;

/**
 * Product controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Product extends AbstractController
{
    /**
     * Action add associated products
     *
     * @param array     $params
     * @param \stdClass $data
     * @param Request   $request
     *
     * @return bool
     *
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionAddAssociatedProducts(array $params, \stdClass $data, Request $request): bool
    {
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }
        if (empty($data->ids) || empty($data->foreignIds)) {
            throw new Exceptions\BadRequest();
        }
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->addAssociateProducts($data);
    }

    /**
     * Action remove associated products
     *
     * @param array     $params
     * @param \stdClass $data
     * @param Request   $request
     *
     * @return bool
     *
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionRemoveAssociatedProducts(array $params, \stdClass $data, Request $request): bool
    {
        if (!$request->isDelete()) {
            throw new Exceptions\BadRequest();
        }
        if (empty($data->ids) || empty($data->foreignIds)) {
            throw new Exceptions\BadRequest();
        }
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->removeAssociateProducts($data);
    }

    /**
     * Create product variant from main product
     *
     * @param array     $params
     * @param stdClass  $data
     * @param Request   $request
     *
     * @return array
     *
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionCreateVariantFromProduct(array $params, stdClass $data, Request $request): array
    {
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        if (empty($data->parent_product_id)) {
            throw new Exceptions\BadRequest("Bad parent_product_id");
        }

        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        return $this->getRecordService()->createProductVariantFromProduct($data->parent_product_id);
    }
}
