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

class PostTagGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'channelsPostTag';
    private static $primaryKey = 'channelsPostTagID';
    private static $searchableColumns = ['channelsPostTag.tag'];


    public function selectRecentTagsBySchoolYear($gibbonSchoolYearID, $limit = 20)
    {
        $query = $this
            ->newSelect()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['channelsPostTag.tag'])
            ->innerJoin('channelsPost', 'channelsPost.channelsPostID=channelsPostTag.channelsPostID')
            ->where('channelsPost.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['channelsPostTag.tag'])
            ->orderBy(['channelsPost.timestamp DESC'])
            ->limit($limit);

        return $this->runSelect($query);
    }
}
