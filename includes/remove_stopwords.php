<?php
function remove_stopwords($query,$boolstring) {
    // $querysansand = preg_replace('/and/i', '', $query) ;
    // stopwords are in innodb_ft_default_stopwords table - copied here for expediency and adding 'and' 'ii'
    $stopwords = explode(',', "a,about,an,and,are,as,at,be,by,com,de,en,for,from,how,i,ii,in,is,it,la,of,on,or,that,the,this,to,was,what,when,where,who,will,with,und,www") ;
    $words = preg_split('/\s+/', $query);
    $searchwords = array_diff($words, $stopwords);

    foreach ($searchwords as $value) {     // split and add +
        if (strlen($value) < 3) { # skip other 1 and 2 character words in natural language search
            continue ;
        }
        $boolstring .= " +" . $value;
    }

    return $boolstring ;
}
?>