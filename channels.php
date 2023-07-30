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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Channels\Domain\PostGateway;
use Gibbon\Module\Channels\Domain\PostTagGateway;
use Gibbon\Module\Channels\Domain\PostAttachmentGateway;
use Gibbon\Module\Channels\Domain\CategoryGateway;
use Gibbon\Module\Channels\Domain\CategoryViewedGateway;

if (isActionAccessible($guid, $connection2, '/modules/Channels/channels.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
} else {
    // Proceed!
    $urlParams = [
        'channelsCategoryID' => $_REQUEST['channelsCategoryID'] ?? '',
        'category' => $_REQUEST['category'] ?? '',
        'tag'      => $_REQUEST['tag'] ?? '',
        'user'     => $_REQUEST['user'] ?? '',
    ];

    $page->scripts->add('magnific', 'modules/Channels/js/magnific/jquery.magnific-popup.min.js');
    $page->stylesheets->add('magnific', 'modules/Channels/js/magnific/magnific-popup.css');

    $page->breadcrumbs
        ->add(__m('View Channels'), 'channels.php');

    if (!empty($urlParams['category'])) {
        $page->breadcrumbs->add(__('Category').': '.$urlParams['category']);
    } else if (!empty($urlParams['tag']) || !empty($urlParams['user'])) {
        $page->breadcrumbs->add(__('Viewing {filter}', [
            'filter' => !empty($urlParams['user']) ? $urlParams['user'] : '#' . $urlParams['tag']
        ]));
    }

    // QUERY
    $postGateway = $container->get(PostGateway::class);
    $categoryGateway = $container->get(CategoryGateway::class);
    $categoryViewedGateway = $container->get(CategoryViewedGateway::class);
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
    if (!empty($categories)) {
        $categories = array_merge([0 => ['name' => __('All'), 'channelsCategoryID' => 0]], $categories);
    }

    if (!empty($urlParams['channelsCategoryID']) && empty($categories[$urlParams['channelsCategoryID']])) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $currentCategory = $categories[$urlParams['channelsCategoryID']] ?? [];

    // Update current category timestamp
    if (!empty($currentCategory)) {
        $data = [
            'gibbonPersonID' => $session->get('gibbonPersonID'),
            'channelsCategoryID' => $currentCategory['channelsCategoryID'],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $updated = $categoryViewedGateway->insertAndUpdate($data, ['timestamp' => date('Y-m-d H:i:s')]);
    }


    $channelsData = array_map(function ($item) {
        // Auto-link urls
        $item['post'] = preg_replace_callback('/(https?:\/\/[^\s\$.?#].[^\s]*)(\s|$)+/iu', function ($matches) {
            $linktext = strlen($matches[1]) > 45 ? substr($matches[1], 0, 45).'…' : $matches[1];
            return Format::link($matches[1], $linktext).' ';
        }, $item['post']);

        // Auto-link hashtags
        $item['post'] = preg_replace_callback('/(?:\s|^)+([#]+)([\w]+)($|\b)+/iu', function ($matches) {
            return ' '.Format::link('./index.php?q=/modules/Channels/channels.php&tag=' . $matches[2], $matches[1] . $matches[2]).$matches[3];
        }, $item['post']);

        return $item;
    }, $channels->toArray());

    // RENDER CHANNELS
    echo $page->fetchFromTemplate('channels.twig.html', [
        'channels' => $channelsData,
        'pageNumber' => $channels->getPage(),
        'pageCount' => $channels->getPageCount(),
        'categories' => $categories,
        'currentCategory' => $currentCategory,
    ]);

    $categoryGateway = $container->get(CategoryGateway::class);
    $categories = $categoryGateway->selectPostableCategoriesByRole($session->get('gibbonRoleIDCurrent'))->fetchKeyPair();

    $sidebarExtra = '';

    // NEW POST
    // Ensure user has access to post in this category
    $canPost = empty($urlParams['channelsCategoryID']) || !empty($categories[$urlParams['channelsCategoryID']]);
    if ($canPost && isActionAccessible($guid, $connection2, '/modules/Channels/posts_manage_add.php')) {
        $form = Form::create('post', $session->get('absoluteURL').'/modules/Channels/posts_manage_addProcess.php');
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('source', 'channels');
        $form->addHiddenValue('channelsCategoryID', $urlParams['channelsCategoryID']);

        $postLength = $container->get(SettingGateway::class)->getSettingByScope('Channels', 'postLength');
        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT role LIKE '%- Left' ORDER BY gibbonCourseClass.name";
        $col = $form->addRow()->addColumn();
//        $col->addLabel('gibbonCourseClassID', __('Class'))->description(__('Select class within a course/Subject.'));
        $col = $form->addRow()->addClass('class bg-blue-100');
        $col->addLabel('gibbonCourseClassID', __('Classes'))->description(__('Select course/Subject & class for this post.'));
        $col->addCheckbox('gibbonCourseClassID')->fromQuery($pdo, $sql, $data);
        $row = $form->addRow()->addDetails()->summary(__('Add Post'));
            $row->addEditor('post')->setID('newPost')->required()->setRows(6)->addClass('font-sans text-sm')->maxLength($postLength);
        $row = $form->addRow()->addDetails()->summary(__('Add Photos'));
            $row->addFileUpload('attachments')->accepts('.jpg,.jpeg,.gif,.png')->uploadMultiple(true);

        // Categories
        if (!empty($urlParams['channelsCategoryID'])) {
            $form->addHiddenValue('channelsCategoryIDList', $urlParams['channelsCategoryID']);
        } else if (!empty($categories)) {
            $row = $form->addRow()->addDetails()->summary(__('Categories'));
                $row->addCheckbox('channelsCategoryIDList')->fromArray($categories);
        }

        $row = $form->addRow()->addSubmit(__('Post'));

        $formHTML = '<div class="column-no-break">';
        $formHTML .= '<h5 class="mt-4 mb-2 text-xs pb-0 ">';
        $formHTML .= !empty($urlParams['category'])
            ? __('New Post in {category}', ['category' => $urlParams['category']])
            : __('New Post');
        $formHTML .= '</h5>';
        $formHTML .= $form->getOutput();
        $formHTML .= '</div>';

        $sidebarExtra .= $formHTML;
    }

    // RECENT TAGS
    $tags = $container->get(PostTagGateway::class)->selectRecentTagsBySchoolYear($gibbonSchoolYearID)->fetchAll(\PDO::FETCH_COLUMN, 0);
    $sidebarExtra .= $page->fetchFromTemplate('tags.twig.html', [
        'tags' => $tags,
    ]);

    $session->set('sidebarExtra', $sidebarExtra);
}
?>
<script>
    $(document).ready(function() {
        var pageNum = <?php echo $channels->getPage(); ?>;
        var pageTotal = <?php echo $channels->getPageCount(); ?>;

        $('#loadPosts').click(function() {
            pageNum++;

            $.ajax({
                url: "<?php echo $session->get('absoluteURL'); ?>/modules/Channels/channelsAjax.php",
                data: {
                    channelsCategoryID: '<?php echo $urlParams['channelsCategoryID']; ?>',
                    page: pageNum,
                },
                type: 'POST',
                success: function(data) {
                    if (data) {
                        $('#channels').append(data);

                        if (pageNum >= pageTotal) {
                            $('#loadPosts').remove();
                        }
                    }
                },
            });
        });

        $('.image-container').magnificPopup({
            type: 'image',
            delegate: 'a',
            gallery: {
                enabled: true
            },
            image: {
                titleSrc: 'data-caption',
            }
        });
    });
</script>
