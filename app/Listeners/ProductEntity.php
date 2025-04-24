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

namespace Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;
use Pim\Repositories\ProductAttributeValue;
use Treo\Core\EventManager\Event;
use Pim\Entities\Channel;
use Treo\Core\Utils\Util;

/**
 * Class ProductEntity
 *
 * @package Pim\Listeners
 * @author  m.kokhanskyi@treolabs.com
 */
class ProductEntity extends AbstractEntityListener
{
    /**
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeSave(Event $event)
    {
        // get entity
        $entity = $event->getArgument('entity');

        // is sku valid
        if (!$this->isSkuUnique($entity)) {
            throw new BadRequest($this->exception('Product with such SKU already exist'));
        }

        if (!$entity->isNew() && $entity->isAttributeChanged('type')) {
            throw new BadRequest($this->exception('You can\'t change field of Type'));
        }
    }

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // get entity
        $entity = $event->getArgument('entity');

        // get options
        $options = $event->getArgument('options');

        $skipUpdate = empty($entity->skipUpdateProductAttributesByProductFamily)
            && empty($options['skipProductFamilyHook']);

        if ($skipUpdate && empty($entity->isDuplicate) && $entity->isAttributeChanged('productFamilyId')) {
            $this->updateProductAttributesByProductFamily($entity, $options);
        }

        $this->getServiceFactory()->create('Product')->runUpdateVariantsAfterProductSave($entity);
    }

    /**
     * @param Event $event
     */
    public function afterUnrelate(Event $event)
    {
        //set default value in isActive for channel after deleted link
        if ($event->getArgument('relationName') == 'channels' && $event->getArgument('foreign') instanceof Channel) {
            $dataEntity = new \StdClass();
            $dataEntity->entityName = 'Product';
            $dataEntity->entityId = $event->getArgument('entity')->get('id');
            $dataEntity->value = (int)!empty(
            $event
                ->getArgument('entity')
                ->getRelations()['channels']['additionalColumns']['isActive']['default']
            );

            $this
                ->getService('Channel')
                ->setIsActiveEntity($event->getArgument('foreign')->get('id'), $dataEntity, true);
        }
    }

    /**
     * Before action delete
     *
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        $id = $event->getArgument('entity')->id;
        $this->removeProductAttributeValue($id);
    }

    /**
     * Before action delete
     *
     * @param Event $event
     * @throws BadRequest
     */
    public function beforeRemove(Event $event)
    {
        $entity = $event->getArgument('entity');

        if (!empty($entity) && empty($entity->get('parentProductId'))) {
            if ($entity->get('productVariants')->count() > 0) {
                throw new BadRequest($this->exception('cantRemoveProductWithVariants'));
            }
        }
    }

    /**
     * @param string $id
     */
    protected function removeProductAttributeValue(string $id)
    {
        $productAttributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['productId' => $id])
            ->find();

        foreach ($productAttributes as $attr) {
            $this->getEntityManager()->removeEntity($attr, ['skipProductAttributeValueHook' => true]);
        }
    }

    /**
     * @param Entity $product
     * @param string $field
     *
     * @return bool
     */
    protected function isSkuUnique(Entity $product): bool
    {
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['sku' => $product->get('sku'), 'catalogId' => $product->get('catalogId')])
            ->find();

        if (count($products) > 0) {
            foreach ($products as $item) {
                if ($item->get('id') != $product->get('id')) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @return bool
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function updateProductAttributesByProductFamily(Entity $entity, array $options): bool
    {
        if (!$entity->isNew() && $entity->isAttributeChanged('productFamilyId')) {
            // unlink attributes from old product family
            $this
                ->getEntityManager()
                ->nativeQuery(
                    "UPDATE product_attribute_value SET product_family_attribute_id=NULL
                        WHERE product_id=:productId AND product_family_attribute_id IS NOT NULL AND deleted=0",
                    ['productId' => $entity->get('id')]
                );
        }

        if (empty($productFamily = $entity->get('productFamily'))) {
            return true;
        }

        // get product family attributes
        $productFamilyAttributes = $productFamily->get('productFamilyAttributes');

        if (count($productFamilyAttributes) > 0) {
            /** @var \Pim\Repositories\ProductAttributeValue $repository */
            $repository = $this->getEntityManager()->getRepository('ProductAttributeValue');

            foreach ($productFamilyAttributes as $productFamilyAttribute) {
                // base fields
                $productAttributeValueData = [
                    'productId'                => $entity->get('id'),
                    'attributeId'              => $productFamilyAttribute->get('attributeId'),
                    'productFamilyAttributeId' => $productFamilyAttribute->get('id'),
                    'isRequired'               => (int)$productFamilyAttribute->get('isRequired'),
                    'scope'                    => $productFamilyAttribute->get('scope'),
                    'locale'                   => $productFamilyAttribute->get('locale'),
                    'localeParentId'           => $repository->getLocaleParentId($productFamilyAttribute, $entity)
                ];

                // searching with channels
                $channels = $channelsIds = [];
                if ($productFamilyAttribute->get('scope') == 'Channel') {
                    $channels = $productFamilyAttribute->get('channels');
                    if (count($channels) > 0) {
                        $channelsIds = array_column($channels->toArray(), 'id');

                        $productAttributeValue = $this
                            ->getEntityManager()
                            ->nativeQuery("SELECT *
                            FROM product_attribute_value as pav
                            INNER JOIN product_attribute_value_channel as pavc
                            ON pav.id = pavc.product_attribute_value_id
                            WHERE pav.product_id=:productId AND pav.attribute_id=:attributeId AND is_required=:isRequired 
                              AND pav.scope=:scope AND pav.deleted=0 
                              AND pavc.channel_id in ('" . implode(',', $channelsIds) . "')",
                                [
                                    'productId'   => $entity->get('id'),
                                    'attributeId' => $productFamilyAttribute->get('attributeId'),
                                    'isRequired'  => (int)$productFamilyAttribute->get('isRequired'),
                                    'scope'       => $productFamilyAttribute->get('scope')
                                ])
                            ->fetchAll(\PDO::FETCH_ASSOC);
                    }
                } else {
                    //Global scope
                    $productAttributeValue = $this
                        ->getEntityManager()
                        ->nativeQuery("SELECT *
                            FROM product_attribute_value
                            WHERE product_id=:productId AND attribute_id=:attributeId
                              AND is_required=:isRequired AND scope=:scope AND deleted=0",
                            [
                                'productId'       => $entity->get('id'),
                                'attributeId'     => $productFamilyAttribute->get('attributeId'),
                                'isRequired'      => (int)$productFamilyAttribute->get('isRequired'),
                                'scope'           => $productFamilyAttribute->get('scope')
                            ])
                        ->fetch(\PDO::FETCH_ASSOC);
                }

                // save
                try {
                    if (
                        !empty($productAttributeValue) && count($productAttributeValue) > 0
                        && ($productFamilyAttribute->get('scope') != 'Channel' || count($productAttributeValue) == count($channels))
                    ) {
                        //update
                        $this
                            ->getEntityManager()
                            ->nativeQuery("UPDATE product_attribute_value
                            SET product_family_attribute_id=:productFamilyAttributeId
                            WHERE product_id=:productId AND attribute_id=:attributeId AND is_required=:isRequired
                            AND scope=:scope AND locale=:locale AND locale_parent_id=:localeParentId",
                                $productAttributeValueData);
                    } else {
                        //create without channels
                        $pav_id = Util::generateId();

                        $attribute = $this->getEntityManager()->getEntity('Attribute', $productFamilyAttribute->get('attributeId'));
                        if (!empty($attribute)) {
                            $attributeType = $attribute->get('type');
                        }
                        $attributeType = $attributeType ?? $this->getMetadata()->get("entityDefs.Attribute.fields.type.default");

                        $date = date('Y-m-d H:i:s', time());

                        $this
                            ->getEntityManager()
                            ->nativeQuery("INSERT INTO product_attribute_value 
                            (id, attribute_type, product_id, attribute_id, product_family_attribute_id, 
                                is_required, scope, locale, locale_parent_id, created_at, modified_at)
                            VALUES ('" . $pav_id . "', '" . $attributeType . "', :productId, :attributeId,
                                :productFamilyAttributeId, :isRequired, :scope, :locale, :localeParentId, '".$date."', '".$date."')",
                                $productAttributeValueData);

                        //create with channels
                        if ($productFamilyAttribute->get('scope') == 'Channel' && count($channels) > 0) {
                            foreach ($channelsIds as $channelsId) {
                                $this
                                    ->getEntityManager()
                                    ->nativeQuery("INSERT INTO product_attribute_value_channel
                                (channel_id, product_attribute_value_id)
                                VALUES ('" . $channelsId . "', '" . $pav_id . "')");
                            }
                        }
                    }
                } catch (BadRequest $e) {
                    $GLOBALS['log']->error('BadRequest: ' . $e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->translate($key, 'exceptions', 'Product');
    }
}
