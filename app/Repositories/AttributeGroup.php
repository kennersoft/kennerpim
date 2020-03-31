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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class AttributeGroup
 *
 * @author r.ratsun@treolabs.com
 */
class AttributeGroup extends Base
{
    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    public function beforeRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        if ($relationName == 'attributes' && !empty($foreign->get('locale'))) {
            throw new BadRequest("Locale attribute can't be linked");
        }

        parent::beforeRelate($entity, $relationName, $foreign, $data, $options);
    }

    /**
     * @inheritDoc
     *
     * @throws BadRequest
     */
    public function beforeUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        if ($relationName == 'attributes' && !empty($foreign->get('locale'))) {
            throw new BadRequest("Locale attribute can't be unlinked");
        }

        parent::beforeUnrelate($entity, $relationName, $foreign, $options);
    }

    /**
     * @inheritDoc
     */
    public function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        if ($relationName == 'attributes' && !empty($foreign->get('isMultilang'))) {
            /** @var string $id */
            $id = $entity->get('id');

            /** @var string $parentId */
            $parentId = $foreign->get('id');

            $this->getEntityManager()->nativeQuery("UPDATE attribute SET attribute_group_id='$id' WHERE parent_id='$parentId' AND locale IS NOT NULL");
        }

        parent::afterRelate($entity, $relationName, $foreign, $data, $options);
    }

    /**
     * @inheritDoc
     */
    public function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        if ($relationName == 'attributes' && !empty($foreign->get('isMultilang'))) {
            /** @var string $parentId */
            $parentId = $foreign->get('id');

            $this->getEntityManager()->nativeQuery("UPDATE attribute SET attribute_group_id=null WHERE parent_id='$parentId' AND locale IS NOT NULL");
        }

        parent::afterUnrelate($entity, $relationName, $foreign, $options);
    }
}
