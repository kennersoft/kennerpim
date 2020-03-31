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
