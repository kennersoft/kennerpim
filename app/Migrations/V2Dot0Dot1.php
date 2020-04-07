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

/**
 * Migration class for version 2.0.1
 *
 * @author r.ratsun@treolabs.com
 */
class V2Dot0Dot1 extends \Treo\Core\Migration\AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        // migrate channel attributes
        $this->migrateChannelAttributes();
    }

    /**
     * Migrate channel attributes
     */
    protected function migrateChannelAttributes(): void
    {
        if (!empty($attributes = $this->getEntityManager()->getRepository('ProductAttributeValue')->find())) {
            // prepare sql
            $sql = "";
            foreach ($attributes as $item) {
                // prepare data
                $id = $item->get('id');
                $attributeId = $item->get('attributeId');
                $productId = $item->get('productId');

                $sql .= "UPDATE channel_product_attribute_value SET product_attribute_id='$id' WHERE attribute_id='$attributeId' AND product_id='$productId' AND deleted=0;";
            }

            if (!empty($sql)) {
                $sth = $this
                    ->getEntityManager()
                    ->getPDO()
                    ->prepare($sql);
                $sth->execute();
            }
        }
    }
}
