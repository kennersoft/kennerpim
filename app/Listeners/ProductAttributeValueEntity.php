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
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;
use Pim\Entities\ProductAttributeValue;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class ProductAttributeValueEntity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductAttributeValueEntity extends AbstractListener
{
    /**
     * @var array
     */
    protected static $beforeSaveData = [];

    /**
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeSave(Event $event)
    {
        // get data
        $entity = $event->getArgument('entity');
        $options = $event->getArgument('options');

        // exit
        if (!empty($options['skipProductAttributeValueHook'])) {
            return true;
        }

        if (empty($entity->get('product')) || empty($entity->get('attribute'))) {
            throw new BadRequest($this->exception('Product and Attribute cannot be empty'));
        }

        if (!$entity->isNew() && !empty($entity->get('productFamilyAttribute')) && empty($entity->skipPfValidation)) {
            if ($entity->isAttributeChanged('scope')
                || $entity->isAttributeChanged('isRequired')
                || $entity->isAttributeChanged('attributeId')) {
                throw new BadRequest($this->exception('Product Family attribute cannot be changed'));
            }
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest(
                sprintf(
                    $this->exception('Such product attribute \'%s\' already exists'),
                    $entity->get('attribute')->get('name')
                )
            );
        }

        // clearing channels ids
        if ($entity->get('scope') == 'Global') {
            $entity->set('channelsIds', []);
        }

        // storing data
        if (!$entity->isNew()) {
            self::$beforeSaveData = $this->getEntityManager()->getEntity('ProductAttributeValue', $entity->get('id'))->toArray();
        }
    }

    /**
     * @param Event $event
     *
     * @return bool
     * @throws Error
     */
    public function afterSave(Event $event)
    {
        /** @var Entity $entity */
        $entity = $event->getArgument('entity');

        /** @var array $options */
        $options = $event->getArgument('options');

        $this->moveImageFromTmp($entity);
        // exit
        if (!empty($options['skipProductAttributeValueHook'])) {
            return true;
        }

        // create note
        if ($entity->isAttributeChanged('value') || $entity->isAttributeChanged('data')) {
            $this->createNote($entity);
        }

        //Update attribute values in variants
        $this->getService('Product')->runUpdateVariantsAfterProductSave($entity->get('product'));

        return true;
    }

    /**
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeRemove(Event $event)
    {
        // get data
        $entity = $event->getArgument('entity');
        $options = $event->getArgument('options');

        // exit
        if (!empty($options['skipProductAttributeValueHook'])) {
            return true;
        }

        if (!empty($productFamilyAttribute = $entity->get('productFamilyAttribute')) && !empty($productFamilyAttribute->get('productFamily'))) {
            throw new BadRequest($this->exception('Product Family attribute cannot be deleted'));
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        // prepare count
        $count = 0;

        if ($entity->get('scope') == 'Global') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(
                    [
                        'id!='        => $entity->get('id'),
                        'productId'   => $entity->get('productId'),
                        'attributeId' => $entity->get('attributeId'),
                        'scope'       => 'Global',
                    ]
                )
                ->count();
        }

        if ($entity->get('scope') == 'Channel') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->distinct()
                ->join('channels')
                ->where(
                    [
                        'id!='        => $entity->get('id'),
                        'productId'   => $entity->get('productId'),
                        'attributeId' => $entity->get('attributeId'),
                        'scope'       => 'Channel',
                        'channels.id' => $entity->get('channelsIds'),
                    ]
                )
                ->count();
        }

        return empty($count);
    }

    /**
     * @param Entity $entity
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function createNote(Entity $entity)
    {
        if (!empty($data = $this->getNoteData($entity))) {
            $note = $this->getEntityManager()->getEntity('Note');
            $note->set('type', 'Update');
            $note->set('parentId', $entity->get('productId'));
            $note->set('parentType', 'Product');
            $note->set('data', $data);
            $note->set('attributeId', $entity->get('id'));

            $this->getEntityManager()->saveEntity($note);
        }
    }

    /**
     * Get note data
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function getNoteData(Entity $entity): array
    {
        // get attribute
        $attribute = $entity->get('attribute');

        // prepare field name
        $fieldName = $this
                ->getContainer()
                ->get('language')
                ->translate('Attribute', 'custom', 'ProductAttributeValue') . ' ' . $attribute->get('name');

        // prepare result
        $result = [];

        // prepare array types
        $arrayTypes = ['array', 'multiEnum'];

        if (self::$beforeSaveData['value'] != $entity->get('value')
            || ($entity->isAttributeChanged('data')
                && self::$beforeSaveData['data']->unit != $entity->get('data')->unit)) {
            $result['fields'][] = $fieldName;
            if (in_array($attribute->get('type'), $arrayTypes)) {
                $result['attributes']['was'][$fieldName] = Json::decode(self::$beforeSaveData['value'], true);
                $result['attributes']['became'][$fieldName] = Json::decode($entity->get('value'), true);
            } else {
                $result['attributes']['was'][$fieldName] = (!empty(self::$beforeSaveData['value'])) ? self::$beforeSaveData['value'] : null;
                $result['attributes']['became'][$fieldName] = $entity->get('value');
            }

            if ($entity->get('attribute')->get('type') == 'unit') {
                $result['attributes']['was'][$fieldName . 'Unit'] = self::$beforeSaveData['data']->unit;
                $result['attributes']['became'][$fieldName . 'Unit'] = $entity->get('data')->unit;
            }
        }

        return $result;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getContainer()->get('language')->translate($key, 'exceptions', 'ProductAttributeValue');
    }

    /**
     * @param ProductAttributeValue $attributeValue
     * @return void
     * @throws Error
     */
    protected function moveImageFromTmp(ProductAttributeValue $attributeValue): void
    {
        $attribute = $attributeValue->get('attribute');
        if (!empty($attribute) && $attribute->get('type') === 'image' && !empty($attributeValue->get('value'))) {
            $file = $this->getEntityManager()->getEntity('Attachment', $attributeValue->get('value'));
            $this->getService($file->getEntityType())->moveFromTmp($file);
            $this->getEntityManager()->saveEntity($file);
        }
    }
}
