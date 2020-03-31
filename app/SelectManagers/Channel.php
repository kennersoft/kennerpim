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

namespace Pim\SelectManagers;

use Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class Channel
 *
 * @author r.ratsun@treolabs.com
 */
class Channel extends AbstractSelectManager
{
    /**
     * NotLinkedWithPriceProfile filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithPriceProfile(&$result)
    {
        if (!empty($priceProfileId = (string)$this->getSelectCondition('notLinkedWithPriceProfile'))) {
            // get channel related with product
            $channel = $this->getEntityManager()
                ->getRepository('Channel')
                ->distinct()
                ->join('priceProfiles')
                ->where(['priceProfiles.id' => $priceProfileId])
                ->find();

            // set filter
            foreach ($channel as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row->get('id')
                ];
            }
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotAllowedForProduct(array &$result)
    {
        $data = (array)$this->getSelectCondition('notAllowedForProduct');

        if (isset($data['productId']) && isset($data['attributeId'])) {
            $ids = $this
                ->getEntityManager()
                ->nativeQuery(
                    "SELECT id
                     FROM channel
                     WHERE deleted=0
                       AND id IN (SELECT channel_id FROM product_channel WHERE product_id=:productId AND deleted=0)
                       AND id NOT IN (SELECT channel_id FROM product_attribute_value_channel WHERE deleted=0 AND product_attribute_value_id IN (SELECT id FROM product_attribute_value WHERE deleted=0 AND product_id=:productId AND attribute_id=:attributeId))",
                    ['productId' => $data['productId'], 'attributeId' => $data['attributeId']]
                )
                ->fetchAll(\PDO::FETCH_COLUMN);

            $result['whereClause'][] = [
                'id' => $ids
            ];
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedWithAttributesInProductFamily(array &$result)
    {
        $data = (array)$this->getSelectCondition('notLinkedWithAttributesInProductFamily');

        if (isset($data['productFamilyId']) && isset($data['attributeId'])) {
            $channels = $this
                ->getEntityManager()
                ->getRepository('Channel')
                ->select(['id'])
                ->distinct()
                ->join(['productFamilyAttributes'])
                ->where([
                    'productFamilyAttributes.attributeId' => $data['attributeId'],
                    'productFamilyAttributes.productFamilyId' => $data['productFamilyId']
                ])
                ->find()
                ->toArray();

            $result['whereClause'][] = [
                'id!=' => !empty($channels) ? array_column($channels, 'id') : []
            ];
        }
    }
}
