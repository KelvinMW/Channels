<?php
//the issue on PHP is Warning: Array to string conversion in /shared/httpd/sis.loc/htdocs/modules/Channels/ChannelsHelper.php on line 25
function fetchGraphsData($connection, $categoryId, $courseClassId, $startDate, $endDate) {
    $params = array();
    $whereClause = "WHERE DATE(timestamp) BETWEEN :startDate AND :endDate";
    $params = ['startDate' => $startDate, 'endDate' => $endDate];

    if (!empty($categoryId)) {
        $whereClause .= " AND FIND_IN_SET(:categoryId, channelsCategoryIDList)";
        $params['channelsCategoryIDList'] = $categoryId;
    }
    if (!empty($courseClassId)) {
        $whereClause .= " AND gibbonCourseClassID = :courseClassId";
        $params['courseClassId'] = $courseClassId;
    }

    $query = "SELECT DATE(timestamp) as date, channelsCategoryIDList as categoryIDs, COUNT(*) as count
            FROM channelsPost 
            $whereClause
            GROUP BY date, categoryIDs";

    try {
        $stmt = $connection->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($result as $row) {
            $date = $row['date'];
            if (is_string($row['channelsCategoryIDList'])) {
                $categoryIDs = explode(',', $row['categoryIDs']);
            } else {
                // Log or output the unexpected data
                var_dump($row['categoryIDs']);
                continue;
            }            
            $categoryIDs = explode(',', $row['categoryIDs']);
            foreach ($categoryIDs as $categoryID) {
                if (!isset($data[$date][$categoryID])) {
                    $data[$date][$categoryID] = 0;
                }
                $data[$date][$categoryID] += $row['count'];
            }
        }
        return $data;

    } catch (PDOException $e) {
        // Log error if you need
        return false;
    }
}

?>
