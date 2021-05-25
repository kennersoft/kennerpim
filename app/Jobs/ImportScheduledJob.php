<?php
/**
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 * Copyright (c) Kenner Soft Service GmbH
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
 */

declare(strict_types=1);

namespace Pim\Jobs;

use Espo\Core\Exceptions\Error;
use Treo\Entities\Attachment;
use Import\Jobs\ImportScheduledJob as Base;

/**
 * Class ImportScheduledJob
 *
 * @author v.shamota@kennersoft.de
 */
class ImportScheduledJob extends Base
{
    /**
     * Upload link file
     *
     * @param string $link
     *
     * @return Attachment
     * @throws Error
     */
    protected function uploadLinkFile(string $link): Attachment
    {
        // get link content
        $content = file_get_contents($link);
        if (!empty($content)) {
            // create attachment
            $attachment = $this->getEntityManager()->getEntity('Attachment');
            $attachment->set('name', 'import_cron_job_' . date('YmdHis') . '.csv');
            $attachment->set('type', 'text/csv');
            $attachment->set('relatedType', 'ImportCronJob');
            $attachment->set('role', 'Attachment');

            // store file
            $this
                ->getContainer()
                ->get('fileStorageManager')
                ->putContents($attachment, $content);


            $this->getEntityManager()->saveEntity($attachment);

            return $attachment;
        }

        throw new Error("File content can't be empty");
    }
}
