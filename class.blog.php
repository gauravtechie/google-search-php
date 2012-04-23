<?php
/**
 * Google Blog Search API
 * Author: Dhiraj Pandey 
 * 
 */
 include 'topsites.php';
 class Blog {
    /**
     * Constructor
     */
     function blog(){
        
     }
     function blog_search($keyword, $profile_id){
        global $db;
        
        $keyword = urlencode($keyword);
        for($i=1;$i<50; $i++){

            $url = "https://ajax.googleapis.com/ajax/services/search/blogs?v=1.0&q=$keyword&key=ABQIAAAAs1rgZBaYXEYGPfjqL9pu4xQ3-o-L4HViqVDmRzV_DUtUXlzzbBR_Ds5tjnO7gAJ021a-6OtuCNHmBw&rsz=8&start=$i";
            //echo $url;
            // sendRequest
            // note how referer is set manually
/*
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_REFERER,'dokito.com');
            $body = curl_exec($ch);
            curl_close($ch);
*/
			//echo $body;
$body = file_get_contents($url);			
            // now, process the JSON string
            $json = json_decode($body);
            //print_r($json);
            $result = $json->responseData->results;
            //print_r($result);
            //die;
            foreach($result as $r){
                $title = trim($db->escape($r->titleNoFormatting));
                $blog_url = trim($db->escape($r->blogUrl));
                $post_url = trim($db->escape($r->postUrl));
                $timestamp = strtotime($r->publishedDate);
                //echo $timestamp;
                //die;
                $author = trim($r->author);
                $check_q = "SELECT * FROM `blog_search` WHERE `blog` = '$blog_url' AND `url` = '$post_url' AND `timestamp` = '$timestamp'";
                echo $check_q;
                $re = $db->get_results($check_q, ARRAY_A);
                //print_r($re);
                if(sizeof($re)== 0){
                    //echo "NEW\t$keyword\t$profile_id";
                    $alexa_rank = $this->alexa_rank($r->blogUrl);
                    $q = "INSERT IGNORE INTO `blog_search` (`profile_id`, `title`, `blog`, `url`, `keyword`, `timestamp`,`author`, `rank`) VALUE ('$profile_id', '$title', '$blog_url', '$post_url', '$keyword', '$timestamp', '$author', '$alexa_rank')";
                    $db->query($q);                
                }
            }            
        }
        return true;        
     }
     function alexa_rank($blog_url){
        global $db;
        $q = "SELECT * FROM `blog_search` WHERE `blog` = '$blog_url' AND `rank`!= '0'";
        //echo $q;
        $d = $db->get_row($q, ARRAY_A);
        //print_r($d);
        if(sizeof($d)!= 0){
            $rank = $d['rank'];
            
        }else{
            $url = compose_url(rtrim($blog_url, '/'));
            $result = make_http_request($url);
            $filecontent = str_replace("aws:","",$result); ///strip out the annoying aws tags
            $xml = simplexml_load_string($filecontent); //convert to simplexml
            $page = $xml->Response->UrlInfoResult->Alexa->TrafficData->Rank;
            //print_r($page);
            $rank = $page[0];
        }
        //echo $rank;
        return $rank;
     }
 }
 
?>