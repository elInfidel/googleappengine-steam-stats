$(document).ready(function() {

    // Listen to server for information.
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var json = JSON.parse(this.responseText);

            // Create chart
            var ctx = document.getElementById("chart").getContext("2d");
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: json['names'],
                    datasets: json['datasets']
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        }
    };

    xmlhttp.open("GET", "ajax_handler.php?type=" + "steam_data", true);
    xmlhttp.send();
});