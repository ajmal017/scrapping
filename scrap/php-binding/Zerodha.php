<?php
require_once "phpwebdriver/WebDriver.php";

$webdriver = new WebDriver("localhost", 4444);
$webdriver->connect("chrome");
$webdriver->get("https://zerodha.com/margin-calculator/BracketCover/");

$scripBuyOperation = 'BUY';
$scripSellOperation = 'SELL';
$month = array(
        'JAN',
        'FEB',
        'MAR',
        'APR',
        'MAY',
        'JUN',
        'JUL',
        'AUG',
        'SEP',
        'OCT',
        'NOV',
        'DEC'
);

selectNfo($webdriver);
selectFutures($webdriver);
$scripValue = 2; // number of month to be skipped
loopThroughAllFutures($webdriver, 'scrip', $scripBuyOperation, 'futures-long', 
        $scripValue);
loopThroughAllFutures($webdriver, 'scrip', $scripSellOperation, "futures-short", 
        $scripValue);

function selectNfo ($webdriver)
{
    $element = $webdriver->findElementBy(LocatorStrategy::id, "exchange");
    
    $nfo = $element->findOptionElementByText('NFO');
    $nfo->click();
}

function selectFutures ($webdriver)
{
    $element = $webdriver->findElementBy(LocatorStrategy::id, "product");
    $nfo = $element->findOptionElementByText('Futures');
    $nfo->click();
}

function loopThroughAllFutures ($webdriver, $elementId, $operation, $fileName, 
        $scipValue)
{
    $finalArray = array();
    $unit = array(
            3
    );
    
    $totalElements = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 
            'select[id="' . $elementId . '"]>option');
    $totalCount = count($totalElements);
    
    for ($x = 1; $x <= $totalCount; $x = $x + $scipValue) {
        $xpath = '//*[@id="scrip"]/option[' . $x . ']';
        $wishPrice = null;
        $eachFuture = $webdriver->findElementBy(LocatorStrategy::xpath, $xpath);
        $scripName = $eachFuture->getText();
        $eachFuture->click();
        $originalPrice = $webdriver->executeScript('return $("#price").val()', 
                array());
        // echo $originalPrice;
        
        foreach ($unit as $price) {
            if ($operation == 'BUY') {
                $wishPrice = $originalPrice - $price;
                $webdriver->findElementBy(LocatorStrategy::className, 
                        "trade-buy")->click();
            } else {
                $wishPrice = $originalPrice + $price;
                $webdriver->findElementBy(LocatorStrategy::className, 
                        "trade-sell")->click();
            }
            // echo $wishPrice;
            /*
             * $webdriver->executeScript(
             * "document.getElementById('stl').value ='" + $wishPrice + "')",
             * array());
             */
            
            $webdriver->findElementBy(LocatorStrategy::xpath, '//*[@id="stl"]')->clear();
            $strng_whishPrice = (string) $wishPrice; // ." - ";
            $webdriver->findElementBy(LocatorStrategy::xpath, '//*[@id="stl"]')->sendKeys(
                    array(
                            $strng_whishPrice
                    ));
            $webdriver->findElementBy(LocatorStrategy::xpath, 
                    '//*[@id="form-boco"]/div/p[9]/input[1]')->click();
            $marginValue = $webdriver->executeScript(
                    'return $("#margin-req").html()', array());
            $data = collectData($scripName, $originalPrice, $operation, $price, 
                    $marginValue);
            array_push($finalArray, $data);
            createCsv($finalArray, $fileName);
        }
    }
}

function createCsv ($arrayList, $fileName)
{
    $file = fopen($fileName . ".csv", "w");
    
    foreach ($arrayList as $line) {
        fputcsv($file, explode(',', $line));
    }
    
    fclose($file);
}

function cleanData ($a)
{
    if (is_numeric($a)) {
        
        $a = preg_replace('/[^0-9,]/s', '', $a);
    }
    
    return $a;
}

function collectData ($scripName, $scripOriginalValue, $scripOperation, $unit, 
        $marginPrice)
{
    $marginPrice = strtr($marginPrice, 
            array(
                    ',' => ''
            ));
    $arr = $scripName . "," . $scripOriginalValue . "," . $scripOperation . "," .
             $unit . "," . $marginPrice;
    
    echo "scrip- " . $scripName . " original value- " . $scripOriginalValue .
             " scrip-operation- " . $scripOperation, " unit- " . $unit .
             " marginprice- " . $marginPrice;
    
    return $arr;
}
$webdriver->close();
?>