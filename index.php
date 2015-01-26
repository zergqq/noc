<?php
    session_start();
?>

<html>
    <head>
        <title>Categories</title>
        <link type="text/css" rel="stylesheet" href="style.css">
        <script type="text/javascript" src="jquery-2.13.min.js"></script>
        <script type="text/javascript" src="script.js"></script>
    </head>
<body>



<?php
/**
 * Created by PhpStorm.
 * User: Nfq
 * Date: 0021 2015-01-21
 * Time: 09:24
 */
include 'Categories.php';
function zadanie()
{
    try
    {
        $pdo = new PDO('pgsql:dbname=nocowanie;host=localhost;user=zadanie_nocowanie;password=PASS');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $tree = new Categories($pdo, 'category','category_trans', 'id_category', 'id_parent', 'lft', 'rgt');
        $_SESSION['tree'] = $tree;
        // dodawanie kategorii
        //$tree->addCategory(44, array('language_code' => 'eng','title' => 'EnglishTitleQQQ', 'description' => 'EnglishDQQQ'));
        //$tree->addCategory(43, array('language_code' => 'eng','title' => 'EnglishTitleDDD', 'description' => 'EnglishDDDD'));
        //$tree->addCategory(44, array('language_code' => 'eng','title' => 'EnglishTitleHHH', 'description' => 'EnglishDHHH'));

        // dodawanie tlumaczenia
        //$tree->addTrans(43, array('language_code' => 'ger','title' => 'GermanTitleXXX', 'description' => 'GermanDXXX'));

         //usuwanie kategorii
         //$tree->removeCategory(44);

        // przenoszenie kategorii
        // $tree->moveCategory(24, 23);

        // wyswietlanie kategorii
        echo show($tree->getCategories('ger'));
    }
    catch (Exception $e)
    {
        die("error: \n" . $e);
    }
}

function show($tree)
{
    $str = '<fieldset id="categories">';
    foreach ($tree as $category) {
        $depth = $category['depth'];
        $id = $category['id_category'];
        $title = $category['title'];
        $description = $category['description'];
        $delete_icon = '<a href="" class="delete"><img alt="" align="absmiddle" border="0" src="img/delete-icon.png" /></a>';

        $str .= '<p id="' . $id . '">';
        if ($category['depth'] > 0) {
            $str .= "  |" . str_repeat("  |", $depth) . "--" .  '<input name="' .$id. '-title" value="' .$title. '"/><input name="' .$id. '-desc" value="' .$description. '"/>';
            $str .= $delete_icon;
        } else {
            $str .= '|--' .  '<input name="' .$id. '-title" value="' .$title. '"/><input name="' .$id. '-desc" value="' .$description. '"/>';
            $str .= $delete_icon;
        }
        $str .= '</p>';
    }
    $str .= '</fieldset><p id="notice"></p>';
    return $str;
}


zadanie();
?>


</body>

</html>

