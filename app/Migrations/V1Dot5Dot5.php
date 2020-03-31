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
 * Class V1Dot5Dot5
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class V1Dot5Dot5 extends AbstractMigration
{

    /**
     * @var array
     */
    protected $updateData = [];

    /**
     * Up to current
     */
    public function up(): void
    {
        $channels = $this->getEntityManager()->getRepository('Channel')->find();

        // Prepare code for channels
        foreach ($channels as $channel) {
            $this->prepareCode($channel->get('id'), $channel->get('name'));
        }

        // prepare update query
        $update = "UPDATE channel SET `code` = '%s' WHERE id = '%s';";
        $sql = '';
        foreach ($this->updateData as $code => $id) {
            $sql .= sprintf($update, $code, $id);
        }

        // execute query
        if (!empty($sql)) {
            /** @var \PDOStatement $sth */
            $sth = $this->getEntityManager()->getPDO()->prepare($sql);
            $sth->execute();
        }
    }

    /**
     *  Set code to channels
     *
     * @param string $id
     * @param string $code
     * @param int    $number
     */
    protected function prepareCode(string $id, string $code, $number = 0)
    {
        $newCode = $number === 0 ? $code : $code . ' ' . $number;

        if (empty($this->updateData[$newCode])) {
            $this->updateData[$newCode] = $id;
        } else {
            $this->prepareCode($id, $code, ++$number);
        }
    }
}
