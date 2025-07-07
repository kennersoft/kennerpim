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

namespace Pim\SelectManagers;

use Pim\Core\SelectManagers\AbstractSelectManager;
use Pim\Services\GeneralStatisticsDashlet;
use Treo\Core\Utils\Util;

/**
 * Product select manager
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Product extends AbstractSelectManager
{
    /**
     * @inheritdoc
     */
    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        // filtering by product types
        $params['where'][] = [
            'type'      => 'in',
            'attribute' => 'type',
            'value'     => array_keys($this->getMetadata()->get('pim.productType', []))
        ];

        // get product attributes filter
        $productAttributes = $this->getProductAttributeFilter($params);

        // get select params
        $selectParams = parent::getSelectParams($params, $withAcl, $checkWherePermission);

        // prepare custom where
        if (!isset($selectParams['customWhere'])) {
            $selectParams['customWhere'] = '';
        }

        // add product attributes filter
        $this->addProductAttributesFilter($selectParams, $productAttributes);

        // for products in category page
        if ($params['sortBy'] == 'pcSorting') {
            $selectParams['additionalColumns']['sorting'] = 'pcSorting';
            $selectParams['orderBy'] = 'product_category_linker.sorting';
        }

        return $selectParams;
    }

    /**
     * @inheritDoc
     */
    protected function textFilter($textFilter, &$result)
    {
        // call parent
        parent::textFilter($textFilter, $result);

        if (empty($result['whereClause'])) {
            return;
        }

        $scopes = $this->getMetadata()->get(['entityDefs', 'Product', 'collection'], []);

        if (isset($scopes['attributeTextFilterDisable']) && $scopes['attributeTextFilterDisable'] == true) {
            return;
        }

        // get last
        $last = array_pop($result['whereClause']);

        if (!isset($last['OR'])) {
            return;
        }

        // prepare text filter
        $textFilter = \addslashes($textFilter);

        // prepare rows
        $rows = [];

        // push for fields
        foreach ($last['OR'] as $name => $value) {
            $rows[] = "product." . Util::toUnderScore(str_replace('*', '', $name)) . " LIKE '" . \addslashes($value) . "'";
        }

        // get attributes ids
        $attributesIds = $this
            ->getEntityManager()
            ->nativeQuery("SELECT id FROM attribute WHERE type IN ('varchar','text','wysiwyg') AND deleted=0")
            ->fetchAll(\PDO::FETCH_ASSOC);
        $attributesIds = array_column($attributesIds, 'id');

        // get products ids
        $productsIds = $this
            ->getEntityManager()
            ->nativeQuery("SELECT product_id FROM product_attribute_value WHERE deleted=0 AND attribute_id IN ('" . implode("','", $attributesIds) . "') AND (value LIKE '%$textFilter%')")
            ->fetchAll(\PDO::FETCH_ASSOC);
        $productsIds = array_column($productsIds, 'product_id');

        // push for attributes
        $rows[] = "product.id IN ('" . implode("','", $productsIds) . "')";

        // prepare custom where
        $result['customWhere'] .= " AND (" . implode(" OR ", $rows) . ")";
    }

    /**
     * @param array $result
     */
    protected function boolFilterNotLinkedCategoryAndOnlyCatalogsProducts(array &$result)
    {
        $data = $this->getSelectCondition('notLinkedCategoryAndOnlyCatalogsProducts');

        if (isset($data['categoryId']) && isset($data['scope'])) {
            $catalogs = $this
                ->getEntityManager()
                ->getRepository('Catalog')
                ->distinct()
                ->select(['id'])
                ->join('categories')
                ->where([
                    'categories.id' => $data['categoryId']
                ])
                ->find()
                ->toArray();

            if (!empty($catalogs)) {
                $productsIds = $this
                    ->getEntityManager()
                    ->getRepository('Product')
                    ->distinct()
                    ->select(['id'])
                    ->join(['productCategories'])
                    ->where([
                        'catalogId' => array_column($catalogs, 'id'),
                        'productCategories.categoryId' => $data['categoryId'],
                        'productCategories.scope' => $data['scope']
                    ])
                    ->find()
                    ->toArray();

                if (!empty($productsIds)) {
                    $result['whereClause'][] = [
                        'id!=' => array_column($productsIds, 'id')
                    ];
                }
            }
        }
    }

    /**
     * Products without associated products filter
     *
     * @param $result
     */
    protected function boolFilterWithoutAssociatedProducts(&$result)
    {
        $result['whereClause'][] = [
            'id' => array_column($this->getProductWithoutAssociatedProduct(), 'id')
        ];
    }

    /**
     * @param array $result
     */
    protected function boolFilterOnlyCatalogProducts(&$result)
    {
        if (!empty($category = $this->getEntityManager()->getEntity('Category', (string)$this->getSelectCondition('notLinkedWithCategory')))) {
            // prepare ids
            $ids = ['-1'];

            // get root id
            if (empty($category->get('categoryParent'))) {
                $rootId = $category->get('id');
            } else {
                $tree = explode("|", (string)$category->get('categoryRoute'));
                $rootId = (!empty($tree[1])) ? $tree[1] : null;
            }

            if (!empty($rootId)) {
                $catalogs = $this
                    ->getEntityManager()
                    ->getRepository('Catalog')
                    ->distinct()
                    ->join('categories')
                    ->where(['categories.id' => $rootId])
                    ->find();

                if (count($catalogs) > 0) {
                    foreach ($catalogs as $catalog) {
                        $ids = array_merge($ids, array_column($catalog->get('products')->toArray(), 'id'));
                    }
                }
            }

            // prepare where
            $result['whereClause'][] = [
                'id' => $ids
            ];
        }
    }

    /**
     * Get product without AssociatedProduct
     *
     * @return array
     */
    protected function getProductWithoutAssociatedProduct(): array
    {
        return $this->fetchAll($this->getGeneralStatisticService()->getQueryProductWithoutAssociatedProduct());
    }

    /**
     * Products without Category filter
     *
     * @param $result
     */
    protected function boolFilterWithoutAnyCategory(&$result)
    {
        $result['whereClause'][] = [
            'id' => array_column($this->getProductWithoutCategory(), 'id')
        ];
    }

    /**
     * Get product without Category
     *
     * @return array
     */
    protected function getProductWithoutCategory(): array
    {
        return $this->fetchAll($this->getGeneralStatisticService()->getQueryProductWithoutCategory());
    }

    /**
     * Products without Image filter
     *
     * @param $result
     */
    protected function boolFilterWithoutImageAssets(&$result)
    {
        $result['whereClause'][] = [
            'id' => array_column($this->getProductWithoutImageAssets(), 'id')
        ];
    }

    /**
     * Get products without Image
     *
     * @return array
     */
    protected function getProductWithoutImageAssets(): array
    {
        return $this->fetchAll($this->getGeneralStatisticService()->getQueryProductWithoutImage());
    }

    /**
     * NotAssociatedProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotAssociatedProducts(&$result)
    {
        // prepare data
        $data = (array)$this->getSelectCondition('notAssociatedProducts');

        if (!empty($data['associationId'])) {
            $associatedProducts = $this->getAssociatedProducts($data['associationId'], $data['mainProductId']);
            foreach ($associatedProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string)$row['related_product_id']
                ];
            }
        }
    }

    /**
     * OnlySimple filter
     *
     * @param array $result
     */
    protected function boolFilterOnlySimple(&$result)
    {
        $result['whereClause'][] = [
            'type' => 'simpleProduct'
        ];
    }

    /**
     * OnlySimple filter
     *
     * @param array $result
     */
    protected function boolFilterOnlyOneProductFamily(&$result)
    {
        // prepare ids
        $ids = ['-1'];

        $pfId = (string)$this->getSelectCondition('onlyOneProductFamily');

        if (!empty($pfId)) {
            $productsIds = $this
                ->getEntityManager()
                ->getRepository('Product')
                ->select(['id'])
                ->where([
                    'productFamilyId' => $pfId
                ])
                ->find()
                ->toArray();

            if (!empty($productsIds)) {
                $ids = array_column($productsIds, 'id');
            }
        }

        // prepare where
        $result['whereClause'][] = [
            'id' => $ids
        ];
    }

    /**
     * Get assiciated products
     *
     * @param string $associationId
     * @param string $productId
     *
     * @return array
     */
    protected function getAssociatedProducts($associationId, $productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT
          related_product_id
        FROM
          associated_product
        WHERE
          main_product_id =' . $pdo->quote($productId) . '
          AND association_id = ' . $pdo->quote($associationId) . '
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithChannel filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithChannel(&$result)
    {
        $channelId = (string)$this->getSelectCondition('notLinkedWithChannel');

        if (!empty($channelId)) {
            $channelProducts = $this->createService('Channel')->getProducts($channelId);
            foreach ($channelProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string)$row['productId']
                ];
            }
        }
    }

    /**
     * NotLinkedWithBrand filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithBrand(array &$result)
    {
        // prepare data
        $brandId = (string)$this->getSelectCondition('notLinkedWithBrand');

        if (!empty($brandId)) {
            // get Products linked with brand
            $products = $this->getBrandProducts($brandId);
            foreach ($products as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productId']
                ];
            }
        }
    }

    /**
     * Get productIds related with brand
     *
     * @param string $brandId
     *
     * @return array
     */
    protected function getBrandProducts(string $brandId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT id AS productId
                FROM product
                WHERE deleted = 0 
                      AND brand_id = :brandId';

        $sth = $pdo->prepare($sql);
        $sth->execute(['brandId' => $brandId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithProductFamily filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProductFamily(array &$result)
    {
        // prepare data
        $productFamilyId = (string)$this->getSelectCondition('notLinkedWithProductFamily');

        if (!empty($productFamilyId)) {
            // get Products linked with brand
            $products = $this->getProductFamilyProducts($productFamilyId);
            foreach ($products as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productId']
                ];
            }
        }
    }

    /**
     * Get productIds related with productFamily
     *
     * @param string $productFamilyId
     *
     * @return array
     */
    protected function getProductFamilyProducts(string $productFamilyId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = 'SELECT id AS productId
                FROM product
                WHERE deleted = 0
                      AND product_family_id = :productFamilyId';

        $sth = $pdo->prepare($sql);
        $sth->execute(['productFamilyId' => $productFamilyId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithPackaging filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithPackaging(&$result)
    {
        // find products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(
                [
                    'packagingId' => (string)$this->getSelectCondition('notLinkedWithPackaging')
                ]
            )
            ->find();

        if (!empty($products)) {
            foreach ($products as $product) {
                $result['whereClause'][] = [
                    'id!=' => $product->get('id')
                ];
            }
        }
    }

    /**
     * Fetch all result from DB
     *
     * @param string $query
     *
     * @return array
     */
    protected function fetchAll(string $query): array
    {
        $sth = $this->getEntityManager()->getPDO()->prepare($query);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Create dashlet service
     *
     * @return GeneralStatisticsDashlet
     */
    protected function getGeneralStatisticService(): GeneralStatisticsDashlet
    {
        return $this->createService('GeneralStatisticsDashlet');
    }

    /**
     * NotLinkedWithProductSerie filter
     *
     * @param $result
     */
    protected function boolFilterNotLinkedWithProductSerie(&$result)
    {
        //find products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->join(['productSerie'])
            ->where(
                [
                    'productSerie.id' => (string)$this->getSelectCondition('notLinkedWithProductSerie')
                ]
            )
            ->find();

        // add product ids to whereClause
        if (!empty($products)) {
            foreach ($products as $product) {
                $result['whereClause'][] = [
                    'id!=' => $product->get('id')
                ];
            }
        }
    }

    /**
     * @param array $result
     */
    protected function boolFilterLinkedWithCategory(array &$result)
    {
        // prepare category id
        $id = (string)$this->getSelectCondition('linkedWithCategory');

        // get categories
        $categories = $this->fetchAll("SELECT id FROM category WHERE (id='$id' OR category_route LIKE '%|$id|%') AND deleted=0");

        // prepare categories ids
        $ids = implode("','", array_column($categories, 'id'));

        // prepare custom where
        if (!isset($result['customWhere'])) {
            $result['customWhere'] = '';
        }

        // set custom where
        $result['customWhere'] .= " AND product.id IN (SELECT product_id FROM product_category_linker WHERE product_id IS NOT NULL AND deleted=0 AND category_id IN ('$ids'))";
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function getProductAttributeFilter(array &$params): array
    {
        // prepare result
        $result = [];

        if (!empty($params['where']) && is_array($params['where'])) {
            $where = [];
            foreach ($params['where'] as $row) {
                if (empty($row['isAttribute'])) {
                    $where[] = $row;
                } else {
                    $result[] = $row;
                }
            }
            $params['where'] = $where;
        }

        return $result;
    }

    /**
     * @param array $selectParams
     * @param array $attributes
     */
    protected function addProductAttributesFilter(array &$selectParams, array $attributes): void
    {
        foreach ($attributes as $row) {
            // find prepare method
            $method = 'prepareType' . ucfirst($row['type']);
            if (!method_exists($this, $method)) {
                $method = 'prepareTypeDefault';
            }

            // prepare where
            $where = $this->{$method}($row);

            // create select params
            $sp = $this
                ->createSelectManager('ProductAttributeValue')
                ->getSelectParams(['where' => [$where]], true, true);
            $sp['select'] = ['productId'];

            // create sql
            $sql = $this
                ->getEntityManager()
                ->getQuery()
                ->createSelectQuery('ProductAttributeValue', $sp);

            // prepare custom where
            $selectParams['customWhere'] .= ' AND product.id IN (' . $sql . ')';
        }
    }

    /**
     * @param string $attributeId
     *
     * @return array
     */
    protected function getValues(string $attributeId): array
    {
        // prepare result
        $result = ['value'];

        if ($this->getConfig()->get('isMultilangActive', false) && !empty($locales = $this->getConfig()->get('inputLanguageList', []))) {
            // is attribute multi-languages ?
            $isMultiLang = $this
                ->getEntityManager()
                ->getRepository('Attribute')
                ->select(['isMultilang'])
                ->where(['id' => $attributeId])
                ->findOne()
                ->get('isMultilang');

            if ($isMultiLang) {
                foreach ($locales as $locale) {
                    $result[] = 'value' . ucfirst(Util::toCamelCase(strtolower($locale)));
                }
            }
        }

        return $result;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareTypeIsTrue(array $row): array
    {
        $where = ['type' => 'or', 'value' => []];
        foreach ($this->getValues($row['attribute']) as $v) {
            $where['value'][] = [
                'type'  => 'and',
                'value' => [
                    [
                        'type'      => 'equals',
                        'attribute' => 'attributeId',
                        'value'     => $row['attribute']
                    ],
                    [
                        'type'      => 'equals',
                        'attribute' => $v,
                        'value'     => '1'
                    ]
                ]
            ];
        }

        return $where;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareTypeIsFalse(array $row): array
    {
        $where = [
            'type'  => 'and',
            'value' => [
                [
                    'type'      => 'equals',
                    'attribute' => 'attributeId',
                    'value'     => $row['attribute']
                ],
                [
                    'type'  => 'or',
                    'value' => []
                ],
            ]
        ];

        foreach ($this->getValues($row['attribute']) as $v) {
            $where['value'][1]['value'][] = [
                'type'      => 'isNull',
                'attribute' => $v
            ];
            $where['value'][1]['value'][] = [
                'type'      => 'notEquals',
                'attribute' => $v,
                'value'     => '1'
            ];
        }

        return $where;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareTypeArrayAnyOf(array $row): array
    {
        $where = [
            'type'  => 'and',
            'value' => [
                [
                    'type'      => 'equals',
                    'attribute' => 'attributeId',
                    'value'     => $row['attribute']
                ],
                [
                    'type'  => 'or',
                    'value' => []
                ],
            ]
        ];

        // prepare values
        $values = (empty($row['value'])) ? [md5('no-such-value-' . time())] : $row['value'];

        foreach ($values as $value) {
            $where['value'][1]['value'][] = [
                'type'      => 'like',
                'attribute' => 'value',
                'value'     => "%\"$value\"%"
            ];
        }

        return $where;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareTypeArrayNoneOf(array $row): array
    {
        $where = [
            'type'  => 'and',
            'value' => [
                [
                    'type'      => 'equals',
                    'attribute' => 'attributeId',
                    'value'     => $row['attribute']
                ],
                [
                    'type'  => 'or',
                    'value' => []
                ],
            ]
        ];

        // prepare values
        $values = (empty($row['value'])) ? [md5('no-such-value-' . time())] : $row['value'];

        foreach ($values as $value) {
            $where['value'][1]['value'][] = [
                'type'      => 'notLike',
                'attribute' => 'value',
                'value'     => "%\"$value\"%"
            ];
        }

        return $where;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareTypeArrayIsEmpty(array $row): array
    {
        $where = [
            'type'  => 'and',
            'value' => [
                [
                    'type'      => 'equals',
                    'attribute' => 'attributeId',
                    'value'     => $row['attribute']
                ],
                [
                    'type'  => 'or',
                    'value' => [
                        [
                            'type'      => 'isNull',
                            'attribute' => 'value'
                        ],
                        [
                            'type'      => 'equals',
                            'attribute' => 'value',
                            'value'     => ''
                        ],
                        [
                            'type'      => 'equals',
                            'attribute' => 'value',
                            'value'     => '[]'
                        ]
                    ]
                ],
            ]
        ];

        return $where;
    }

    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareTypeArrayIsNotEmpty(array $row): array
    {
        $where = [
            'type'  => 'and',
            'value' => [
                [
                    'type'      => 'equals',
                    'attribute' => 'attributeId',
                    'value'     => $row['attribute']
                ],
                [
                    'type'      => 'isNotNull',
                    'attribute' => 'value'
                ],
                [
                    'type'      => 'notEquals',
                    'attribute' => 'value',
                    'value'     => ''
                ],
                [
                    'type'      => 'notEquals',
                    'attribute' => 'value',
                    'value'     => '[]'
                ]
            ]
        ];

        return $where;
    }


    /**
     * @param array $row
     *
     * @return array
     */
    protected function prepareTypeDefault(array $row): array
    {
        $where = ['type' => 'or', 'value' => []];
        foreach ($this->getValues($row['attribute']) as $v) {
            $where['value'][] = [
                'type'  => 'and',
                'value' => [
                    [
                        'type'      => 'equals',
                        'attribute' => 'attributeId',
                        'value'     => $row['attribute']
                    ],
                    [
                        'type'      => $row['type'],
                        'attribute' => $v,
                        'value'     => $row['value']
                    ]
                ]
            ];
        }

        return $where;
    }

    /**
     * @param string $id
     * @param array  $result
     */
    protected function boolFilterAllowedForCategory(array &$result)
    {
        // prepare product id
        $id = (string)$this->getSelectCondition('allowedForCategory');

        // get allowed ids
        $ids = $this->getEntityManager()->getRepository('Category')->getProductsIdsThatCanBeRelatedWithCategory($id);

        $result['whereClause'][] = [
            'id' => empty($ids) ? ['no-such-id'] : $ids
        ];
    }

    /**
     * @param array $result
     */
    protected function boolFilterProductVariant(array &$result)
    {
        /**
         * @noinspection SqlNoDataSourceInspection
         */
        $sql = <<<SQL
SELECT id
FROM product
WHERE deleted = 0
AND parent_product_id IS NOT NULL
SQL;

        $result['whereClause'][] = [
            'id' => $this->fetchColumn($sql)
        ];
    }

    /**
     * @param array $result
     */
    protected function boolFilterProduct(array &$result)
    {
        /**
         * @noinspection SqlNoDataSourceInspection
         */
        $sql = <<<SQL
SELECT id
FROM product
WHERE deleted = 0
AND parent_product_id IS NULL
SQL;

        $result['whereClause'][] = [
            'id' => $this->fetchColumn($sql)
        ];
    }
}
