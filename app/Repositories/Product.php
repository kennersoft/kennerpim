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

namespace Pim\Repositories;

use Espo\ORM\Entity;
use Espo\Core\Utils\Util;

/**
 * Class Product
 *
 * @author r.ratsun@treolabs.com
 */
class Product extends AbstractRepository
{
    /**
     * @return array
     */
    public function getInputLanguageList(): array
    {
        return $this->getConfig()->get('inputLanguageList', []);
    }

    /**
     * @inheritdoc
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        // save attributes
        $this->saveAttributes($entity);

        // parent action
        parent::afterSave($entity, $options);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function saveAttributes(Entity $product): bool
    {
        if (!empty($product->productAttribute)) {
            $data = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(
                    [
                        'productId'   => $product->get('id'),
                        'attributeId' => array_keys($product->productAttribute),
                        'scope'       => 'Global'
                    ]
                )
                ->find();

            // prepare exists
            $exists = [];
            if (count($data) > 0) {
                foreach ($data as $v) {
                    $exists[$v->get('attributeId')] = $v;
                }
            }

            foreach ($product->productAttribute as $attributeId => $values) {
                if (isset($exists[$attributeId])) {
                    $entity = $exists[$attributeId];
                } else {
                    $entity = $this->getEntityManager()->getEntity('ProductAttributeValue');
                    $entity->set('productId', $product->get('id'));
                    $entity->set('attributeId', $attributeId);
                    $entity->set('scope', 'Global');
                }

                foreach ($values['locales'] as $locale => $value) {
                    if ($locale == 'default') {
                        $entity->set('value', $value);
                    } else {
                        // prepare locale
                        $locale = Util::toCamelCase(strtolower($locale), '_', true);
                        $entity->set("value$locale", $value);
                    }
                }

                if (isset($values['data']) && !empty($values['data'])) {
                    foreach ($values['data'] as $field => $item) {
                        $entity->set($field, $item);
                    }
                }

                $this->getEntityManager()->saveEntity($entity);
            }
        }

        return true;
    }
}
