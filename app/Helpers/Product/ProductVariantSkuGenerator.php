<?php

declare(strict_types=1);

namespace Pim\Helpers\Product;

use Pim\Core\ORM\EntityManager;

class ProductVariantSkuGenerator
{
    private EntityManager $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function generateNewSku(string $parentProductId, string $parentProductSku): string
    {
        $variantsCount = $this->getVariantsCount($parentProductId);

        $index = $variantsCount + 1;
        $result = null;
        while ($result === null) {
            $newSku = sprintf('%s-%d', $parentProductSku, $index);
            if (!$this->isIssetVariantWithSameSku($parentProductId, $newSku)) {
                $result = $newSku;
            }

            $index++;
        }

        return $result;
    }

    private function getVariantsCount(string $parentProductId): int
    {
        /**
         * @noinspection SqlNoDataSourceInspection
         */
        $sql = <<<SQL
SELECT COUNT(*)
FROM product
WHERE parent_product_id = :parentProductId and deleted = 0;
SQL;

        return (int)$this->em->nativeQuery($sql, ['parentProductId' => $parentProductId])->fetchColumn();
    }

    private function isIssetVariantWithSameSku(string $parentProductId, string $sku): bool
    {
        /**
         * @noinspection SqlNoDataSourceInspection
         */
        $sql = <<<SQL
SELECT COUNT(*)
FROM product
WHERE parent_product_id = :parentProductId and deleted = 0 AND sku = :sku;
SQL;

        $count = (int)$this->em->nativeQuery(
            $sql,
            [
                'parentProductId' => $parentProductId,
                'sku' => $sku,
            ]
        )->fetchColumn();

        return $count > 0;
    }
}