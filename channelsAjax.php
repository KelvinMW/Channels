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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Channels\Domain\PostGateway;
use Gibbon\Module\Channels\Domain\PostTagGateway;
use Gibbon\Module\Channels\Domain\PostAttachmentGateway;
use Gibbon\Module\Channels\Domain\CategoryGateway;
use Gibbon\Module\Channels\Domain\CategoryViewedGateway;
use Gibbon\View\View;

$_POST['address'] = '/modules/Channels/channels.php';

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Channels/channels.php') == false) {
    // Access denied
    echo Format::alert(__('You do not have access to this action.'));
    return;
} else {
    // Proceed!
    $urlParams = [
        'channelsCategoryID' => $_REQUEST['channelsCategoryID'] ?? '',
        'category' => $_REQUEST['category'] ?? '',
        'tag'      => $_REQUEST['tag'] ?? '',
        'user'     => $_REQUEST['user'] ?? '',
    ];

    // QUERY
    $postGateway = $container->get(PostGateway::class);
    $categoryGateway = $container->get(CategoryGateway::class);
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    $criteria = $postGateway->newQueryCriteria(true)
        ->sortBy(['timestamp'], 'DESC')
        ->filterBy('category', $urlParams['channelsCategoryID'])
        ->filterBy('tag', $urlParams['tag'])
        ->filterBy('user', $urlParams['user'])
        ->pageSize(15)
        ->fromPOST();

    // Get the channels, join a set of attachment data per post
    $showPreviousYear = $container->get(SettingGateway::class)->getSettingByScope('Channels', 'showPreviousYear');
    $channels = $postGateway->queryPostsBySchoolYear($criteria, $gibbonSchoolYearID, $showPreviousYear, null, $session->get('gibbonRoleIDCurrent'));
    $channelsPosts = $channels->getColumn('channelsPostID');
    $attachments = $container->get(PostAttachmentGateway::class)->selectAttachmentsByPost($channelsPosts)->fetchGrouped();
    $channels->joinColumn('channelsPostID', 'attachments', $attachments);

    // Get viewable categories
    $categories = $categoryGateway->selectViewableCategoriesByPerson($session->get('gibbonPersonID'))->fetchGroupedUnique();
    if (!empty($urlParams['channelsCategoryID']) && empty($categories[$urlParams['channelsCategoryID']])) {
        echo Format::alert(__('You do not have access to this action.'));
        return;
    }

    $currentCategory = $categories[$urlParams['channelsCategoryID']] ?? [];

    // Auto-link hashtags
    $channelsData = array_map(function ($item) {
        $item['post'] = preg_replace_callback('/([#]+)([\w]+)/iu', function ($matches) {
            return Format::link('./index.php?q=/modules/Channels/channels.php&tag=' . $matches[2], $matches[1] . $matches[2]);
        }, $item['post']);

        return $item;
    }, $channels->toArray());

    // RENDER POSTS
    $view = $container->get(View::class);
    echo $view->fetchFromTemplate('channelsPosts.twig.html', [
        'channels' => $channelsData,
        'pageNumber' => $channels->getPage(),
        'pageCount' => $channels->getPageCount(),
        'categories' => $categories,
        'currentCategory' => $currentCategory,
    ]);
}
?>
