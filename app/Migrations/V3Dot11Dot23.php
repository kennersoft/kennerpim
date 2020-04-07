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
 * Migration class for version 3.11.23
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class V3Dot11Dot23 extends Base
{
    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->exec("ALTER TABLE `channel` ADD locales MEDIUMTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci");
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->exec("ALTER TABLE `channel` DROP locales");
    }

    /**
     * @param string $sql
     *
     * @return void
     */
    private function exec(string $sql): void
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\PDOException $e) {
            $GLOBALS['log']->error('Migration of PIM (3.11.23): ' . $sql . ' | ' . $e->getMessage());
        }
    }
}
