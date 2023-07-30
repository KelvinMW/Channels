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
use Gibbon\Module\Channels\Domain\PostGateway;
use Gibbon\Module\Channels\Domain\PostTagGateway;
use Gibbon\Module\Channels\Domain\PostAttachmentGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$channelsPostID = $_POST['channelsPostID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Channels/posts_manage_edit.php&channelsPostID='.$channelsPostID;

if (isActionAccessible($guid, $connection2, '/modules/Channels/posts_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {

    // Proceed!
    $postGateway = $container->get(PostGateway::class);
    $postTagGateway = $container->get(PostTagGateway::class);
    $postAttachmentGateway = $container->get(PostAttachmentGateway::class);
    $partialFail = false;

    // Sanitize the whole $_POST array
    $_POST = $container->get(Validator::class)->sanitize($_POST);

    $data = [
        'post' => $_POST['post'] ?? '',
        'channelsCategoryIDList'  => (!empty($_POST['channelsCategoryIDList']) &&(is_array($_POST['channelsCategoryIDList'])) ? implode(",", $_POST['channelsCategoryIDList']) : null)
    ];

    // Validate the required values are present
    if (empty($channelsPostID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$postGateway->exists($channelsPostID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $postGateway->update($channelsPostID, $data);

    // Auto-detect tags used in this post
    $matches = [];
    if (preg_match_all('/[#]+([\w]+)/iu', $data['post'], $matches)) {
        $postTagGateway->deleteWhere(['channelsPostID' => $channelsPostID]);

        foreach ($matches[1] as $tag) {
            $data = [
                'channelsPostID' => $channelsPostID,
                'tag' => $tag,
            ];

            $postTagGateway->insert($data);
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

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
