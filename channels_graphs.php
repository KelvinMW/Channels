<?php
//$form = Form::create('HomeworkStatistics', $session->get('absoluteURL').'/index.php?q=/modules/Channels/chennels_graphs.php');
//$form->addHiddenValue('address', $session->get('address'));
//$row = $form->addRow();
//    $row->addLabel('startDate', __('Start Date'));
//    $row->addDate('startDate')->setValue(Format::date($startDate))->required();
//    $row = $form->addRow();
//    $row->addLabel('endDate', __('End Date'));
//    $row->addDate('endDate')->setValue(Format::date($endDate))->required();
//    $row->addLabel('cartegoryId', __('cartegoryFilter'));
//    $row->add
//    $row->addLabel('gibbonCourseClassID', __('Form Group'));
//    $row->add
//    $form->addRow()->addSubmit();
//echo $form->getOutput();
echo '<!DOCTYPE html>
<html>
<head>
    <title>Graph</title>
    <!-- include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <form id="filtersForm">
        <label for="categoryFilter">Category:</label>
        <input type="text" id="categoryFilter" name="categoryId">
        <label for="courseClassFilter">Course Class:</label>
        <input type="text" id="courseClassFilter" name="courseClassId">
        <label for="startDate">Start Date:</label>
        <input type="date" id="startDate" name="startDate">
        <label for="endDate">End Date:</label>
        <input type="date" id="endDate" name="endDate">
        <button type="submit">Apply Filters</button>
    </form>
    ';
    echo "<canvas id='myChart'></canvas>";
   // $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/channels_fetch_graphs_data.php?viewBy=$viewBy&subView=$subView&address=".$session->get('address'));
    ?>

<script>
    // Create filters form
        let filtersForm = document.getElementById('filtersForm');

        // Fetch data and create graph when form is submitted
        filtersForm.addEventListener('submit', function(event) {
            event.preventDefault();

            let formData = new FormData(filtersForm);
            let params = new URLSearchParams(formData).toString();
            $URL = $session->get('absoluteURL')."/index.php?q=/modules/Channels/channel_fetch_graphs_data.php";
            //&gibbonPlannerEntryID=$AI&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitID=".$_POST['gibbonUnitID']."&date=$date&viewableParents=$viewableParents&viewableStudents=$viewableStudents&name=$name&summary=$summary&return=success1
            fetch($URL + params)
                .then(response => response.json())
                .then(data => {
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
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                });
        });
    </script>
</body>
</html>