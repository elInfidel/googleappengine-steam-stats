<?php
    require 'steam.php';

    $dsn = getenv('MYSQL_DSN');
    $user = getenv('MYSQL_USER');
    $password = getenv('MYSQL_PASSWORD');

    // Create database context
    $db = new PDO($dsn, $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    database_check($db);

    // Do processing
    if(process_users($db) && process_applications($db)) 
    {
        syslog(LOG_INFO, 'Steam data processing job completed.');
        http_response_code(200);
        
    }
    else
    {
        syslog(LOG_ERROR, 'Failed to process steam data.');
        http_response_code(404);
    }

    // clean up db context.
    $db = null;

    function process_users($db)
    {
        return true;
    }

    function process_applications($db)
    {
        // Grab the latest game list from steam.
        $games = Steam::getSteamGameList();

        // Compare apps in list to what we have, adding/removing as required.
        foreach($games as $game)
        {
            try 
            {
                $stmt = $db->prepare("
                INSERT IGNORE INTO app_proc (appid) 
                VALUES (:appid)");
                $stmt->bindParam(':appid', $game->appid);
                $stmt->execute();
                $stmt = null;
            }
            catch( PDOException $Exception ) 
            {
                echo $Exception->getMessage( );
                return false;
            }
        }

        // Process a maximum of 150 games from our unprocessed list.
        // Store their data and set them to processed.
        $pCount = 0;
        foreach ($db->query('SELECT appid FROM app_proc WHERE is_processed IS FALSE') as $row) 
        {
            if($pCount > 150) break;

            $appid = $row['appid'];
            $game = Steam::getGameInfo($appid)->{$appid};
            
            if($game->success)
            {
                $game_data = $game->data;

                try 
                {
                    // We need to parse the string in json but also need something
                    // that can be passed by reference so we create a variable to hold the stored data.
                    $is_free = ($game_data->is_free === 'true') ? 1 : 0;

                    // Attempt to add the application to our database.
                    $stmt = $db->prepare("
                    INSERT IGNORE INTO applications (appid, type, name, is_free, recommendation_count, achievement_count, price) 
                    VALUES (:appid, :type, :name, :is_free, :recommendation_count, :achievement_count, :price)");
                    $stmt->bindParam(':appid', $game_data->steam_appid);
                    $stmt->bindParam(':type', $game_data->type);
                    $stmt->bindParam(':name', $game_data->name);
                    $stmt->bindParam(':is_free', $is_free);
                    $stmt->bindParam(':recommendation_count', $game_data->recommendations->total);
                    $stmt->bindParam(':achievement_count', $game_data->achievements->total);
                    $stmt->bindParam(':price', $game_data->price_overview->final);
                    $stmt->execute();
                    $stmt = null;

                     // Attempt to add the application to our database.
                     $stmt = $db->prepare("
                     INSERT IGNORE INTO applications (appid, type, name, is_free, recommendation_count, achievement_count, price) 
                     VALUES (:appid, :type, :name, :is_free, :recommendation_count, :achievement_count, :price)");
                     $stmt->bindParam(':appid', $game_data->steam_appid);
                     $stmt->bindParam(':type', $game_data->type);
                     $stmt->bindParam(':name', $game_data->name);
                     $stmt->bindParam(':is_free', $is_free);
                     $stmt->bindParam(':recommendation_count', $game_data->recommendations->total);
                     $stmt->bindParam(':achievement_count', $game_data->achievements->total);
                     $stmt->bindParam(':price', $game_data->price_overview->final);
                     $stmt->execute();
                     $stmt = null;
                }
                catch( PDOException $Exception ) 
                {
                    return false;
                }
            }

            $db->query('UPDATE app_proc SET is_processed = TRUE WHERE appid = '.$appid);
            ++$pCount;
        }

        syslog(LOG_INFO, 'Processed '.$pCount.' new applications.');

        // Update pre-calculated values based on new data.
        $apps_summary = new stdClass;
        $apps_summary->appCount = $db->query('SELECT COUNT(appid) AS app_count FROM app_proc')->fetchColumn();

        return true;
    }

    function database_check($db)
    {
        $db->query('CREATE TABLE IF NOT EXISTS users
        (
            steamid BIGINT,
            name VARCHAR(70),
            user_level INTEGER,
            game_count INTEGER,
            friend_count INTEGER,
            PRIMARY KEY(steamid)
        );');
    
        $db->query('CREATE TABLE IF NOT EXISTS applications
        (
            appid BIGINT,
            type VARCHAR(15),
            name VARCHAR(70),
            is_free BOOLEAN,
            recommendation_count INTEGER NOT NULL DEFAULT 0,
            achievement_count INTEGER NOT NULL DEFAULT 0,
            price INTEGER,
            PRIMARY KEY(appid)
        );');
    
        $db->query('CREATE TABLE IF NOT EXISTS genres
        (
            genreid INTEGER PRIMARY KEY AUTO_INCREMENT,
            genre VARCHAR(50)
        );');
    
        $db->query('CREATE TABLE IF NOT EXISTS game_genres
        (
            appid BIGINT,
            genreid INTEGER,
            FOREIGN KEY (appid) REFERENCES applications(appid) ON DELETE CASCADE,
            FOREIGN KEY (genreid) REFERENCES genres(genreid) ON DELETE CASCADE
        );');

        $db->query('CREATE TABLE IF NOT EXISTS categories
        (
            categoryid INTEGER PRIMARY KEY AUTO_INCREMENT,
            category VARCHAR(50)
        );');

        $db->query('CREATE TABLE IF NOT EXISTS game_categories
        (
            appid BIGINT,
            categoryid INTEGER,
            FOREIGN KEY (appid) REFERENCES applications(appid) ON DELETE CASCADE,
            FOREIGN KEY (categoryid) REFERENCES categories(categoryid) ON DELETE CASCADE
        );');

        $db->query('CREATE TABLE IF NOT EXISTS app_proc
        (
            appid BIGINT PRIMARY KEY,
            is_processed BOOLEAN NOT NULL DEFAULT 0,
            date_last_processed int(11),
            FOREIGN KEY (appid) REFERENCES applications(appid) ON DELETE CASCADE
        );');
        
    }
?>

