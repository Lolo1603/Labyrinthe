<?php
/*********************************************************************************************
 * BLOC 1 : LOGIQUE PHP (Gestion de l'état, de la BDD et des requêtes)
 *********************************************************************************************/

// 1. Démarrage et Initialisation de la Session
session_start();
$message = ""; 

$id_actuel = null; 

// Initialisation des variables de session SI LA PARTIE N'A JAMAIS ÉTÉ DÉMARRÉE
// On utilise une variable marqueur simple (ex: 'partie_demarree') pour éviter les réinitialisations involontaires.
if (!isset($_SESSION['partie_demarree'])) {
    
    // --- Initialisation ---
    $_SESSION['partie_demarree'] = true; // Marqueur
    $_SESSION['cle'] = 0;       // Nombre de clés possédées
    $_SESSION['score'] = 0;     // Nombre de déplacements
    $_SESSION['cles_trouvees'] = []; // Pièces où les clés ont été ramassées
    // ----------------------
}

// 2. Connexion à la base de données
$bdd_fichier = 'labyrinthe.db';
$sqlite = new SQLite3($bdd_fichier);

// Vérification de la consommation de clé (si le joueur vient de cliquer sur "Ouvrir la grille")
if (isset($_GET['action']) && $_GET['action'] == 'use_key') {
    if ($_SESSION['cle'] > 0) {
        $_SESSION['cle']--; // On retire une clé de l'inventaire
        $message = "Vous avez utilisé une clé pour ouvrir la grille.";
    }
}


// 3. Détermination de la Pièce Actuelle et du Score
if (isset($_GET['id'])) {
    // Cas 1 : Déplacement (l'ID est passé par l'URL)
    $id_actuel = (int) $_GET['id'];
    
    // On incrémente le score seulement si l'ID est valide (pour ne pas compter l'initialisation)
    if ($id_actuel > 0) {
        $_SESSION['score']++;
    }
}
else {
    // Cas 2 : Démarrage de la partie (on cherche le couloir de type 'depart')
    $sql_depart = "SELECT id FROM couloir WHERE type='depart'";
    $res_depart = $sqlite->query($sql_depart);
    $row_depart = $res_depart->fetchArray(SQLITE3_ASSOC);
    $id_actuel = $row_depart['id'];
}

// 4. Récupération des informations du couloir actuel
$stmt_info = $sqlite->prepare("SELECT * FROM couloir WHERE id = :id");
$stmt_info->bindValue(':id', $id_actuel, SQLITE3_INTEGER);
$result_info = $stmt_info->execute();
$piece_actuelle = $result_info->fetchArray(SQLITE3_ASSOC);


// 5. Logique de ramassage de la clé (si la pièce actuelle en contient une)
if ($piece_actuelle['type'] == 'cle' && !in_array($id_actuel, $_SESSION['cles_trouvees'])) {
    $_SESSION['cle']++; // Ajout d'une clé
    $_SESSION['cles_trouvees'][] = $id_actuel; // Ajout de l'ID à la liste des clés déjà trouvées
    $message = "Vous avez trouvé une clé ! Elle a été ajoutée à votre inventaire.";
}


// 6. Récupération des chemins possibles
// REQUÊTE CORRIGÉE : On cherche l'ID actuel dans couloir1 OU couloir2 (pour gérer les liens bidirectionnels)
$sql_chemins = "SELECT * FROM passage WHERE couloir1 = :id OR couloir2 = :id";
$stmt_chemins = $sqlite->prepare($sql_chemins);
$stmt_chemins->bindValue(':id', $id_actuel, SQLITE3_INTEGER);
$result_chemins = $stmt_chemins->execute();

?>

<?php
/*********************************************************************************************
 * BLOC 2 : AFFICHAGE (HTML et intégration des données PHP)
 *********************************************************************************************/
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Jeu du Labyrinthe - Pièce <?php echo $piece_actuelle['id']; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1>Couloir N° <?php echo $piece_actuelle['id']; ?></h1>
    
    <div class="statut">
        <h2>Statut de la partie</h2>
        <p>Type de lieu : <strong><?php echo $piece_actuelle['type']; ?></strong></p>
        <p>Déplacements effectués (Score) : <strong><?php echo $_SESSION['score']; ?></strong></p>
        <p>Clés possédées : <strong><?php echo $_SESSION['cle']; ?></strong></p>
        <?php if (!empty($message)): ?>
            <p style="color: #4CAF50; font-weight: bold;"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>

    <?php 
    // CONDITION DE FIN DE PARTIE
    if ($piece_actuelle['type'] == 'sortie'): 
    ?>
        <h2 style="color: #4CAF50;">BRAVO ! VOUS AVEZ TROUVÉ LA SORTIE !</h2>
        <p>Votre score final est de : <strong><?php echo $_SESSION['score']; ?> déplacements.</strong></p>
        <?php session_destroy(); // Destruction de la session pour permettre une nouvelle partie ?>
        <a href="index.php" class="direction" style="background-color: #5865f2;">Recommencer une partie</a>
    <?php 
    // CONDITION DE JEU NORMAL
    else: 
    ?>
        
        <h3>Où voulez-vous aller ?</h3>
        <div class="choix">
            <?php
            $aucun_chemin = true;

            // BOUCLE PRINCIPALE : Analyse de tous les chemins trouvés par la requête SQL
            while ($chemin = $result_chemins->fetchArray(SQLITE3_ASSOC)) {
                $aucun_chemin = false;
                
                // 1. Détermination de la destination et de la direction
                if ($chemin['couloir1'] == $id_actuel) {
                    // Si on est le couloir1, la destination est couloir2 et la direction est position1
                    $destination = $chemin['couloir2'];
                    $direction = $chemin['position1']; 
                } else {
                    // Si on est le couloir2, la destination est couloir1 et la direction est position2
                    $destination = $chemin['couloir1'];
                    $direction = $chemin['position2'];
                }

                $type_passage = $chemin['type']; // Ex: libreNS, grilleOE, secret...
                
                // 2. Affichage conditionnel des liens selon le type de passage
                
                // Cas 1 : Passage libre (contient 'libre' ou est 'vide')
                if (strpos($type_passage, 'libre') !== false || $type_passage == 'vide') {
                    echo '<a href="jeu.php?id=' . $destination . '" class="direction">Aller vers ' . $direction . '</a>';
                }
                // Cas 2 : Grille (contient 'grille')
                elseif (strpos($type_passage, 'grille') !== false) {
                    if ($_SESSION['cle'] > 0) {
                        // Accès avec une clé
                        echo '<a href="jeu.php?id=' . $destination . '&action=use_key" class="direction" style="background-color: #faa61a;">Ouvrir la grille vers ' . $direction . ' (Utilise 1 clé)</a>';
                    } else {
                        // Grille bloquée
                        echo '<span class="direction" style="background-color: #ff4545;">Grille fermée vers ' . $direction . ' (Il faut une clé)</span>';
                    }
                }
                // Cas 3 : Passage secret (le passage n'est pas affiché pour l'instant)
                // Si la logique de déblocage n'est pas définie, on ignore le chemin 'secret'
                
                echo '<br>'; // Retour à la ligne pour séparer les liens
            }

            if ($aucun_chemin) {
                // Ce cas devrait seulement arriver si le joueur est dans un cul-de-sac sans chemin retour.
                echo "<p style='color: #ff4545;'>Vous êtes bloqué ! Aucun chemin disponible.</p>";
            }
            ?>
        </div>

    <?php endif; ?>

</body>
</html>
<?php 
// Fermeture de la connexion à la base de données
$sqlite->close(); 
?>
