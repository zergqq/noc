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
        $pdo = new PDO('pgsql:dbname=nocowanie;host=localhost;user=zadanie_nocowanie;password=ppvdjFNt94YSbdBr');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $tree = new Categories($pdo, 'category','category_trans', 'id_category', 'id_parent', 'lft', 'rgt');

        // dodawanie kategorii
        $tree->addCategory(74, array('language_code' => 'pl','title' => 'ziemniak4', 'description' => 'ziemniak_opis'));

        // dodawanie tlumaczenia
        // $tree->addTrans(72, array('language_code' => 'en','title' => 'potato', 'description' => 'potato_desc'));

        // usuwanie kategorii
        // $tree->removeCategory(45);

        // przenoszenie kategorii
        // $tree->moveCategory(24, 23);

        // wyswietlanie
        echo show($tree->getCategories());
    }
    catch (Exception $e)
    {
        die("error: \n" . $e);
    }
}

function show($tree)
{
    $str = '';
    foreach ($tree as $category) {
        if ($category['depth'] > 0) {
            $str .= "|" . str_repeat("  |", $category['depth']) . "-" . $category['id_category'] . "</br>";
        } else {
            $str .= '|-' . $category['id_category'] . "</br>";
        }
    }
    return $str;
}


zadanie();




