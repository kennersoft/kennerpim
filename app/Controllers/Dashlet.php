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

namespace Pim\Controllers;

use Espo\Core\Exceptions;
use Pim\Services\DashletInterface;
use Slim\Http\Request;

/**
 * Class DashletController
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Dashlet extends AbstractController
{

    /**
     * Get dashlet
     *
     * @ApiDescription(description="Get Dashlet data")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Dashlet/{dashletName}")
     * @ApiParams(name="dashletName", type="string", is_required=1, description="Dashlet name")
     * @ApiReturn(sample="[{
     *     'total': 'integer',
     *     'list': 'array'
     * }]")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function actionGetDashlet($params, $data, Request $request): array
    {
        // is get?
        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        if (!empty($params['dashletName'])) {
            return $this->createDashletService($params['dashletName'])->getDashlet();
        }

        throw new Exceptions\Error();
    }

    /**
     * Create dashlet service
     *
     * @param string $dashletName
     *
     * @return DashletInterface
     * @throws Exceptions\Error
     */
    protected function createDashletService(string $dashletName): DashletInterface
    {
        $serviceName = ucfirst($dashletName) . 'Dashlet';

        $dashletService = $this->getServiceFactory()->create($serviceName);

        if (!$dashletService instanceof DashletInterface) {
            $message = sprintf($this->translate('notDashletService'), $serviceName);

            throw new Exceptions\Error($message);
        }

        return $dashletService;
    }

    /**
     * Translate
     *
     * @param string $key
     *
     * @param string $category
     *
     * @return string
     */
    protected function translate(string $key, string $category = 'exceptions'): string
    {
        return $this->getContainer()->get('language')->translate($key, $category);
    }
}
