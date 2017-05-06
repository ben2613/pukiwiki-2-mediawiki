<?php
$debug = false;
function convert($text){
    global $debug;
    $lines = preg_split('/\r?\n/', $text);
    for ($i=0; $i < sizeof($lines); $i++) {
        $lines[$i] = convLine($lines[$i]);
    }
    return finalize(implode("\r\n", $lines));
}

$ENV = array('BLOCKQUOTE'=>0, 'TABLE'=>0);

$breakableCheck = array(
    //pre
    array('re' => '/^[ \t]/', 'func' => function($line,$re){return $line;}),
    //horizontal line
    array('re' => '/^----+/', 'func' => function($line,$re){return preg_replace($re, '----', $line, 1);}),
    //comment
    array('re' => '/^\/\//', 'func' => function($line,$re){return preg_replace('/$/',' -->',preg_replace($re, '<!--', $line, 1));}),
    //remove plugins
    array('re' => '/^#.*/', 'func' => function($line,$re){return '';}),
    //array( 're' => '/^#contents\(?.*\)?;?\s*/', 'func' => function($line,$re){return '';}),
    //array( 're' => '/^#navi\(?.*\)?;?\s*/', 'func' => function($line,$re){return '';}),
    //close block element
    array('print' => 1, 're' => '/^$/', 'func' =>
    function($line,$re){
        global $ENV;
        if($ENV['BLOCKQUOTE']){
            $ENV['BLOCKQUOTE']=0;
            return "<\/blockquote>\r\n";
        }else if($ENV['TABLE']){
            $ENV['TABLE']=0;
            return "|}\r\n";
        }else{
            return '';
        }
    }
    )
);

$unbreakableCheck = array(
    //headings
    array( 're' => '/^\*\*\*\*\*/', 'func' =>  function($line,$re){return preg_replace('/(\[#\w{8}\])?\s*$/', ' ======', preg_replace($re, '====== ', $line, 1), 1);}),
    array( 're' => '/^\*\*\*\*/', 'func' =>  function($line,$re){return preg_replace('/(\[#\w{8}\])?\s*$/', ' =====', preg_replace($re, '===== ', $line, 1), 1);}),
    array( 're' => '/^\*\*\*/', 'func' =>  function($line,$re){return preg_replace('/(\[#\w{8}\])?\s*$/', ' ====', preg_replace($re, '==== ', $line, 1), 1);}),
    array( 're' => '/^\*\*/', 'func' =>  function($line,$re){return preg_replace('/(\[#\w{8}\])?\s*$/', ' ===', preg_replace($re, '=== ', $line, 1), 1);}),
    array( 're' => '/^\*/', 'func' =>  function($line,$re){return preg_replace('/(\[#\w{8}\])?\s*$/', ' ==', preg_replace($re, '== ', $line, 1), 1);}),
    //unordered lists
    array( 're' => '/^---/', 'func' =>  function($line,$re){return preg_replace($re, '***', $line, 1);}),
    array( 're' => '/^--/', 'func' =>  function($line,$re){return preg_replace($re, '**', $line, 1);}),
    array( 're' => '/^-/', 'func' =>  function($line,$re){return preg_replace($re, '*', $line, 1);}),
    //ordered lists
    array( 're' => '/^\+\+\+/', 'func' =>  function($line,$re){return preg_replace($re, '###', $line, 1);}),
    array( 're' => '/^\+\+/', 'func' =>  function($line,$re){return preg_replace($re, '##', $line, 1);}),
    array( 're' => '/^\+/', 'func' =>  function($line,$re){return preg_replace($re, '#', $line, 1);}),
    //line break after each line in paragraph
    array( 're' => '/^[^ =*#|]/', 'func' =>  function($line,$re){return preg_replace('/~?$/','<br \/>',$line, 1);}),
    //make double quotes to triple and triple to double (strong/italic)
    array( 're' => '/\'\'\'/', 'func' => function($line,$re){return preg_replace($re, "TWO_QUOTATIONS", $line);}),
    array( 're' => '/\'\'/', 'func' => function($line,$re){return preg_replace($re, "'''", $line);}),
    array( 're' => '/TWO_QUOTATIONS/', 'func' => function($line,$re){return preg_replace($re, "''", $line);}),
    //footer text (complecated, so I'll leave it as a comment)
    //array( 're' => '/\(\((.+)\)\)/, 'func' => function($line,$re){return line.replace(re,"($1)");}),
    //del
    array( 're' => '/%%(.+)%%/', 'func' => function($line,$re){return preg_replace($re, "<del>$1</del>", $line);}),
    //external links
    array( 're' => '/\[\[([^:>]+?)[:>](http.+?)\]\]/', 'func' => function($line,$re){return preg_replace($re, '[$2 $1]', $line);}),
    //blockquote
    array( 're' => '/^> ?/', 'func' =>
        function($line,$re){
            global $ENV;
            if($ENV['BLOCKQUOTE']){
                return preg_replace($re, '', $line, 1);
            }else{
                $ENV['BLOCKQUOTE']=1;
                return preg_replace($re, "<blockquote>\r\n", $line, 1);
            }
        }
    ),
    //table
    array( 're' => '/^\|/', 'func' =>
    function($line,$re){
        global $ENV;
        if($ENV['TABLE']){
            return '|-'.
                preg_replace('/\|/',"\r\n|",preg_replace('/\|~/',"\r\n!",preg_replace('/\|[hf]?\s*$/', '', $line, 1), 1), 1);
        }else{
            $ENV['TABLE']=1;
            return '{|'.
                preg_replace('/\|/',"\r\n|",preg_replace('/\|~/',"\r\n!",preg_replace('/\|[hf]?\s*$/', '', $line, 1), 1), 1);
            }
        }
    ),
    //end of table
    array( 're' => '/^[ =*#]/', 'func' =>
        function($line,$re){
            global $ENV;
            if($ENV['TABLE']){
                $ENV['TABLE']=0;
                return "|}\r\n".$line;
            }
            return $line;
        })
);

function convLine($line)
{
    global $debug;
    global $ENV;
    global $breakableCheck;
    global $unbreakableCheck;
    if($debug){
        fwrite(STDERR,'------ENV.TABLE-'.$ENV['TABLE'].'-------'.PHP_EOL.$line.PHP_EOL.PHP_EOL);
    }
    for ($i=0; $i < sizeof($breakableCheck); $i++) {
        $bci = $breakableCheck[$i];
        if($debug){
            fwrite(STDERR,'------ENV.TABLE-'.$ENV['TABLE'].'-------'.PHP_EOL.$line.PHP_EOL.$bci['re'].PHP_EOL);
        }
        if(preg_match($bci['re'], $line)){
            $ret = $bci['func']($line, $bci['re']);
            // if(array_key_exists('print', $bci)){
            //     fwrite(STDERR,'------ENV.TABLE-'.$ENV['TABLE'].'-------'.PHP_EOL.$line.PHP_EOL.$bci['re'].PHP_EOL.$ret.PHP_EOL);
            // }
            return $ret;
        }
    }
    for ($i = 0; $i < sizeof($unbreakableCheck); $i++) {
        $ubci = $unbreakableCheck[$i];
        if($debug){
            fwrite(STDERR,'------ENV.TABLE-'.$ENV['TABLE'].'-------'.PHP_EOL.$line.PHP_EOL.$ubci['re'].PHP_EOL);
        }
        if(preg_match($ubci['re'], $line)){
            $line = $ubci['func']($line, $ubci['re']);
        }
    }
    return $line;
}

function finalize($text){
    return preg_replace('/<br \/>(\r\n(?:<!--[^\n]*-->\r\n)*(?:[ =*#\r]|<\/blockquote>|{\|))/m','$1',$text);
}

?>
