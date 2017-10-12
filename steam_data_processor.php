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
        syslog(LOG_ERR, 'Failed to process steam data.');
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

        syslog(LOG_INFO, count($games).' applications currently exist on Steam.');

        // Compare apps in list to what we have, adding/removing as required.
        $newApps = 0;
        $stmt = $db->prepare("INSERT IGNORE INTO app_proc (appid) VALUES (:appid)");
        foreach($games as $game)
        {
            try 
            {
                $stmt->bindParam(':appid', $game->appid);
                $stmt->execute();
                ++$newApps;
            }
            catch( PDOException $e ) 
            {
                syslog(LOG_ERR, $e->getMessage( ));
                return false;
            }
        }
        $stmt = null;

        syslog(LOG_INFO, 'Registered '.$newApps.' new applications.');

        // Process a maximum amount games from our unprocessed list.
        // Store their data and set them to processed.
        $pCount = 0;
        foreach ($db->query('SELECT appid FROM app_proc WHERE is_processed = 0') as $row) 
        {
            if($pCount >= 200) break;

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

                    $stmt = $db->prepare("
                    INSERT IGNORE INTO applications (appid, type, name, is_free, recommendation_count, achievement_count, price) 
                    VALUES (:appid, :type, :name, :is_free, :recommendation_count, :achievement_count, :price)");
                    // Attempt to add the application to our database.
                    $stmt->bindParam(':appid', $game_data->steam_appid);
                    $stmt->bindParam(':type', $game_data->type);
                    $stmt->bindParam(':name', $game_data->name);
                    $stmt->bindParam(':is_free', $is_free);
                    $stmt->bindParam(':recommendation_count', $game_data->recommendations->total);
                    $stmt->bindParam(':achievement_count', $game_data->achievements->total);
                    $stmt->bindParam(':price', $game_data->price_overview->final);
                    $stmt->execute();
                    $stmt = null;

                    // genre
                    $stmt = $db->prepare("
                    INSERT IGNORE INTO genres (genreid, genre) 
                    VALUES (:genreid, :genre)");
                    foreach($game_data->genres as $genre){
                        $stmt->bindParam(':genreid', $genre->id);
                        $stmt->bindParam(':genre', $genre->description);
                        $stmt->execute();
                    }
                    $stmt = null;

                    $stmt = $db->prepare("
                    INSERT IGNORE INTO game_genres (appid, genreid) 
                    VALUES (:appid, :genreid)");
                    foreach($game_data->genres as $genre){
                        $stmt->bindParam(':appid', $game_data->steam_appid);
                        $stmt->bindParam(':genreid', $genre->id);
                        $stmt->execute();
                    }
                    $stmt = null;

                    // category
                    $stmt = $db->prepare("
                    INSERT IGNORE INTO categories (categoryid, category) 
                    VALUES (:categoryid, :category)");
                    foreach($game_data->categories as $category){
                        $stmt->bindParam(':categoryid', $category->id);
                        $stmt->bindParam(':category', $category->description);
                        $stmt->execute();
                    }
                    $stmt = null;

                    $stmt = $db->prepare("
                    INSERT IGNORE INTO game_categories (appid, categoryid) 
                    VALUES (:appid, :categoryid)");
                    foreach($game_data->categories as $category){
                        $stmt->bindParam(':appid', $game_data->steam_appid);
                        $stmt->bindParam(':categoryid', $category->id);
                        $stmt->execute();
                    }
                    $stmt = null;
                }
                catch( PDOException $e ) 
                {
                    syslog(LOG_ERR, $e->getMessage( ));
                    return false;
                }
            }
            $db->query('UPDATE app_proc SET is_processed = 1 WHERE appid = '.$appid);
            ++$pCount;
        }

        syslog(LOG_INFO, 'Processed '.$pCount.' new applications. ( We have a limit of 200 per 5 minutes. )');

        // Update pre-calculated values based on new data.
        // Store the data calculated in google bucket.
        $apps_summary = new stdClass;
        $apps_summary->app_count = $db->query('SELECT COUNT(appid) FROM app_proc')->fetchColumn();
        $apps_summary->avg_price = $db->query('SELECT AVG(price) FROM applications WHERE price > 0')->fetchColumn() * 0.01;
        $apps_summary->total_price = $db->query('SELECT SUM(price) FROM applications WHERE price > 0')->fetchColumn();
        $apps_summary->num_free_games = $db->query('SELECT COUNT(is_free) FROM applications WHERE is_free = 1')->fetchColumn();

        file_put_contents("gs://".getenv('BUCKET_NAME')."/apps_summary.json", json_encode($apps_summary));

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
            type VARCHAR(15) NOT NULL,
            name VARCHAR(70) NOT NULL,
            is_free BOOLEAN NOT NULL,
            recommendation_count INTEGER NOT NULL DEFAULT 0,
            achievement_count INTEGER NOT NULL DEFAULT 0,
            price INTEGER,
            PRIMARY KEY(appid)
        );');
    
        $db->query('CREATE TABLE IF NOT EXISTS genres
        (
            genreid INTEGER PRIMARY KEY,
            genre VARCHAR(50)
        );');
    
        $db->query('CREATE TABLE IF NOT EXISTS categories
        (
            categoryid INTEGER PRIMARY KEY,
            category VARCHAR(50)
        );');

        $db->query('CREATE TABLE IF NOT EXISTS game_genres
        (
            appid BIGINT,
            genreid INTEGER,
            FOREIGN KEY (appid) REFERENCES applications(appid) ON DELETE CASCADE,
            FOREIGN KEY (genreid) REFERENCES genres(genreid) ON DELETE CASCADE,
            PRIMARY KEY (appid, genreid)
        );');

        $db->query('CREATE TABLE IF NOT EXISTS game_categories
        (
            appid BIGINT NOT NULL,
            categoryid INTEGER NOT NULL,
            FOREIGN KEY (appid) REFERENCES applications(appid) ON DELETE CASCADE,
            FOREIGN KEY (categoryid) REFERENCES categories(categoryid) ON DELETE CASCADE,
            PRIMARY KEY (appid, categoryid)
        );');

        $db->query('CREATE TABLE IF NOT EXISTS app_proc
        (
            appid BIGINT PRIMARY KEY,
            is_processed BOOLEAN NOT NULL DEFAULT 0,
            date_last_processed int(11)
        );');
    }
?>

