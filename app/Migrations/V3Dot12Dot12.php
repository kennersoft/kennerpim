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

namespace Pim\Migrations;

use Treo\Core\Migration\Base;

/**
 * Migration class for version 3.12.12
 *
 * @author m.kokhanskyi@treolabs.com
 */

/**
 * Migration class for version 3.12.12
 *
 * @author Roman Ratsun <r.ratsun@gmail.com>
 */
class V3Dot12Dot12 extends Base
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        $this->exec("ALTER TABLE `category` ADD sort_order INT DEFAULT NULL COLLATE utf8mb4_unicode_ci");

        // get all categories
        $sth = $this->getPDO()->prepare("SELECT id FROM `category` WHERE deleted=0");
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_COLUMN);

        // update sort order
        $max = 0;
        foreach ($data as $id) {
            $this->getPDO()->exec("UPDATE category SET sort_order=$max WHERE id='$id'");
            $max = $max + 10;
        }
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
        $this->exec("ALTER TABLE `category` DROP sort_order");
    }

    /**
     * @param string $sql
     */
    protected function exec(string $sql): void
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\PDOException $e) {
            $GLOBALS['log']->error('Migration of PIM (3.12.12): ' . $e->getMessage() . ' | ' . $sql);
        }
    }
}
