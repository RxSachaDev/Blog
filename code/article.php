<?php
ob_start();
include_once("./components/header.php");
include("./components/navbar.php");

$connexion = getConnexion();

if (empty($_SESSION['pseudo'])) {
    header("Location: auth.php");
    exit();
}

$requestUri = trim($_SERVER['REQUEST_URI'], '/');
$parts = explode('/', $requestUri);
$articleIndex = array_search('article', $parts);

if ($articleIndex !== false) {
    $parts = array_slice($parts, $articleIndex);
}

if (count($parts) === 1) {
    header("Location: index.php");
    exit();
} elseif (count($parts) === 2) {
    $category_slug = $parts[1];
    $stmt = getArticlesByCategory($category_slug);
    $articles = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<main class="main-specific-category-articles">
    <h2>Articles dans la catégorie: <?php echo htmlspecialchars($category_slug); ?></h2>
    <div class="article-list">
        <?php foreach ($articles as $article) : ?>
            <?php $article_slug = slugify($article['title_article']); ?>
            <div class='article-filter-by-category'>
                <h3><?php echo htmlspecialchars($article['title_article']); ?></h3>
                <a class="a-redirection" href='<?php echo ($category_slug . '/' . $article_slug); ?>'>En savoir +</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php
} elseif (count($parts) === 3) {
    $category_slug = $parts[1];
    $article_slug = $parts[2];

    $stmt = getArticleBySlug($article_slug);
    $article = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    if ($article) {
        $title = $article['title_article'];
        $content = $article['content_article'];
        $image = $article['picture_article'];
        $date = $article['date_article'];

        $stmt = getPseudoWithArticle($article['id_article']);
        $user = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $pseudoWriter = $user ? $user['pseudo'] : 'Inconnu';

        $stmt = getCategoryWithArticle($article['id_article']);
        $category = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $idCategory = $category ? $category['id_cat'] : null;

        $stmt = getCategoryWithIdCategory($idCategory);
        $nameCategory = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $name = ucfirst(htmlspecialchars($category_slug));

        $commentaires = getCommentByArticle($article['id_article']);
        $initialCount = 2;

?>
<main>
    <div class='main-article'>
        <h2><?php echo $title ;?></h2>

        <p class='rubrique-article'><a href="http://localhost/but2-tp-blog/code/index.php">Article</a>><?php echo "$name>$title";?></p>
        <div class='container-article'>
            <div class="content-article">
                <p><?php echo $content ?></p>
                <img src="http://localhost/but2-tp-blog/code/assets/article/<?php echo $image; ?>" alt="Article Image">
            </div>
            <div class='info-article'>
                <p><b>Auteur : </b><?php echo $pseudoWriter?></p>
                <p><b>Publié le :</b><?php echo $date?></p>
            </div>
        </div>
    
    <?php
        $userPseudo = $_SESSION['pseudo'];
        $userComments = [];
        $otherComments = [];

        foreach ($commentaires as $commentaire) {
            $stmt = getPseudoWithIdComment($commentaire['id_comment']);
            $user = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            $pseudoCommentaire = $user ? $user['pseudo'] : 'Anonyme';

            if ($pseudoCommentaire === $userPseudo) {
                $userComments[] = $commentaire;
            } else {
                $otherComments[] = $commentaire;
            }
        }

        $allComments = array_merge($userComments, $otherComments);
        $rowCount = count($allComments);

        if ($rowCount > 0) {
            echo "<p class='rubrique-article'>Toutes les réponses :</p>";
            for ($i = 0; $i < $rowCount; $i++) {
                $contenuCommentaire = $allComments[$i]['content_comment'];
                $idCommentaire = $allComments[$i]['id_comment'];
                $dateCommentaire = $allComments[$i]['date_comment'];
                $stmt = getPseudoWithIdComment($idCommentaire);
                $user = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                $pseudoCommentaire = $user ? $user['pseudo'] : 'Anonyme';
                
                $isHidden = $i >= $initialCount ? 'hidden' : '';
                ?>
                <div class='comment <?php echo $isHidden; ?>'>
                    <p class='content-comment'><?php echo htmlspecialchars($contenuCommentaire); ?></p>
                    <div class='info-article'>
                        <p><b>Auteur : </b><?php echo htmlspecialchars($pseudoCommentaire); ?></p>
                        <p><b>Publié le :</b><?php echo htmlspecialchars($dateCommentaire); ?></p>
                    </div>
                    <?php
                    if ($pseudoCommentaire === $userPseudo) {
                        ?>
                        <form method="POST" class="delete-comment-form">
                            <input type="hidden" name="comment_id" value="<?php echo $idCommentaire; ?>">
                            <button type="submit" name="delete_comment" class="delete-button">Supprimer</button>
                        </form>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            if ($rowCount > $initialCount) {
                echo "<button id='voir-plus'>Voir plus</button>";
            }
        }
    ?>
        
        <div class='post-comment'>
            <h2>Créer une réponse</h2>
            <p>Remplissez les champs ci-dessous pour créer et publier votre réponse!</p>
            <form action="" method='POST'>
                <textarea name="comment" placeholder='Votre réponse' maxlength="100"></textarea>
                <p id='caractere'>0 / 100 caractères</p>
                <button>Poster votre réponse</button>
            </form>
        </div>
        <?php 
            if (!empty($_POST['comment'])) {
                insertComment($userPseudo, $_POST['comment'], $article['id_article']);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
            
            if (isset($_POST['delete_comment'])) {
                $commentId = $_POST['comment_id'];
                deleteComment($commentId);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        } else {
            echo "<p class ='error'>Article non trouvé.</p>";
        }
    }
    ?>
    </div>
</main>
<?php
include("./components/footer.php");
?>
