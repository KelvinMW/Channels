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

class CategoryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'channelsCategory';
    private static $primaryKey = 'channelsCategoryID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCategories(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['channelsCategory.channelsCategoryID', 'name', 'active', 'staffAccess', 'studentAccess', 'parentAccess', 'otherAccess']);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('channelsCategory.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectViewableCategoriesByPerson($gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonPerson')
            ->cols(['channelsCategory.channelsCategoryID as groupBy', 'channelsCategory.channelsCategoryID', 'channelsCategory.name', 'channelsCategory.active', 'staffAccess', 'studentAccess', 'parentAccess', 'otherAccess', 'channelsCategoryViewed.timestamp', "COUNT(DISTINCT CASE WHEN channelsPost.timestamp>channelsCategoryViewed.timestamp THEN channelsPost.channelsPostID END) as recentPosts"])
            ->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
            ->innerJoin('channelsCategory', "channelsCategory.active='Y'")
            ->leftJoin('channelsCategoryViewed', "channelsCategoryViewed.channelsCategoryID=channelsCategory.channelsCategoryID AND channelsCategoryViewed.gibbonPersonID=gibbonPerson.gibbonPersonID")
            ->leftJoin('channelsPost', 'FIND_IN_SET(channelsCategory.channelsCategoryID, channelsPost.channelsCategoryIDList)')
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("((gibbonRole.category = 'Staff' AND (channelsCategory.staffAccess='View' OR channelsCategory.staffAccess='Post'))
                OR (gibbonRole.category = 'Student' AND (channelsCategory.studentAccess='View' OR channelsCategory.studentAccess='Post'))
                OR (gibbonRole.category = 'Parent' AND (channelsCategory.parentAccess='View' OR channelsCategory.parentAccess='Post'))
                OR (gibbonRole.category = 'Other' AND (channelsCategory.otherAccess='View' OR channelsCategory.otherAccess='Post'))
            )")
            ->groupBy(['channelsCategory.channelsCategoryID'])
            ->orderBy(['channelsCategory.sequenceNumber', 'channelsCategory.name']);

        return $this->runSelect($query);
    }

    public function selectPostableCategoriesByRole($gibbonRoleID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonRole')
            ->cols(['channelsCategory.channelsCategoryID', 'channelsCategory.name'])
            ->innerJoin('channelsCategory', "channelsCategory.active='Y'")
            ->where('gibbonRole.gibbonRoleID=:gibbonRoleID')
            ->bindValue('gibbonRoleID', $gibbonRoleID)
            ->where("((gibbonRole.category = 'Staff' AND channelsCategory.staffAccess='Post')
                OR (gibbonRole.category = 'Student' AND channelsCategory.studentAccess='Post')
                OR (gibbonRole.category = 'Parent' AND channelsCategory.parentAccess='Post')
                OR (gibbonRole.category = 'Other' AND channelsCategory.otherAccess='Post')
            )")
            ->groupBy(['channelsCategory.channelsCategoryID'])
            ->orderBy(['channelsCategory.sequenceNumber', 'channelsCategory.name']);

        return $this->runSelect($query);
    }
}
