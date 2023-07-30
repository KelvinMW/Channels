<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\Channels\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class PostAttachmentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'channelsPostAttachment';
    private static $primaryKey = 'channelsPostAttachmentID';
    private static $searchableColumns = [''];

    public function selectAttachmentsByPost($posts)
    {
        $postList = is_array($posts)? implode(',', $posts) : $posts;

        $query = $this
            ->newSelect()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['channelsPostAttachment.channelsPostID as groupBy', 'channelsPostAttachmentID', 'attachment', 'thumbnail', 'type'])
            ->where('FIND_IN_SET(channelsPostAttachment.channelsPostID, :postList)')
            ->bindValue('postList', $postList);

        return $this->runSelect($query);
    }
}
