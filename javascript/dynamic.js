$(document).ready(function() {
    displayGenres();
});

function displayGenres() {
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
            displayGraph(genreLabels, genreData, '# of Games')
        }
    };
    xmlhttp.open("GET", "ajax_handler.php?type=" + "steam_data", true);
    xmlhttp.send();
}

function displayCategories() {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var json = JSON.parse(this.responseText);

            var categoryLabels = [];
            var categoryData = [];

            // Genre data
            var categories = Object.keys(json['categories']);
            for (var i = 0; i < categories.length; i++) {
                categoryLabels.push(categories[i]);
                categoryData.push(json['categories'][categories[i]]);
            }
            displayGraph(categoryLabels, categoryData, '# of Games')
        }
    };
    xmlhttp.open("GET", "ajax_handler.php?type=" + "steam_data", true);
    xmlhttp.send();
}

var chart;

function displayGraph(pLabels, pData, pLabel) {
    if (chart) {
        chart.destroy();
    }

    //Create our chart
    var context = document.getElementById("chart").getContext("2d");
    chart = new Chart(context, {
        type: 'horizontalBar',
        data: {
            labels: pLabels,
            datasets: [{
                label: pLabel,
                data: pData,
                backgroundColor: '#8FB93B'
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