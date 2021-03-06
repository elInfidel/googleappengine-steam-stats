<?php

include 'steam.php';

session_start();

?>

<html>
    <head>
        <meta charset="UTF-8">

        <link href="https://fonts.googleapis.com/css?family=Open+Sans|Roboto" rel="stylesheet">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.min.js"></script>
        <script src="/javascript/dynamic.js"></script>
        
        <link type="text/css" rel="stylesheet" href="/stylesheets/reset.css"/>
        <link type="text/css" rel="stylesheet" href="/stylesheets/styles.css"/>

    </head>

    <body>

    <h1 id="page_title">Steam Cloud Statistics</h1>

    <?php if( isset($_POST["steam_user"])){ ?>
        
        <?php 
            $_SESSION["steam_user"] = $_POST["steam_user"];
            $steamAPI = new Steam($_SESSION["steam_user"]);
            
            // Here we store a bunch of data related to the entered steam account.
            $accountSummary = $steamAPI->getPlayerSummary();
            $level = $steamAPI->getSteamLevel();
            $friends = $steamAPI->getFriendList();
            $gamesInfo = $steamAPI->getOwnedGames();

            $isOnline = $accountSummary->personastate == 1;
        ?>
        
        <section id="player_section">

            <div id="player_section_image">
                <img class="status_img" src="<?php echo $accountSummary->avatarfull; ?>" alt="Steam Avatar" style="<?php if($isOnline) echo "border-color: #8FB93B;"; else echo "border-color: #519EBC;"; ?>">
            </div>
            <div id="player_section_data">
                <h2><?php echo $accountSummary->personaname; ?> </h2>
                <form action="<?php echo $accountSummary->profileurl; ?>">
                <input type="submit" value="Profile Page" formtarget="_blank"/>
                </form>
                <div>Level  <?php echo $level; ?> </div>
                <div>Friend: <?php echo count($friends); ?> </div>
                <div>Games: <?php echo $gamesInfo->game_count; ?> </div>
                <div>Date Created: <?php echo date('d/m/Y', $accountSummary->timecreated); ?> </div>
            </div>

            <div id="nav_bar">
                <h2 class="nav_title">Players</h2>
                <ul>
                    <li>Coming Soon</li>
                </ul> 
                <h2 class="nav_title">Games</h2>
                <ul>
                    <li><a href="" onclick="displayGenres();return false;">Genres</a></li>
                    <li><a href=""onclick="displayCategories();return false;">Categories</a></li>
                </ul> 
            </div>

        </section>

        <section id="stats_section">
            <canvas id="chart"></canvas>
        </section>


    <?php }else{ ?>

        <div class="panel" id="intro_container">

            <h2 id="intro_suggest_input">Input your Steam ID</h2>

            <form action="/index.php" method="post">
            <div><input type="text" name="steam_user" placeholder="Steam ID"></div>
            </form>

        </div>

        <div class="panel" id="info_container">
            <h2>Description</h2>
            <p>
                This website was created to discover trends in user statistics on Steam.
                The idea is that, after several users visit the site, data crunching in the cloud
                will begin to show interesting data on the usage of Steam.
            </p>
        </div>

    <?php } ?>

    </body>

 <footer>
 </footer>

<html>