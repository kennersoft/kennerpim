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

namespace Pim\Services;

use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use PDO;

class QueueManagerUpdateProductVariants extends \Treo\Services\QueueManagerBase
{
    public const DATA_KEY_PRODUCT_ID = 'productId';

    private EntityManager $em;

    public function run(array $data = []): bool
    {
        if (empty($data[self::DATA_KEY_PRODUCT_ID])) {
            return false;
        }

        $this->em = $this->getEntityManager();

        $productRepository = $this->em->getRepository('Product');
        $mainProduct = $productRepository->where(['id' => $data[self::DATA_KEY_PRODUCT_ID]])->findOne();
        if (empty($mainProduct)) {
            return false;
        }

        $parentProductAttributeValues = $this->getOnlyProductAttributeValues($data[self::DATA_KEY_PRODUCT_ID]);
        if (!empty($parentProductAttributeValues)) {
            $this->updateAttributeValuesInVariants($parentProductAttributeValues, $mainProduct->get('productVariants'));
        }

        return true;
    }

    private function updateAttributeValuesInVariants(array $productAttributeValues, EntityCollection $variants): void
    {
        /**
         * @var \Pim\Entities\Product $variant
         */
        foreach ($variants as $variant) {
            foreach ($productAttributeValues as $attributeValue) {
                /** @noinspection SqlNoDataSourceInspection */
                $sql = <<<SQL
UPDATE product_attribute_value
SET value = :value
WHERE product_id = :productId
AND attribute_id = :attributeId
SQL;
                $this->em->nativeQuery(
                    $sql,
                    [
                        'value' => $attributeValue['value'],
                        'productId' => $variant->get('id'),
                        'attributeId' => $attributeValue['attribute_id'],
                    ]
                )->execute();
            }
        }
    }

    private function getOnlyProductAttributeValues(string $productId): array
    {
        /** @noinspection SqlNoDataSourceInspection */
        $sql = <<<SQL
SELECT attribute_id, value
FROM product_attribute_value
INNER JOIN attribute ON product_attribute_value.attribute_id = attribute.id AND attribute.is_variant_attribute = 0
WHERE product_attribute_value.product_id = :productId 
AND product_attribute_value.deleted = 0
AND product_attribute_value.scope = 'Global';
SQL;

        return $this->em->nativeQuery($sql, ['productId' => $productId])->fetchAll(PDO::FETCH_ASSOC);
    }
}
