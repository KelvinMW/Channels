<?php
//This code throws Error("The response is not a valid JSON:" + text); to the channels_graphs.php after modifying as you suggested
//How do I modify this code to achieve the objective
ob_start();
header('Content-Type: application/json');

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
//use Gibbon\Module\Channels\Domain\PostGateway;
//use Gibbon\Module\Channels\Domain\PostTagGateway;
//use Gibbon\Module\Channels\Domain\PostAttachmentGateway;
use Gibbon\Module\Channels\Domain\CategoryGateway;
use Gibbon\Module\Channels\Domain\CategoryViewedGateway;
use Gibbon\FileUploader;
use Gibbon\Data\Validator;

//require_once '../../gibbon.php';
require_once __DIR__ . '/../../gibbon.php';
require_once 'ChannelsHelper.php'; 
if (isActionAccessible($guid, $connection2, '/modules/Channels/channels_fetch_graphs_data.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
} else {

$session = $container->get('session');
$gibbon->session = $session;
if($_POST){
$categoryId = $_POST['channelsCategoryIDList'] ?? null;
$courseClassId = $_POST['classes'] ?? null;
$startDate = $_POST['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_POST['endDate'] ?? date('Y-m-d');
} else{
    $cartegoryId = null;
    $courseClassID= null;
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate =     date('Y-m-d');
}
$data = fetchGraphsData($connection2, $categoryId, $courseClassId, $startDate, $endDate);

if ($data) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch data']);
}

}
?>
