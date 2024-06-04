
<!doctype html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>ReSoC - Mur</title> 
        <meta name="author" content="Julien Falconnet">
        <link rel="stylesheet" href="style.css"/>
    </head>
    <body>
    <?php
        include("sources/header.php");
    ?>
        <div id="wrapper">
            <?php
            /**
             * Etape 1: Le mur concerne un utilisateur en particulier
             * La première étape est donc de trouver quel est l'id de l'utilisateur
             * Celui ci est indiqué en parametre GET de la page sous la forme user_id=...
             * Documentation : https://www.php.net/manual/fr/reserved.variables.get.php
             * ... mais en résumé c'est une manière de passer des informations à la page en ajoutant des choses dans l'url
             */
            //$userId =intval($_GET['user_id']);
            if (isset($_GET['user_id'])){
                $user_wall_id =intval($_GET['user_id']);
            }
            else{

                $user_wall_id =$_SESSION['connected_id'];
            }
            //echo "connectedID = " . $user_connectedID; 
            //echo "userID = " . $userId;
            ?>
            
            <?php
            /** Etape 2: se connecter à la base de donnée*/
            include("sources/connexion.php");
            ?>

            <aside>
                <?php
                /**
                 * Etape 3: récupérer le nom de l'utilisateur
                 */                
                $laQuestionEnSql = "SELECT * FROM users WHERE id= '$user_wall_id' ";
                include("sources/library.php");
                $user = $lesInformations->fetch_assoc();
                
                //echo "<pre>" . print_r($user, 1) . "</pre>";
                ?>
                <img src="user.jpg" alt="Portrait de l'utilisatrice"/>
                <section>
                    <h3>Présentation</h3>
                    <p>Sur cette page vous trouverez tous les message de l'utilisatrice : <?php echo $user["alias"] ?>
                        (n° <?php echo $user_wall_id ?>)
                    </p>
                </section>
                <article>
                
                    <?php
                    echo "<pre>" . print_r($_POST, 1) . "</pre>";

                    $enCoursDeTraitement = isset($_POST['jeVeuxTeSuivre']);
                    if ($enCoursDeTraitement)
                    {
                        
                        
                        $followed_connectedID = $_POST['jeVeuxTeSuivre'];
                        $following_connectedID = $_POST['tuMeSuis'];


                        //Etape 3 : Petite sécurité
                        // pour éviter les injection sql : https://www.w3schools.com/sql/sql_injection.asp
                        
                        //$user_connectedID = intval($mysqli->real_escape_string($user_connectedID));
                        
                        //Etape 4 : construction de la requete
                         $lInstructionSql = "INSERT INTO followers "
                                . "(id, followed_user_id, following_user_id) "
                                . "VALUES (NULL, "
                                . $followed_connectedID . ", "
                                . "'" . $following_connectedID . "' )";

                        //echo $lInstructionSql;
                        // Etape 5 : execution
                        $ok = $mysqli->query($lInstructionSql);
                        if ( ! $ok)
                        {
                            echo "Impossible de suivre la personne: " . $mysqli->error;
                         } else
                         {
                            echo "cool un nouveau friend :";
                        }
                    }
                    ?>                     
                    <form action="wall.php" method="post">
                        <input type='hidden' name='jeVeuxTeSuivre' value="<?php echo $_SESSION['connected_id']?>"> 
                        <input type='hidden' name='tuMeSuis' value="<?php echo $_GET['user_id']?>">
                        <!-- <dl> -->
                            <!--<dt><label for='auteur'>Auteur</label></dt>
                             <dd>
                                <select name='auteur'>
                                    <?php
                                    //foreach ($listAuteurs as $id => $alias)
                                    //    echo "<option value='$id'>$alias</option>";
                                    ?>
                                </select></dd> -->
                            <!-- <dt><label for='message'>Message</label></dt>
                            <dd><textarea name='message'></textarea></dd>
                        </dl> -->
                        <input type='submit' value="&#x1F984 Abonne Toi &#x1F440"></input> 
                    </form>               
                </article>
            </aside>





            <!--Partie qui marche____-__--------__________--______- --> 

            <main>
                <?php
                /**
                 * Etape 3: récupérer tous les messages de l'utilisatrice
                 */
                $laQuestionEnSql = "
                    SELECT posts.content, 
                    posts.created, 
                    users.alias as author_name, 
                    posts_tags.post_id as post_id,
                    posts_tags.tag_id as tag_id,
                    COUNT(likes.id) as like_number, GROUP_CONCAT(DISTINCT tags.label) AS taglist 
                    FROM posts
                    JOIN users ON  users.id=posts.user_id
                    LEFT JOIN posts_tags ON posts.id = posts_tags.post_id  
                    LEFT JOIN tags       ON posts_tags.tag_id  = tags.id 
                    LEFT JOIN likes      ON likes.post_id  = posts.id 
                    WHERE posts.user_id='$user_wall_id' 
                    GROUP BY posts.id
                    ORDER BY posts.created DESC  
                    ";
                include("sources/library.php");
                if ( ! $lesInformations)
                {
                    echo("Échec de la requete : " . $mysqli->error);
                }

                while ($post = $lesInformations->fetch_assoc())
                {

                    //echo "<pre>" . print_r($post, 1) . "</pre>";
                    ?>                
                    <article>
                        <h3>
                        <?php
                            $date =new DateTime($post['created']);

                        
                        //strftime('%d-%m-%Y',strtotime($date));
                            ?>
                            <time><?php echo $date->format('l jS \o\f F Y h:i:s A'), "\n";?></time>
                        </h3>
                        <address>De <?php echo $post["author_name"] ?></address>
                        <div>
                        
                            <p><?php echo $post["content"] ?></p>
                        </div>                                            
                        <footer>
                            <small>♥ <?php echo $post["like_number"] ?></small>
                            <a href="tags.php?tag_id=<?php echo $post['tag_id'] ?>">#<?php echo $post["taglist"] ?></a>
                        </footer>
                    </article>
                <?php } ?>
                <article>
                    <h2>Poster un message</h2>
                    <?php
                    //echo "<pre>" . print_r($_POST, 1) . "</pre>";
                    $enCoursDeTraitement = isset($_POST['author_id']);
                    if ($enCoursDeTraitement)
                    {
                        // on ne fait ce qui suit que si un formulaire a été soumis.
                        // Etape 2: récupérer ce qu'il y a dans le formulaire @todo: c'est là que votre travaille se situe
                        // observez le résultat de cette ligne de débug (vous l'effacerez ensuite)
                        
                        // et complétez le code ci dessous en remplaçant les ???
                        $user_connectedID = $_POST['author_id'];
                        $postContent = $_POST['message'];


                        //Etape 3 : Petite sécurité
                        // pour éviter les injection sql : https://www.w3schools.com/sql/sql_injection.asp
                        $user_connectedID = intval($mysqli->real_escape_string($user_connectedID));
                        $postContent = $mysqli->real_escape_string($postContent);
                        //Etape 4 : construction de la requete
                        $lInstructionSql = "INSERT INTO posts "
                                . "(id, user_id, content, created) "
                                . "VALUES (NULL, "
                                . $user_connectedID . ", "
                                . "'" . $postContent . "', "
                                . "NOW())";

                        //echo $lInstructionSql;
                        // Etape 5 : execution
                        $ok = $mysqli->query($lInstructionSql);
                        if ( ! $ok)
                        {
                            echo "Impossible d'ajouter le message: " . $mysqli->error;
                        } else
                        {
                            echo "Message posté en tant que :" . $user_connectedID;
                        }
                    }
                    ?>                     
                    <form action="wall.php" method="post">
                        <input type='hidden' name='author_id' value="<?php echo $user_connectedID ?>">
                        <dl>
                            <!--<dt><label for='auteur'>Auteur</label></dt>
                             <dd>
                                <select name='auteur'>
                                    <?php
                                    //foreach ($listAuteurs as $id => $alias)
                                    //    echo "<option value='$id'>$alias</option>";
                                    ?>
                                </select></dd> -->
                            <dt><label for='message'>Message</label></dt>
                            <dd><textarea name='message'></textarea></dd>
                        </dl>
                        <input type='submit'>
                    </form>               
                </article>

            </main>
        </div>
    </body>
</html>
