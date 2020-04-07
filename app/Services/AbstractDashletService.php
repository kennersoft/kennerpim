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

namespace Pim\Services;

use Espo\Core\Templates\Repositories\Base as BaseRepository;
use Espo\Core\Services\Base;
use Treo\Services\DashletInterface;

/**
 * Class AbstractDashletService
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractDashletService extends Base implements DashletInterface
{
    /**
     * Get PDO
     *
     * @return \PDO
     */
    protected function getPDO(): \PDO
    {
        return $this->getEntityManager()->getPDO();
    }

    /**
     * Get Repository
     *
     * @param $entityType
     *
     * @return BaseRepository
     */
    protected function getRepository(string $entityType): BaseRepository
    {
        return $this->getEntityManager()->getRepository($entityType);
    }
}
