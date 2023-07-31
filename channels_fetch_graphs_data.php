<?php
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Channels\Domain\PostGateway;
use Gibbon\Module\Channels\Domain\PostTagGateway;
use Gibbon\Module\Channels\Domain\PostAttachmentGateway;
use Gibbon\Module\Channels\Domain\CategoryGateway;
use Gibbon\Module\Channels\Domain\CategoryViewedGateway;
    // Assuming $connection2 is a valid PDO connection
    $categoryId = $_GET['categoryId'] ?? null;
    $courseClassId = $_GET['courseClassId'] ?? null;
    $startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
    $endDate = $_GET['endDate'] ?? date('Y-m-d');

    $whereClause = "WHERE DATE(timestamp) BETWEEN :startDate AND :endDate";
    $params = ['startDate' => $startDate, 'endDate' => $endDate];

    if ($categoryId !== null) {
        $whereClause .= " AND FIND_IN_SET(:categoryId, channelsCategoryIDList)";
        $params['categoryId'] = $categoryId;
    }

    if ($courseClassId !== null) {
        $whereClause .= " AND gibbonCourseClassID = :courseClassId";
        $params['courseClassId'] = $courseClassId;
    }

    $query = "SELECT DATE(timestamp) as date, channelsCategoryIDList as categoryIDs, COUNT(*) as count
            FROM channelsPost 
            $whereClause
            GROUP BY date, categoryIDs";

    $stmt = $connection2->prepare($query);
    $stmt->execute($params);

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // We will create an associative array where keys are dates and values are another associative array 
    // where keys are category IDs and values are counts.
    $data = [];
    foreach($result as $row) {
        $date = $row['date'];
        $categoryIDs = explode(',', $row['categoryIDs']);  // If categories are comma-separated
        foreach($categoryIDs as $categoryID) {
            if (!isset($data[$date][$categoryID])) {
                $data[$date][$categoryID] = 0;
            }
            $data[$date][$categoryID] += $row['count'];
        }
    }

    echo json_encode($data);
?>