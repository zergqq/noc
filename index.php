<?php
/**
 * Created by PhpStorm.
 * User: Nfq
 * Date: 0021 2015-01-21
 * Time: 09:24
 */
include 'Categories.php';
function connDB()
{
    try
    {
        $pdo = new PDO('pgsql:dbname=nocowanie;host=localhost;user=zadanie_nocowanie;password=PASS');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $tree = new Categories($pdo, 'category','category_trans', 'id_category', 'id_parent', 'lft', 'rgt');

        // dodawanie kategorii
        // $tree->addCategory(72, array('language_code' => 'pl','title' => 'ziemniak', 'description' => 'ziemniak_opis'));

        // dodawanie tlumaczenia
        // $tree->addTrans(72, array('language_code' => 'en','title' => 'potato', 'description' => 'potato_desc'));

        // wyswietlanie
        // $tree->draw('pl');

        // usuwanie kategorii
        // $tree->removeCategory(45);

        // przenoszenie kategorii
        // $tree->moveCategory(24, 23);


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
    foreach ($tree as $element) {
        if ($element['depth'] > 0) {
            $str .= "|" . str_repeat("  |", $element['depth']) . "-" . $element['id_category'] . "</br>";
        } else {
            $str .= '|-' . $element['id_category'] . "</br>";
        }
    }
    return $str;
}


connDB();




