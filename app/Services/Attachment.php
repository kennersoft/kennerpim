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

namespace Pim\Services;

use Espo\ORM\Entity;

/**
 * Class Attachment
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Attachment extends \Treo\Services\Attachment
{
    /**
     * @inheritdoc
     */
    public function createEntity($data)
    {
        if (!empty($data->file)
            && isset($data->relatedType)
            && $data->relatedType == 'productAttributesGrid') {
            return $this->createProductAttributeAttachment($data);
        }

        return parent::createEntity($data);
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
     * @param \stdClass $data
     *
     * @return Entity
     */
    protected function createProductAttributeAttachment(\stdClass $data): Entity
    {
        // prepare service
        $recordService = $this->getInjection('serviceFactory')->create('Record');
        $recordService->setEntityType('Attachment');

        // prepare contents
        $arr = explode(',', $data->file);
        $contents = '';
        if (count($arr) > 1) {
            $contents = $arr[1];
        }
        $contents = base64_decode($contents);
        $data->contents = $contents;

        // create entity
        $entity = $recordService->createEntity($data);

        if (!empty($data->file)) {
            $entity->clear('contents');
        }

        return $entity;
    }
}
