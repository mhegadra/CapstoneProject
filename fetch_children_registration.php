<?php
// fetch_children_registration.php
require 'database_connection.php'; // Include your database connection

$query = "SELECT COUNT(id) AS count, MONTH(registration_date) AS month
          FROM children
          WHERE YEAR(registration_date) = YEAR(CURRENT_DATE)
          GROUP BY MONTH(registration_date)";

$result = $conn->query($query);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        'month' => $row['month'],
        'count' => $row['count']
    ];
}

echo json_encode($data);
?>
<!-- Include Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch('fetch_children_registration.php')
            .then(response => response.json())
            .then(data => {
                const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                const labels = data.map(entry => months[entry.month - 1]);
                const counts = data.map(entry => entry.count);

                const ctx = document.getElementById('childrenRegistrationChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Children Registered',
                            data: counts,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                beginAtZero: true
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching data:', error));
    });
</script>
