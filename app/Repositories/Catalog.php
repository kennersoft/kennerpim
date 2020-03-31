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

namespace Pim\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Catalog repository
 *
 * @author r.ratsun@treolabs.com
 */
class Catalog extends Base
{
    /**
     * @param string $catalogId
     * @param string $categoryId
     */
    public function unrelateProductsCategories(string $catalogId, string $categoryId): void
    {
        $ids = $this
            ->getEntityManager()
            ->nativeQuery(
                "SELECT pcl.id 
                     FROM product_category_linker pcl 
                         JOIN product p ON p.id=pcl.product_id AND p.deleted=0 
                         JOIN category c ON c.id=pcl.category_id AND c.deleted=0 
                     WHERE pcl.deleted=0 
                       AND p.catalog_id=:id 
                       AND c.category_route LIKE :likeRoute",
                [
                    'id'        => $catalogId,
                    'likeRoute' => "%|$categoryId|%",
                ]
            )
            ->fetchAll(\PDO::FETCH_COLUMN);

        $this
            ->getEntityManager()
            ->nativeQuery("UPDATE product_category_linker SET deleted=1 WHERE id IN ('" . implode("','", $ids) . "')");
    }

    /**
     * @inheritDoc
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        /** @var string $id */
        $id = $entity->get('id');

        // remove catalog products
        $this->getEntityManager()->nativeQuery("UPDATE product SET deleted=1 WHERE catalog_id='$id'");

        parent::afterRemove($entity, $options);
    }

    /**
     * @inheritDoc
     */
    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        parent::afterUnrelate($entity, $relationName, $foreign, $options);

        if ($relationName == 'categories') {
            $this->unrelateProductsCategories((string)$entity->get('id'), is_string($foreign) ? $foreign : (string)$foreign->get('id'));
        }
    }
}
