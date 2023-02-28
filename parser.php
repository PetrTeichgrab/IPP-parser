
<?php
ini_set('display_errors', 'stderr');

//Za povinnou XML hlavičkou10 následuje kořenový element program (s povinným textovým atribu-
// tem language s hodnotou IPPcode23), který obsahuje pro instrukce elementy instruction. Každý
// element instruction obsahuje povinný atribut order s pořadím instrukce. Při generování elementů
// je pořadí číslováno od 1 v souvislé posloupnosti. Dále element obsahuje povinný atribut opcode
// (hodnota operačního kódu je ve výstupním XML vždy velkými písmeny) a elementy pro odpoví-
// dající počet operandů/argumentů: arg1 pro případný první argument instrukce, arg2 pro případný
// druhý argument a arg3 pro případný třetí argument instrukce. Element pro argument má povinný
// atribut type s možnými hodnotami int, bool, string, nil, label, type, var podle toho, zda se
// jedná o literál, návěští, typ nebo proměnnou, a obsahuje textový element.
// Tento textový element potom nese buď hodnotu literálu (již bez určení typu a bez znaku @),
// jméno návěští, typ, nebo identifikátor proměnné (včetně určení rámce a @). U proměnných vypisujte
// označení rámce vždy velkými písmeny, jak by mělo být již na vstupu. Velikosti písmen samotného
// jména proměnné ponechejte beze změny. Formát celých čísel je dekadický, oktalový nebo hexade-
// cimální dle zvyklostí PHP (viz funkce intval), nicméně na výstup tato čísla vypisujte přesně ve
// formátu, v jakém byla načtena ze zdrojového kódu (např. zůstanou kladná znaménka čísel nebo po-
// čáteční přebytečné nuly). U literálů typu string při zápisu do XML nepřevádějte původní escape
// sekvence, ale pouze pro problematické znaky v XML (např. <, >, &) využijte odpovídající XML
// entity (např. &lt;, &gt;, &amp;). Podobně převádějte problematické znaky vyskytující se v identifi-
// kátorech proměnných. Literály typu bool vždy zapisujte malými písmeny jako false nebo true.

//<instruction order="instructionNumber" opcode="INSTRUCTIONNAME"> 
//  <arg1 type="argType">argName<arg1/>
//<instruction/>

// $group1 = array(
//     'home'  => True,
//     );

// // used for $current_users = 'current';
// $group2 = array(
//     'users.online'      => True,
//     'users.location'    => True,
//     'users.featured'    => True,
//     'users.new'         => True,
//     'users.browse'      => True,
//     'users.search'      => True,
//     'users.staff'       => True,
//     );

// // used for $current_forum = 'current';
// $group3 = array(
//     'forum'     => True,
//     );

//groups of instructions divided by type and amount of arguments

//var

$types = array(
    0 => 'var',
    1 => 'symb',
    2 => 'label'
);

$withoutArg = array(
    'CREATEFRAME' => true,
    'PUSHFRAME' => true,
    'POPFRAME' => true,
    'RETURN' => true,
    'BREAK' => true
);

$oneLabel = array(
    'CALL' => true,
    'LABEL' => true,
    'JUMP' => true
);

$oneVar = array(
    'DEFVAR' => true,
    'POPS' => true
);

$oneSymb = array(
    'EXIT' => true,
    'DPRINT' => true
);
$varAndSymb = array(
    'MOVE' => true,
    'INT2CHAR' => true,
    'STRLEN' => true,
    'TYPE' => true
);

$varSymb1Symb2 = array(
    'ADD' => true,
    'SUB' => true,
    'MUL' => true,
    'IDIV' => true,
    'LT' => true,
    'GT' => true,
    'EQ' => true,
    //'AND' => true,
    'OR' => true,
    'NOT' => true,
    'STRI2INT' => true,
    'CONCAT' => true,
    'GETCHAR' => true,
    'SETCHAR' => true
);

$labelSymb1Symb2 = array(
    'JUMPIFEQ' => true,
    'JUMPIFNEQ' => true,
);

$error = 0;
$lineCount = 0;
$headerOk = false;
$xw = new XMLWriter();
$instructCount = 0;

const HEAD = ".IPPcode23";

startXMLwriter();

while ($line = fgets(STDIN))
{
    $line = ModifyLine($line);
    //checking header
    if($lineCount == 0 && !checkHeader($line))
    {
        exit(21);
    }
    //splitting line into instructions and arguments
    //TODO: print XML header and program block
    $line = splitLine($line);
    
    $instruction = strtoupper($line[0]);

    if(isset($withoutArg[$instruction]))
    {
        $instructCount++;
        printInstruction($line, $instructCount);
        continue;
    }

    if(isset($oneVar[$instruction]))
    {
        if(checkVar($line[1]))
        {
            $instructCount++;
            printInstruction($line, $instructCount, $types[0]);
            continue;
        }
        else
            exit(1);
    }

    if(isset($oneLabel[$instruction]))
    {
        if(checkLabel($line[1]))
        {
            $instructCount++;
            printInstruction($line, $instructCount, $types[2]);
            continue;
        }
        else
            exit(1);
    }

    if(isset($oneSymb[$instruction]))
    {
        if(checkSymbol($line[1]))
        {
            $instructCount++;
            printInstruction($line, $instructCount, $types[1]);
            continue;
        }
        else
        {
            exit(1);
        }
    }

    if(isset($varSymb1Symb2[$instruction]))
    {
        if(checkVar($line[1]) && checkSymbol($line[2]) && checkSymbol($line[3]))
        {
            $instructCount++;
            printInstruction($line, $instructCount, $types[0], $types[1], $types[2]);
            continue;
        }
        else
            exit(1);        
    }

    if(isset($labelSymb1Symb2[$instruction]))
    {
        if(checkLabel($line[1]) && checkSymbol($line[2]) && checkSymbol($line[3]))
        {
            $instructCount++;
            printInstruction($line, $instructCount, $types[0], $types[1], $types[2]);
            continue;
        }        
        else
            exit(1);
    }
    $lineCount++;
}

endXMLwriter();

function checkVar($arg)
{
    if(!preg_match("/^(GF|LF|TF)@[\-\$&%\*!\?_A-Za-z]+[\-\$&%\*!\?_A-Za-z0-9]*$/", $arg))
    {
        return false;
    }
    return true;
}

function checkLabel($arg)
{
    if(!preg_match("/^[\-\$&%\*!\?_A-Za-z]+[\-\$&%\*!\?_A-Za-z0-9]*$/", $arg))
    {
        return false;
    }
    return true;
}
function checkConst($arg)
{
    //check int constant
    if(!preg_match("/^int@-*[0-9]*$/", $arg))
    {
        return false;
    }
    //check bool constant
    elseif(!preg_match("/^bool@true|false{1}$/",$arg))
    {
        return false;
    }
    //check string constant //TODO: string constant regex

    //checking nil constant
    elseif(!preg_match("/^nil@nil$/", $arg))
    {
        return false;
    }
    return true;
}

function checkSymbol($arg)
{
    if(!(checkConst($arg) || checkVar($arg)))
    {
        return false;
    }
    return true;
}
function printInstruction($line, $instructCount, $type1 = "var", $type2 = "var", $type3 = "var")
{
    switch(count($line))
    {
        case 1:
            printNoArgsInstruct($line, $instructCount);
            break;
        case 2:
            printOneArgInstruct($line, $instructCount, $type1);
            break;
        case 3:
            printTwoArgInstruct($line, $instructCount, $type1, $type2);
            break;
        case 4:
            printThreeArgInstruct($line, $instructCount, $type1, $type2, $type3); 
            break;
    }
}
function checkVarArgInstruct(string $variable)
{
    if(!preg_match("/^(GF|LF|TF)@[\-\$&%\*!\?_A-Za-z]+[\-\$&%\*!\?_A-Za-z0-9]*$/", $variable))
    {
        //TODO: error code
        exit(1);
    }
}

function printOneArgInstruct($line, $instructCount, $type1)
{
    global $xw;
    $xw->startElement("instruction");
    $xw->writeAttribute('order', $instructCount);
    $xw->writeAttribute('opcode', $line[0]);
    $xw->startElement("arg1");
    $xw->writeAttribute('type', $type1);
    $xw->endElement();
    $xw->endElement();
}
function printThreeArgInstruct($line, $instructCount, $type1, $type2, $type3)
{
    global $xw;
    $xw->startElement("instruction");
    $xw->writeAttribute('order', $instructCount);
    $xw->writeAttribute('opcode', $line[0]);
    $xw->startElement("arg1");
    $xw->writeAttribute('type', $type1);
    $xw->startElement("arg2");
    $xw->writeAttribute('type', $type2);
    $xw->startElement("arg3");
    $xw->writeAttribute('type', $type3);
    $xw->endElement();
    $xw->endElement();
    $xw->endElement();
    $xw->endElement();
}
function printTwoArgInstruct($line, $instructCount, $type1, $type2)
{
    global $xw;
    $xw->startElement("instruction");
    $xw->writeAttribute('order', $instructCount);
    $xw->writeAttribute('opcode', $line[0]);
    $xw->startElement("arg1");
    $xw->writeAttribute('type', $type1);
    $xw->startElement("arg2");
    $xw->writeAttribute('type', $type2);
    $xw->endElement();
    $xw->endElement();
    $xw->endElement();
}

function printNoArgsInstruct($line, $instructCount)
{
    global $xw;
    $xw->startElement("instruction");
    $xw->writeAttribute('order', $instructCount);
    $xw->writeAttribute('opcode', $line[0]);
    $xw->endElement();
}
function splitLine(string $line)
{
    return explode(' ',$line);
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

function startXMLwriter()
{
    global $xw;
    $xw->openMemory();
    $xw->setIndent(true);
    $xw->setIndentString(' ');
    $xw->startDocument("1.0");
    $xw->startElement("program");
}

function endXMLwriter()
{
    global $xw;
    $xw->endElement();
    $xw->endDocument();
    echo $xw->outputMemory();
}