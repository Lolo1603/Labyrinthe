<?php
// On démarre la session au cas où (bonne pratique), mais le jeu sera sur jeu.php
session_start();

// Si une partie est en cours, on la détruit pour garantir un départ propre
if (isset($_SESSION['cle'])) {
    session_destroy();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Projet Labyrinthe Web - Menu Principal</title>
    <style>
        body { font-family: sans-serif; text-align: center; margin-top: 100px; background-color: #f4f4f4; }
        h1 { color: #333; }
        button { padding: 15px 30px; font-size: 1.2em; cursor: pointer; background-color: #4CAF50; color: white; border: none; border-radius: 5px; transition: background-color 0.3s; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>Jeu du Labyrinthe - BTS SIO</h1>
    <p>Trouvez la sortie en optimisant vos déplacements.</p>
    
    <a href="jeu.php"><button>Commencer une nouvelle partie</button></a>
</body>
</html>
