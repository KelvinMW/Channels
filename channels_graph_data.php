<?php
// channels_graphs_data.php
//require_once __DIR__ . '/../../gibbon.php';
require_once 'ChannelsHelper.php';

try {
    // Your logic here
    if ($_POST) {
        $categoryId = $_POST['channelsCategoryIDList'] ?? [];
        $courseClassId = $_POST['classes'] ?? [];
        $startDate = $_POST['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
        $endDate = $_POST['endDate'] ?? date('Y-m-d');
    
        $data = fetchGraphsData($connection2, $categoryId, $courseClassId, $startDate, $endDate);
    exit;
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to fetch data']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

?>