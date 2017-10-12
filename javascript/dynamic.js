$(document).ready(function() {

    // Listen to server for information.
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var json = JSON.parse(this.responseText);

            var genreLabels = [];
            var genreData = [];

            // Genre data
            var genres = Object.keys(json['genres']);
            for (var i = 0; i < genres.length; i++) {
                genreLabels.push(genres[i]);
                genreData.push(json['genres'][genres[i]]);
            }

            // Category data
            //var keys = Object.keys(yourObject);
            //for (var i = 0; i < keys.length; i++) {
            //    var key = keys[i];
            //    console.log(key, yourObject[key]);
            //}

            // Create chart
            var ctx = document.getElementById("chart").getContext("2d");
            var myChart = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: genreLabels,
                    datasets: [{
                        label: '# of Games',
                        data: genreData
                    }]
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