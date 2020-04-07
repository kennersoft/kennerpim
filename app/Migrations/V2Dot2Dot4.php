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
use Espo\Core\Utils\Json;

/**
 * Migration class for version 2.2.4
 *
 * @author r.zablodskiy@treolabs.com
 */
class V2Dot2Dot4 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $this->parseArrayMultiLangNoteData();
    }

    /**
     * Parse arrayMultiLang values in Note entity
     */
    protected function parseArrayMultiLangNoteData(): void
    {
        $attributes = $this
            ->getEntityManager()
            ->getRepository('Attribute')
            ->select(['id'])
            ->where([
                'type' => 'arrayMultiLang'
            ])
            ->find()
            ->toArray();

        if (!empty($attributes)) {
            $attributes = array_column($attributes, 'id');

            $notes = $this
                ->getEntityManager()
                ->getRepository('Note')
                ->select(['id', 'data'])
                ->where([
                    'attributeId' => $attributes
                ])
                ->find();

            if (count($notes) > 0) {
                $sql = '';
                foreach ($notes as $note) {
                    $data = Json::decode(Json::encode($note->get('data')), true);

                    foreach ($data['attributes']['was'] as $key => $value) {
                        if (!empty($value)) {
                            $data['attributes']['was'][$key] = Json::decode($value, true);
                        }
                    }

                    $sql .= sprintf(
                        "UPDATE note SET data='%s' WHERE id='%s' AND deleted=0;",
                        Json::encode($data),
                        $note->get('id')
                    );
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
}
