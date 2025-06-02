// Sample data - replace this with data fetched from your database
const data = {
    labels: ['0-1 Month', '1-6 Months', '6-12 Months'],
    datasets: [{
        label: 'Number of Registered Children',
        data: [15, 30, 25], // Replace with actual data
        backgroundColor: [
            'rgba(255, 99, 132, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 206, 86, 0.2)'
        ],
        borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)'
        ],
        borderWidth: 1
    }]
};

// Create the pie chart
const ctx = document.getElementById('ageGroupPieChart').getContext('2d');
new Chart(ctx, {
    type: 'pie',
    data: data,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.label + ': ' + tooltipItem.raw;
                    }
                }
            }
        }
    }
});
