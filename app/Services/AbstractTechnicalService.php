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


namespace Pim\Services;

use Espo\Core\Templates\Services\Base;
use Espo\Core\Exceptions;

/**
 * Class AbstractTechService
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class AbstractTechnicalService extends AbstractService
{

    /**
     * Check acl for related Entity when action for technical Entity
     *
     * @param string $entityName
     * @param string $entityId
     * @param string $action
     *
     * @return bool
     * @throws Exceptions\Forbidden
     */
    protected function checkAcl(string $entityName, string $entityId, string $action): bool
    {
        // get entity
        if (!empty($entityId) && !empty($entityName)) {
            $entity = $this
                ->getEntityManager()
                ->getEntity($entityName, $entityId);
        }

        // check Acl
        if (!isset($entity) || !$this->getAcl()->check($entity, $action)) {
            throw new Exceptions\Forbidden();
        }

        return true;
    }


    /**
     * Check is valid data for create
     *
     * @param array $data
     * @param array $requiredParams
     *
     * @return bool
     * @throws Exceptions\BadRequest
     */
    protected function isValidCreateData(array $data, array $requiredParams): bool
    {
        // check data
        foreach ($requiredParams as $field) {
            if (empty($data[$field])) {
                $message = $this->getTranslate('notValid', 'exceptions', 'AbstractTechnical');
                throw new Exceptions\BadRequest($message);
            }
        }

        return true;
    }
}
