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

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Channels\Domain\PostGateway;
use Gibbon\Module\Channels\Domain\PostTagGateway;
use Gibbon\Module\Channels\Domain\PostAttachmentGateway;
use Gibbon\Module\Channels\Domain\CategoryGateway;
use Gibbon\Data\Validator;

$_POST['address'] = '/modules/Channels/channels_postProcess.php';

require_once '../../gibbon.php';

$source = $_POST['source'] ?? '';
$category = $_POST['category'] ?? '';
$channelsCategoryID = $_POST['channelsCategoryID'] ?? '';
$URL = $source == 'channels'
    ? $session->get('absoluteURL').'/index.php?q=/modules/Channels/channels.php&category='.$category.'&channelsCategoryID='.$channelsCategoryID
    : $session->get('absoluteURL').'/index.php?q=/modules/Channels/posts_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Channels/posts_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $postGateway = $container->get(PostGateway::class);
    $postTagGateway = $container->get(PostTagGateway::class);
    $postAttachmentGateway = $container->get(PostAttachmentGateway::class);
    $categoryGateway = $container->get(CategoryGateway::class);

    $partialFail = false;

    // Sanitize the whole $_POST array
    $_POST = $container->get(Validator::class)->sanitize($_POST);


    $data = [
        'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID'),
        'gibbonPersonID'        => $session->get('gibbonPersonID'),
        'post'                  => $_POST['post'] ?? '',
        'gibbonCourseClassID'   => $_POST['gibbonCourseClassID'] ?? null,
        'channelsCategoryIDList'  => $_POST['channelsCategoryIDList'] ?? null,
        'timestamp'             => date('Y-m-d H:i:s'),
    ];

    $data['gibbonCourseClassID'] = is_array($data['gibbonCourseClassID'])
    ? implode(',', $data['gibbonCourseClassID'])
    : $data['gibbonCourseClassID'];

    $data['channelsCategoryIDList'] = is_array($data['channelsCategoryIDList'])
        ? implode(',', $data['channelsCategoryIDList'])
        : $data['channelsCategoryIDList'];

    // Validate the required values are present
    if (empty($data['post'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Create the post
    $channelsPostID = $postGateway->insert($data);

    // Auto-detect tags used in this post
    $matches = [];
    if (preg_match_all('/[#]+([\w]+)/iu', $data['post'], $matches)) {
        foreach ($matches[1] as $tag) {
            $data = [
                'channelsPostID' => $channelsPostID,
                'tag' => $tag,
            ];

            $postTagGateway->insertAndUpdate($data, $data);
        }
    }

    // Handle file upload for multiple attachments, including resizing images & generating thumbnails
    if (!empty($_FILES['attachments']['tmp_name'][0])) {
        $fileUploader = new FileUploader($pdo, $session);
        $absolutePath = $session->get('absolutePath');
        $maxImageSize = $container->get(SettingGateway::class)->getSettingByScope('Channels', 'maxImageSize');

        foreach ($_FILES['attachments']['name'] as $index => $name) {
            $file = array_combine(array_keys($_FILES['attachments']), array_column($_FILES['attachments'], $index));
            $attachment = $fileUploader->uploadAndResizeImage($file, 'channelsPhoto', $maxImageSize, 90);

            if (!empty($attachment)) {
                $thumbPath = $absolutePath.'/'.str_replace('channelsPhoto', 'channelsThumb', $attachment);
                $thumbnail = $fileUploader->resizeImage($absolutePath.'/'.$attachment, $thumbPath, 650);

                $data = [
                    'channelsPostID' => $channelsPostID,
                    'attachment'   => $attachment,
                    'thumbnail'    => str_replace($absolutePath.'/', '', $thumbnail),
                    'type'         => 'Image',
                ];

                $postAttachmentGateway->insert($data);
            } else {
                $partialFail = true;
            }
        }
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$channelsPostID";

    header("Location: {$URL}");
}
