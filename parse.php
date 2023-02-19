<?php

declare(strict_types=1);

define("DEVEL", "\t[DEVEL]: ");
define("SUCCESS", 0);
define("PARAMETER_ERROR", 10);
define("HEADER_ERROR", 21);
define("UNEXPECTED_COMMAND", 22);
define("LEXICAL_ERROR", 23);
define("SYNTAX_ERROR", 23);
define("SYMBOL_TYPES", "string int bool nil var");
define("ALLOWED_SPECIAL_CHARS", "_-$&%*!?");

class ArgumentParser
{
    private $numberOfArguments;
    private $arguments;
    private $argumentsDone = 1; //skip the firs one.

    public function __construct($argc, $argv)
    {
        $this->numberOfArguments = $argc;
        $this->arguments = $argv;
    }
    public function getNumberOfArgumets()
    {
        return $this->numberOfArguments;
    }
    public function getArguments()
    {
        return $this->arguments;
    }

    public function getNextArgument()
    {
        if ($this->argumentsDone != $this->numberOfArguments) {
            return $this->arguments[$this->argumentsDone++];
        } else {
            return null;
        }
    }

    public function argHelp()
    {
        echo ("------------------------------------Usage------------------------------------ 
1 - Filter script reads the source code in IPPcode23 from the standard input. 
2 - Checks the lexical and syntactic correctness of the code.
3 - Prints the XML representation to standard output.\n");
    }

    public function parseArguments()
    {
        $moreArguments = true;
        do {
            $curArgument = $this->getNextArgument();
            if ($curArgument == null) {
                $moreArguments = false;
                //skips parsing when there is no more arguments
                continue;
            }
            //Cuts to position of equals in the string, last character if equals is not in the string
            $toEquals = strpos($curArgument, '=') ? strpos($curArgument, '=') : strlen($curArgument);

            switch (substr($curArgument, 0, $toEquals)) {
                case '--help':
                    $this->argHelp();
                    //there should not be any onther argument besides --help
                    $ALLOWED_ARGS_WITH_HELP = 2;
                    if ($ALLOWED_ARGS_WITH_HELP != $this->numberOfArguments)
                        exit(PARAMETER_ERROR);
                    else
                        exit(SUCCESS);

                default:
                    //other parameters are not allowed
                    exit(PARAMETER_ERROR);
            }
        } while ($moreArguments == true);
    }
}


class mainParser
{

    private $stdin;
    private $curLine;
    private $instructionOrder = 1;

    private function prepareLine()
    {
        $line = fgets($this->stdin);
        //EOF = false
        if ($line == false) {
            $this->curLine = false;
            return;
        }

        //remove any comment / whitespace
        $commentPos = strpos($line, '#');
        if (is_numeric($commentPos)) {
            $line = substr($line, 0, $commentPos);
        }
        $line = trim($line);

        //EMPTY LINE / COMMENT ONLY -> get another
        if ($line == null)
            $this->prepareLine();
        else {
            $line = explode(" ", $line);

            //remove empty items from array
            foreach ($line as $key => $item) {
                if (empty($item)) {
                    unset($line[$key]);
                }
            }
            $line = array_values($line);
            $this->curLine = $line;
        }
        return;
    }

    private function header()
    {
        //check if there is a prolog on the input
        $this->prepareLine();

        //empty input
        if ($this->curLine[0] === false)
            exit(HEADER_ERROR);

        $prolog = ".IPPcode23";
        if ($this->curLine[0] != $prolog) {
            // echo "[DEVEL]: Missling prolog.\n";
            exit(HEADER_ERROR);
        }
        // echo "\t[DEVEL]: prolog found.\n";

        //print XML prolog
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<program language=\"IPPcode23\">\n";
    }

    private function checkEscapeSequences($string)
    {
        $position = -1;
        while (($position = strpos($string, '\\', $position + 1)) !== false) {
            // \x00 - \xFF
            if ($string[$position + 1] == 'x') {
                $regularExpression = "/^[xX][a-fA-F][a-fA-F]*$/";
                if (preg_match($regularExpression, substr($string, $position + 1, 3)) == 1) {
                    continue;
                }
                exit(SYNTAX_ERROR);
                // \000 - \377
            } else {
                $regularExpression = "/^[0-9][0-9][0-9]$/";
                if (preg_match($regularExpression, substr($string, $position + 1, 3)) == 1) {
                    if (substr($string, $position + 1, 3) <= 377) {
                        //pass
                        continue;
                    }
                }
                exit(SYNTAX_ERROR);
            }
        }
    }

    private function isNumber($number)
    {
        if (is_numeric($number))
            return;
        if (preg_match("/^0[0-7]*$/", $number))
            return;
        if (preg_match("/^0[xX][0-9a-fA-F]*$/", $number))
            return;
        exit(SYNTAX_ERROR);
    }

    private function isBool($bool){
        if (strtolower($bool) == 'false' || strtolower($bool) == 'true') {
            return;
        }
        exit(SYNTAX_ERROR);
    }

    private function changeXMLCharacters($string)
    {
        if (empty($string)) {
            return;
        }
        $position = -1;
        while (($position = strpos($string, '&', $position + 1)) !== false) {
            $string = substr($string, 0, $position) . '&amp;' . substr($string, $position + 1);
        }
        $position = -1;
        while (($position = strpos($string, '<', $position + 1)) !== false) {
            $string = substr($string, 0, $position) . '&lt;' . substr($string, $position + 1);
        }
        $position = -1;
        while (($position = strpos($string, '>', $position + 1)) !== false) {
            $string = substr($string, 0, $position) . '&gt;' . substr($string, $position + 1);
        }

        return $string;
    }

    private function checkVarName($varName)
    {
        //cannot start with numbers
        $regularExpression = "/^[" . preg_quote(ALLOWED_SPECIAL_CHARS) . "a-zA-Z][" . preg_quote(ALLOWED_SPECIAL_CHARS) . "a-zA-Z0-9]*$/";
        if (preg_match($regularExpression, $varName)) {
            return;
        } else {
            exit(SYNTAX_ERROR);
        }
    }

    private function parseInstructionArgument($name)
    {
        $argumentValue = explode('@', $name);
        $valuesFound = count($argumentValue);

        // ...@...
        if ($valuesFound == 2) {
            switch ($argumentValue[0]) {
                case 'GF':
                case 'TF':
                case 'LF':
                    $this->checkVarName($argumentValue[1]);
                    return array('var',  $this->changeXMLCharacters($name));

                case 'nil':
                    if ($argumentValue[1] != 'nil')
                        exit(LEXICAL_ERROR);
                    return array($argumentValue[0], $argumentValue[1]);

                case 'int':
                    $this->isNumber($argumentValue[1]);
                    return array($argumentValue[0], $argumentValue[1]);

                case 'bool':
                    $this->isBool($argumentValue[1]);
                    return array($argumentValue[0], strtolower($argumentValue[1]));

                case 'string':
                    $this->checkEscapeSequences($argumentValue[1]);

                    return array($argumentValue[0], $this->changeXMLCharacters($argumentValue[1]));

                default:
                    exit(LEXICAL_ERROR);
            }
            // ...
        } else if ($valuesFound == 1) {
            switch ($argumentValue[0]) {
                case 'int':
                case 'string':
                case 'bool':
                case 'nil':
                    return array('type', $argumentValue[0]);

                default:
                    exit(LEXICAL_ERROR);
            }
            // ... ... ... 
        } else {
            exit(SYNTAX_ERROR);
        }
    }

    private function instruction($instruction, $arg1 = false, $type1 = false, $arg2 = false, $type2 = false, $arg3 = false, $type3 = false)
    {
        echo "\t<instruction order=\"" . $this->instructionOrder++ . "\" opcode=\"" . $instruction . "\">\n";

        if ($arg1 !== false) {
            echo "\t\t<arg1 type=\"" . $type1 . "\">" . $arg1 . "</arg1>\n";
        }
        if ($arg2 !== false) {
            echo "\t\t<arg2 type=\"" . $type2 . "\">" . $arg2 . "</arg2>\n";
        }
        if ($arg3 !== false) {
            echo "\t\t<arg3 type=\"" . $type3 . "\">" . $arg3 . "</arg3>\n";
        }

        echo "\t</instruction>\n";
    }

    private function checkType_var($var)
    {
        if ($var !== 'var')
            exit(SYNTAX_ERROR);
    }

    private function checkType_symb($symbol)
    {
        if (strpos(SYMBOL_TYPES, $symbol) === false)
            exit(SYNTAX_ERROR);
    }

    private function checkType_type($type)
    {
        if ($type != 'type')
            exit(SYNTAX_ERROR);
    }

    private function parseBody()
    {
        $this->prepareLine();

        //EOF
        if ($this->curLine == false)
            return;

        //parse input
        //expect:
        //      [0]operationName, [1]arg1, [2]arg2 ...
        $this->curLine[0] = strtoupper($this->curLine[0]);
        $argumentCount = count($this->curLine);

        switch ($this->curLine[0]) {
                //NO ARGUMENT
            case 'CREATEFRAME':
            case 'PUSHFRAME':
            case 'POPFRAME':
            case 'RETURN':
            case 'BREAK':
                if ($argumentCount != 1)
                    exit(SYNTAX_ERROR);
                $this->instruction($this->curLine[0]);
                break;

                //1 VAR
            case 'DEFVAR':
            case 'POPS':
                if ($argumentCount != 2)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                $this->checkType_var($argType);

                $this->instruction($this->curLine[0], $argValue, $argType);
                break;

                //1 SYMB
            case 'PUSHS':
            case 'WRITE':
            case 'EXIT':
            case 'DPRINT':
                if ($argumentCount != 2)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                $this->checkType_symb($argType);

                $this->instruction($this->curLine[0], $argValue, $argType);
                break;

                //1 VAR 2 SYMB
            case 'MOVE':
            case 'INT2CHAR':
            case 'STRLEN':
            case 'TYPE':
                if ($argumentCount != 3)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                list($argType2, $argValue2) = $this->parseInstructionArgument($this->curLine[2]);
                $this->checkType_var($argType);
                $this->checkType_symb($argType2);


                $this->instruction($this->curLine[0], $argValue, $argType, $argValue2, $argType2);
                break;

                //1 VAR 2 SYMB 3 SYMB
            case 'ADD':
            case 'SUB':
            case 'MUL':
            case 'IDIV':
            case 'LT':
            case 'EQ':
            case 'GT':
            case 'AND':
            case 'OR':
            case 'STRI2INT':
            case 'CONCAT':
            case 'GETCHAR':
            case 'SETCHAR':

                if ($argumentCount != 4)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                list($argType2, $argValue2) = $this->parseInstructionArgument($this->curLine[2]);
                list($argType3, $argValue3) = $this->parseInstructionArgument($this->curLine[3]);
                $this->checkType_var($argType);
                $this->checkType_symb($argType2);
                $this->checkType_symb($argType3);
                $this->instruction($this->curLine[0], $argValue, $argType, $argValue2, $argType2, $argValue3, $argType3);
                break;



                //1 VAR 2 TYPE
            case 'READ':
                if ($argumentCount != 3)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                list($argType2, $argValue2) = $this->parseInstructionArgument($this->curLine[2]);
                $this->checkType_var($argType);
                $this->checkType_type($argType2);

                $this->instruction($this->curLine[0], $argValue, $argType, $argValue2, $argType2);
                break;

                //1 LABEL
            case 'CALL':
            case 'LABEL':
            case 'JUMP':
                if ($argumentCount != 2)
                    exit(SYNTAX_ERROR);
                $this->checkVarName($this->curLine[1]);
                $this->instruction($this->curLine[0], $this->curLine[1], 'label');
                break;

                //1 LABEL 2 SYMB 3 SYMB
            case 'JUMPIFEQ':
            case 'JUMPIFNEQ':
                if ($argumentCount != 4)
                    exit(SYNTAX_ERROR);

                $this->checkVarName($this->curLine[1]);
                list($argType2, $argValue2) = $this->parseInstructionArgument($this->curLine[2]);
                list($argType3, $argValue3) = $this->parseInstructionArgument($this->curLine[3]);
                $this->checkType_symb($argType2);
                $this->checkType_symb($argType3);

                $this->instruction($this->curLine[0], $this->curLine[1], 'label', $argValue2, $argType2, $argValue3, $argType3);
                break;

                //1 VAR 2SYMB (3 SYMB)
            case 'NOT':

                if ($argumentCount < 3 || $argumentCount > 4)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                list($argType2, $argValue2) = $this->parseInstructionArgument($this->curLine[2]);
                $this->checkType_var($argType);
                $this->checkType_symb($argType2);

                if ($argumentCount == 4) {
                    list($argType3, $argValue3) = $this->parseInstructionArgument($this->curLine[3]);
                    $this->checkType_symb($argType3);
                } else {
                    list($argType3, $argValue3) = [false, false];
                }

                $this->instruction($this->curLine[0], $argValue, $argType, $argValue2, $argType2, $argValue3, $argType3);
                break;


            default:
                echo DEVEL . "Operation " . $this->curLine[0] . " not found \n\n";
                exit(UNEXPECTED_COMMAND);
        }

        //recursive calling
        $this->parseBody();
    }

    public function parse($stdin)
    {
        $this->stdin = $stdin;

        $this->header();

        $this->parseBody();

        //finish
        echo "</program>\n";
        return;
    }
}


$argumentParser = new ArgumentParser($argc, $argv);

$argumentParser->parseArguments();

$parser = new mainParser();

$parser->parse(fopen("php://stdin", "r"));

exit(SUCCESS);
