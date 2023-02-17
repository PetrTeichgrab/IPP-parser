<?php
ini_set('display_errors', 'stderr');

$lineCount = 0;
$headerOk = false;
const HEAD = ".IPPcode23";

while ($line = fgets(STDIN))
{
    $line = ModifyLine($line);
    #checking header
    if($lineCount == 0 && !checkHeader($line))
    {
        exit(21);
    }
    echo($line);
    $lineCount+=1;
}


function ModifyLine(string $line)
{
    $line = DeleteComments($line);
    $line = DelEdgeWhiteSpace($line);
    return $line;
}
function DelEdgeWhiteSpace(string $line)
{
    return trim($line);
}
function DeleteComments(string $line)
{
    $split = explode('#', $line);
    return $split[0];
}

function checkHeader(string $head)
{
    return $head == HEAD ? true : false;
}