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

namespace Pim\Listeners;

use Treo\Core\EventManager\Event;

/**
 * Class ProductController
 *
 * @author m.kokhanksyi <m.kokhanksyi@treolabs.com>
 */
class ProductController extends AbstractEntityListener
{
    /**
     * @param Event $event
     */
    public function afterActionListLinked(Event $event)
    {
        $params = $event->getArgument('params');
        $result = $event->getArgument('result');

        if ($params['link'] === 'channels') {
            $isActives = $this->getIsActive($params['id']);
            foreach ($result['list'] as &$item) {
                $item->isActiveEntity = (bool)$isActives[$item->id]['isActive'];
            }
        }

        $event->setArgument('result', $result);
    }

    /**
     * @param string $productId
     * @return array
     */
    protected function getIsActive(string $productId): array
    {
        return $this
            ->getEntityManager()
            ->nativeQuery("SELECT channel_id, is_active AS isActive FROM product_channel pc WHERE product_id = '{$productId}' AND pc.deleted = 0")
            ->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_UNIQUE);
    }
}
