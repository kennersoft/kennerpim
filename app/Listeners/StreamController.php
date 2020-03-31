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

namespace Pim\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class StreamController
 *
 * @author r.zablodskiy@treolabs.com
 */
class StreamController extends AbstractListener
{
    /**
     * After action list
     *
     * @param Event $event
     */
    public function afterActionList(Event $event)
    {
        $result = $event->getArgument('result');

        switch ($event->getArgument('params')['scope']) {
            case 'User':
                $result = $this->prepareDataForUserStream($result);
                break;
            case 'Product':
                $result = $this->injectAttributeType($result);
                break;
        }

        $event->setArgument('result', $result);
    }

    /**
     * Inject attribute type in data
     *
     * @param array $result
     *
     * @return array
     */
    protected function injectAttributeType(array $result): array
    {
        if (isset($result['list']) && is_array($result['list'])) {
            if (!empty($attributes = $this->getAttributesType(array_column($result['list'], 'attributeId')))) {
                foreach ($result['list'] as $key => $item) {
                    if (isset($attributes[$item['attributeId']])) {
                        $result['list'][$key]['attributeType'] = $attributes[$item['attributeId']];
                        if ($result["list"][$key]["attributeType"] === 'image') {
                            foreach ($result['list'][$key]['data']->fields as $field) {
                                $becameValue = $result["list"][$key]["data"]->attributes->became->{$field};
                                $result["list"][$key]["data"]->attributes->became->{$field . 'Id'} = $becameValue;
                                unset ($result["list"][$key]["data"]->attributes->became->{$field});

                                $wasValue = $result["list"][$key]["data"]->attributes->was->{$field};
                                $result["list"][$key]["data"]->attributes->was->{$field . 'Id'} = $wasValue;
                                unset ($result["list"][$key]["data"]->attributes->was->{$field});
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Prepare data for user stream panel in dashlet
     *
     * @param array $result
     *
     * @return array
     */
    protected function prepareDataForUserStream(array $result): array
    {
        if (!empty($result['list'])) {
            // prepare notes ids
            $noteIds = array_column($result['list'], 'id');

            if (!empty($noteIds)) {
                // get notes attributeId field
                $items = $this
                    ->getEntityManager()
                    ->getRepository('Note')
                    ->select(['id', 'attributeId'])
                    ->where(['id' => $noteIds])
                    ->find()
                    ->toArray();

                if (!empty($items)) {
                    $items = array_column($items, 'attributeId', 'id');

                    // set attributeId field where needed in result
                    foreach ($result['list'] as $key => $value) {
                        if (isset($items[$value['id']])) {
                            $result['list'][$key]['attributeId'] = $items[$value['id']];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    protected function getAttributesType(array $ids): array
    {
        $result = [];

        $attributes = $this->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['id' => $ids])
            ->find();

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                if (!empty($attribute->get('attribute'))) {
                    $result[$attribute->get('id')] = $attribute->get('attribute')->get('type');
                }
            }
        }

        return $result;
    }
}
