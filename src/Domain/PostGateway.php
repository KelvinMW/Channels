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

class PostGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'channelsPost';
    private static $primaryKey = 'channelsPostID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryPostsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $showPreviousYear = "N", $gibbonPersonID = null, $gibbonRoleID = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['channelsPost.channelsPostID', 'channelsPost.post', 'channelsPost.timestamp', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.username', 'gibbonPerson.image_240'])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=channelsPost.gibbonPersonID')
            ->innerJoin('gibbonSchoolYear', 'channelsPost.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('channelsPostTag', 'channelsPostTag.channelsPostID=channelsPost.channelsPostID')
            ->leftJoin('channelsCategory', 'FIND_IN_SET(channelsCategory.channelsCategoryID, channelsPost.channelsCategoryIDList)')
            ->groupBy(['channelsPost.channelsPostID']);

        if ($showPreviousYear == "Y") {
            $query->where('(channelsPost.gibbonSchoolYearID=:gibbonSchoolYearID OR channelsPost.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE sequenceNumber<(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) ORDER BY sequenceNumber DESC LIMIT 0,1))')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }
        else {
            $query->where('channelsPost.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        if (!empty($gibbonPersonID)) {
            $query->where('channelsPost.gibbonPersonID=:gibbonPersonID')
                  ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        if (!empty($gibbonRoleID)) {
            $query->innerJoin('gibbonRole', "( (channelsCategory.channelsCategoryID IS NULL OR channelsPost.channelsCategoryIDList = '')
                    OR (gibbonRole.category = 'Staff' AND (channelsCategory.staffAccess='View' OR channelsCategory.staffAccess='Post'))
                    OR (gibbonRole.category = 'Student' AND (channelsCategory.studentAccess='View' OR channelsCategory.studentAccess='Post'))
                    OR (gibbonRole.category = 'Parent' AND (channelsCategory.parentAccess='View' OR channelsCategory.parentAccess='Post'))
                    OR (gibbonRole.category = 'Other' AND (channelsCategory.otherAccess='View' OR channelsCategory.otherAccess='Post'))
                )")
                ->where('gibbonRole.gibbonRoleID=:gibbonRoleID')
                  ->bindValue('gibbonRoleID', $gibbonRoleID);
        }

        $criteria->addFilterRules([
            'category' => function ($query, $category) {
                return $query
                    ->where('FIND_IN_SET(:category, channelsPost.channelsCategoryIDList)')
                    ->bindValue('category', $category);
            },
            'tag' => function ($query, $tag) {
                return $query
                    ->where('channelsPostTag.tag=:tag')
                    ->bindValue('tag', $tag);
            },
            'user' => function ($query, $user) {
                return $query
                    ->where('gibbonPerson.username=:user')
                    ->bindValue('user', $user);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
}
