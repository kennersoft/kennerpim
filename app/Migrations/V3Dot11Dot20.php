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

use Treo\Core\Migration\AbstractMigration;

/**
 * Migration class for version 3.11.20
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot11Dot20 extends AbstractMigration
{
    /**
     * @inheritdoc
     */
    public function up(): void
    {
        $this->execute("UPDATE attribute SET type='array' WHERE type='arrayMultiLang'");
        $this->execute("UPDATE attribute SET type='varchar', is_multilang=1 WHERE type='varcharMultiLang'");
        $this->execute("UPDATE attribute SET type='text', is_multilang=1 WHERE type='textMultiLang'");
        $this->execute("UPDATE attribute SET type='wysiwyg', is_multilang=1 WHERE type='wysiwygMultiLang'");
        $this->execute("UPDATE attribute SET type='enum', is_multilang=1 WHERE type='enumMultiLang'");
        $this->execute("UPDATE attribute SET type='multiEnum', is_multilang=1 WHERE type='multiEnumMultiLang'");
    }

    /**
     * @inheritdoc
     */
    public function down(): void
    {
        $this->execute("UPDATE attribute SET type='varcharMultiLang' WHERE type='varchar' AND is_multilang=1");
        $this->execute("UPDATE attribute SET type='textMultiLang' WHERE type='text' AND is_multilang=1");
        $this->execute("UPDATE attribute SET type='wysiwygMultiLang' WHERE type='wysiwyg' AND is_multilang=1");
        $this->execute("UPDATE attribute SET type='enumMultiLang' WHERE type='enum' AND is_multilang=1");
        $this->execute("UPDATE attribute SET type='multiEnumMultiLang' WHERE type='multiEnum' AND is_multilang=1");
    }

    /**
     * @param string $sql
     *
     * @return mixed
     */
    private function execute(string $sql)
    {
        $sth = $this
            ->getEntityManager()
            ->getPDO()
            ->prepare($sql);
        $sth->execute();

        return $sth;
    }
}
