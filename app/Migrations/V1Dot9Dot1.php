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

namespace Pim\Migrations;

use Treo\Core\Migration\AbstractMigration;

/**
 * Migration class for version 1.9.1
 *
 * @author r.ratsun@treolabs.com
 */
class V1Dot9Dot1 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $sth = $this->getEntityManager()->getPDO()->prepare("SELECT * FROM product_family_attribute");
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $sql = '';
            foreach ($data as $row) {
                // prepare data
                $isRequired = $row['is_required'];
                $productFamilyId = $row['product_family_id'];
                $attributeId = $row['attribute_id'];
                $isMultiChannel = $row['is_multi_channel'];

                $sql
                    .= "INSERT INTO product_family_attribute_linker
                            (is_required, product_family_id, attribute_id, is_multi_channel) 
                         VALUES 
                            ($isRequired, '$productFamilyId', '$attributeId', $isMultiChannel);";
            }

            $sql .= "DELETE FROM product_family_attribute";

            // execute
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();
        }
    }
}
