<?php
namespace Gibbon\Module\Channels\Tables;

class ChannelsData
{
    protected $pdo;

    public function __construct(\Gibbon\Database\Connection $pdo)
    {
        $this->pdo = $pdo->getConnection();
    }
    
    public function getChannelsData($startDate, $endDate,$categoryId, $courseClassId){

    }
    public function getHomeworkData($startDate, $endDate)
    {
        $data = array('startDate'=>$startDate, 'endDate'=>$endDate);
        $sql = "SELECT gibbonCourse.name as CourseName, gibbonCourseClass.name as class, date, title, gibbonPerson.preferredName as preferredName, homeworkDetails,homeworkDueDateTime, gibbonPersonIDCreator
                FROM gibbonPlannerEntry
                JOIN gibbonPerson ON gibbonPlannerEntry.gibbonPersonIDCreator = gibbonPerson.gibbonPersonID
                JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
                JOIN gibbonCourse ON gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID
                WHERE gibbonPlannerEntry.date>=:startDate AND gibbonPlannerEntry.date <= :endDate
                ORDER BY date ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $stmt->fetchAll();
    }
    public function getHomeworkDataPosts($startDate, $endDate)
    {
        $data = array('startDate'=>$startDate, 'endDate'=>$endDate);
        $sql = "SELECT gibbonCourse.name as CourseName, gibbonCourseClass.name as class, date, title, gibbonPerson.preferredName as preferredName, homeworkDetails,homeworkDueDateTime, gibbonPersonIDCreator
                FROM gibbonPlannerEntry
                JOIN gibbonPerson ON gibbonPlannerEntry.gibbonPersonIDCreator = gibbonPerson.gibbonPersonID
                JOIN gibbonCourseClass ON gibbonPlannerEntry.gibbonCourseClassID = gibbonCourseClass.gibbonCourseClassID
                JOIN gibbonCourse ON gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID
                WHERE gibbonPlannerEntry.date>=:startDate AND gibbonPlannerEntry.date <= :endDate
                ORDER BY date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return $stmt->fetchAll();
    }

}
