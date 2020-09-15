<?php
echo('........................................................................' . PHP_EOL);
echo(".............................. Start parser ............................" . PHP_EOL);
echo('........................................................................' . PHP_EOL);

$rawContent = file_get_contents('https://mebelservice.com/product-category/novinki/');
preg_match_all('#class="cat-item[^"]*"[^<]*<a\s*href="(?<url>[^"]*)"[^>]*>(?<name>[^<]*)<#ims', $rawContent, $categories, PREG_SET_ORDER);

foreach ($categories as $category) {

    $category_name = preg_replace(['#/#ims', '# #ims'], ['', '_'], $category['name']);

    if (!file_exists($category_name)) {
        if (mkdir($category_name, 0777, true)) {
            echo('........................................................................' . PHP_EOL);
            echo('............... Folder created: "' . $category_name . '" ...............' . PHP_EOL);
            echo('........................................................................' . PHP_EOL);
        }
    } else {
        echo('........................ Folder "'.$category_name.'" is exists! ........................' . PHP_EOL);
    }

    parser($category['url'], $category_name);
    $category_content = file_get_contents($category['url']); //контент категорії // first page

    //all next page [3]
    $all_next_page = '#<li\s*class="ui--box ui--gradient ui--gradient-grey"[^<]+<a\s*href="(?<url>[^"]*)"#ims';
    if (preg_match_all($all_next_page, $category_content, $paginations, PREG_SET_ORDER)) {
        foreach ($paginations as $paginate) {
            parser($paginate['url'], $category_name);
        }
    }
}

function parser($listing_url, $category_name)
{
    $listing = file_get_contents($listing_url);
    preg_match_all('#<a\s*class="ui--content-box-link"\s*href="(?<url>[^"]*)"(?:[^>]*>){2}<h5#ims', $listing, $results, PREG_SET_ORDER);
    foreach ($results as $value) {
        $value['url'];
        $advert = file_get_contents($value['url']);
        preg_match('#<h1\s*itemprop="name"[^>]*>(?<text>[^<]*)<#ims', $advert, $title);
        $titleName = preg_replace(['#/#ims', '# #ims'], ['', '_'], $title['text']);

        if (!file_exists($category_name . '/' . $titleName)) {
            if (mkdir($category_name . '/' . $titleName, 0777, true)) {
                echo('........................................................................' . PHP_EOL);
                echo('............... Subfolder created: "' . $titleName . '" ...............' . PHP_EOL);
                echo('........................................................................' . PHP_EOL);
            }
        } else {
            echo('............... Folder "' . $category_name . '/' . $titleName . '" is exists! ...............' . PHP_EOL);
        }

            $image_reg = '#<[^>]*class="iconic-woothumbs-images__image"[^>]*data-large-image="(?<img>[^"]*\/(?<name>[^.]*.\w{3}))"#ims';
            if (preg_match_all($image_reg, $advert, $result, PREG_SET_ORDER)) {
                foreach ($result as $item) {
                    saveImage($item, $titleName, $category_name);
                }
            }

            $images_block = '#id="description"(?<block>.+?)</li#ims';
            $images_reg_else = '#<a href="(?<img>[^"]*/(?<name>[^.]*.\w{3}))"#ims';
            $images_reg_else2 = '#class="span3"[^<]*<h5(?:[^<]*<){2}a\s*href="(?<img>[^"]*/(?<name>[^.]*.\w{3}))"#ims';

            if ((preg_match($images_block, $advert, $else_image) &&
                preg_match_all($images_reg_else, $else_image['block'], $result2, PREG_SET_ORDER)) ||
                preg_match_all($images_reg_else2,$advert,$result2,PREG_SET_ORDER)
            ) {
                foreach ($result2 as $item) {
                    saveImage($item, $titleName, $category_name);
                }
            }

    }
}

function saveImage($item, $titleName, $category_name)
{
    if (!file_exists($category_name . '/' . $titleName . '/' . $item['name'])) {
        $ch = curl_init($item['img']);
        $fp = fopen($category_name . '/' . $titleName . '/' . $item['name'], 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        echo("Image saved: " . $item['img'] . PHP_EOL);
    } else {
        echo('SKIPPED: Image "'.$item['name'].'" is exists!' . PHP_EOL);
    }
}

echo('........................................................................' . PHP_EOL);
echo("........................ Download images completed ....................." . PHP_EOL);
echo('........................................................................' . PHP_EOL);

?>