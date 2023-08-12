<?php
//code to display the graph on file name channels_graphs channelsCategoryIDList
error_reporting(E_ALL);
ini_set('display_errors', 1);
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Module\Homework\Tables\HomeworkData;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\School\FacilityGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Forms\Form;
use Gibbon\Http\Url;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Gibbon\Forms\Input\Button;
use Gibbon\Forms\Input\Input;
use Gibbon\Data\Validator;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Module\Planner\Forms\PlannerFormFactory;
use Gibbon\Module\Channels\Domain\PostGateway;
use Gibbon\Module\Channels\Domain\CategoryGateway;
use Gibbon\Module\Channels\Domain\CategoryViewedGateway;

require_once __DIR__ . '/../../gibbon.php';
require_once 'ChannelsHelper.php';

if (isActionAccessible($guid, $connection2, '/modules/Channels/channels_graphs.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
} else {

$settingGateway = $container->get(SettingGateway::class);
$postGateway = $container->get(PostGateway::class);
$categoryGateway = $container->get(CategoryGateway::class);
$categoryViewedGateway = $container->get(CategoryViewedGateway::class);
$gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
$categoryGateway = $container->get(CategoryGateway::class);
$categories = $categoryGateway->selectPostableCategoriesByRole($session->get('gibbonRoleIDCurrent'))->fetchKeyPair();
$session = $container->get('session');
$gibbon->session = $session;
$container->share(\Gibbon\Contracts\Services\Session::class, $session);
if($_POST){
    $categoryId = $_POST['channelsCategoryIDList'] ?? [];
    $courseClassId = $_POST['classes'] ?? [];
    $startDate = $_POST['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
    $endDate = $_POST['endDate'] ?? date('Y-m-d');
    $data = fetchGraphsData($connection2, $categoryId, $courseClassId, $startDate, $endDate);
    } else{
        $categoryId = [];
        $courseClassId= [];
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate =     date('Y-m-d');
        $data = [];
    }
$form = Form::create('ChannelsStatistics', $session->get('absoluteURL').'/index.php?q=/modules/Channels/channels_graphs.php');
$form->setID('filtersForm');
$form->addHiddenValue('address', $session->get('address'));
$row = $form->addRow();
    $row->addLabel('startDate', __('Start Date'));
    $row->addDate('startDate')->setValue(Format::date($startDate))->required();
$row = $form->addRow();
    $row->addLabel('endDate', __('End Date'));
    $row->addDate('endDate')->setValue(Format::date($endDate))->required();
    $year = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
    $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name
        FROM gibbonCourse
        JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourseClass.name";
    $row = $form->addRow()->addClass('class bg-blue-100');
    $row->addLabel('classes[]', __('Select Classes'));
    $row->addSelect('classes[]')->fromQuery($pdo, $sql, $year)->selectMultiple()->selected($courseClassId);
       // Categories
       if(!empty($categories)) {
        $row = $form->addRow()->addDetails()->summary(__('Categories'));
        $row->addCheckbox('channelsCategoryIDList')->fromArray($categories);
    }        
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    echo $form->getOutput();
    //expect a graph displaying but only the form above shows


    echo "<canvas id='myChart'></canvas>";
}
    ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.getElementById("filtersForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    let fetchfile ="<?php echo $session->get('absoluteURL') . '/index.php?q=/modules/Channels/channels_graph_data.php&' ?>";
    fetch(fetchfile, {
        method: 'POST',
        body: formData
    })

    .then(response => response.json())
    .then(data => {
        if (data.success) {
        let chartData = {};
        let labels = [];
        let datasets = [];

        for(let date in data) {
            labels.push(date);
            for(let category in data[date]) {
                if(!chartData[category]) chartData[category] = [];
                chartData[category].push(data[date][category]);
            }
        }

        let colors = ['red', 'blue', 'green', 'yellow', 'purple']; // Add more colors if needed

        if (Object.keys(data).length > colors.length) {
            // Handle the case for more categories than colors
            // Generate colors dynamically (as an example)
            for (let i = colors.length; i < Object.keys(data).length; i++) {
                colors.push('#' + Math.floor(Math.random()*16777215).toString(16));
            }
        }

        let colorIndex = 0;
        for(let category in chartData) {
            datasets.push({
                label: category,
                data: chartData[category],
                backgroundColor: colors[colorIndex]
            });
            colorIndex++;
        }

        // Create Chart.js graph
        const ctx = document.getElementById('myChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            }
        });
    }});
    })
    .catch(err => {
        console.error('Error:', err);
    });
</script>