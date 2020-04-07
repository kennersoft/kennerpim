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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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

namespace Pim\Migrations;

use Treo\Core\Migration\AbstractMigration;

/**
 * Migration class for version 2.9.1
 *
 * @author r.zablodskiy@treolabs.com
 */
class V2Dot9Dot1 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $attributes = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->distinct()
            ->join('attribute')
            ->where([
                'attribute.type' => ['array', 'multiEnum', 'arrayMultiLang', 'multiEnumMultiLang']
            ])
            ->find();

        if (count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                if (!empty($value = $attribute->get('value'))) {
                    $attribute->set('value', json_encode(json_decode($value), JSON_UNESCAPED_UNICODE));

                    $this->getEntityManager()->saveEntity($attribute);
                }
            }
        }
    }
}
