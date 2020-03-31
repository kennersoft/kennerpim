<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * KennerPIM is Pim-based Open Source application.
 * Copyright (C) 2020 KenerSoft Service GmbH
 * Website: https://kennersoft.de
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Pim\Services;

use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

/**
 * Class ProductFamily
 *
 * @author r.ratsun@treolabs.com
 */
class ProductFamily extends Base
{
    /**
     * Get count not empty product family attributes
     *
     * @param string $productFamilyId
     * @param string $attributeId
     *
     * @return int
     */
    public function getLinkedProductAttributesCount(string $productFamilyId, string $attributeId): int
    {
        // prepare result
        $count = 0;

        // if not empty productFamilyId and attributeId
        if (!empty($productFamilyId) && !empty($attributeId)) {
            // get count products
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(
                    [
                        'productFamilyId' => $productFamilyId,
                        'attributeId'     => $attributeId,
                        'value!='         => ['null', '', 0, '0', '[]']
                    ]
                )
                ->count();
        }

        return $count;
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('serviceFactory');
    }

    /**
     * @param Entity $entity
     * @param Entity $duplicatingEntity
     */
    protected function duplicateProductFamilyAttributes(Entity $entity, Entity $duplicatingEntity)
    {
        if (!empty($productFamilyAttributes = $duplicatingEntity->get('productFamilyAttributes')->toArray())) {
            // get service
            $service = $this->getInjection('serviceFactory')->create('ProductFamilyAttribute');

            foreach ($productFamilyAttributes as $productFamilyAttribute) {
                // prepare data
                $data = $service->getDuplicateAttributes($productFamilyAttribute['id']);
                $data->productFamilyId = $entity->get('id');

                // create entity
                $service->createEntity($data);
            }
        }
    }
}
