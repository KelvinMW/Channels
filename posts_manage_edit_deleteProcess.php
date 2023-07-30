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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Channels\Domain\PostAttachmentGateway;

$_POST['address'] = '/modules/Channels/posts_manage_edit.php';

require_once '../../gibbon.php';

$channelsPostID = $_GET['channelsPostID'] ?? '';
$channelsPostAttachmentID = $_GET['channelsPostAttachmentID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Channels/posts_manage_edit.php&channelsPostID='.$channelsPostID;

if (isActionAccessible($guid, $connection2, '/modules/Channels/posts_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $postAttachmentGateway = $container->get(PostAttachmentGateway::class);
    $absolutePath = $session->get('absolutePath');
    $partialFail = false;
  
    // Validate the required values are present
    if (empty($channelsPostID) || empty($channelsPostAttachmentID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $attachment = $postAttachmentGateway->getByID($channelsPostAttachmentID);
    if (empty($attachment)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Delete the image files
    if (!empty($attachment['attachment'])) {
        $partialFail &= !unlink($absolutePath.'/'.$attachment['attachment']);
    }

    if (!empty($attachment['thumbnail'])) {
        $partialFail &= !unlink($absolutePath.'/'.$attachment['thumbnail']);
    }

    // Delete the record
    $deleted = $postAttachmentGateway->delete($channelsPostAttachmentID);

    $URL .= $partialFail || !$deleted
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
