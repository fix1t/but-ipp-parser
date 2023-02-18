<?php

declare(strict_types=1);

//error codes
// define("DEVEL","\t[DEVEL]: ");
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

    /* TODO */
    public function argHelp()
    {
        echo ("Usage: [...] ...\n");
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

    private function changeXMLCharacters($string)
    {
        $position = 0;
        while (($position = strpos($string, '&', $position + 1)) !== false) {
            $string = substr($string, 0, $position) . '&amp;' . substr($string, $position + 1);
        }
        $position = 0;
        while (($position = strpos($string, '<', $position + 1)) !== false) {
            $string = substr($string, 0, $position) . '&lt;' . substr($string, $position + 1);
        }
        $position = 0;
        while (($position = strpos($string, '>', $position + 1)) !== false) {
            $string = substr($string, 0, $position) . '&gt;' . substr($string, $position + 1);
        }
        if (strpos($string, '/') !== false) {
            exit(LEXICAL_ERROR);
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
            if ($argumentValue[1] == '')
                exit(SYNTAX_ERROR);

            switch ($argumentValue[0]) {
                case 'GF':
                case 'TF':
                case 'LF':
                    $this->checkVarName($argumentValue[1]);
                    return array('var',  $this->changeXMLCharacters($name));

                case 'int':
                case 'bool':
                case 'nil':
                    return array($argumentValue[0], $argumentValue[1]);

                case 'string':
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

    private function instruction($instruction, $arg1 = false, $type1 = false, $arg2 = false, $type2 = false)
    {
        echo "\t<instruction order=\"" . $this->instructionOrder++ . "\" opcode=\"" . $instruction . "\">\n";

        if ($arg1 !== false) {
            echo "\t\t<arg1 type=\"" . $type1 . "\">" . $arg1 . "</arg1>\n";
        }
        if ($arg2 !== false) {
            echo "\t\t<arg2 type=\"" . $type2 . "\">" . $arg2 . "</arg2>\n";
        }

        echo "\t</instruction>\n";
    }

    private function checkType_var_symb($var, $symbol)
    {
        $this->checkType_var($var);
        $this->checkType_symb($symbol);
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
                if ($argumentCount != 2)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                $this->checkType_symb($argType);

                $this->instruction($this->curLine[0], $argValue, $argType);
                break;

                //1 VAR 2 SYMB
            case 'MOVE':
                if ($argumentCount != 3)
                    exit(SYNTAX_ERROR);

                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                list($argType2, $argValue2) = $this->parseInstructionArgument($this->curLine[2]);
                $this->checkType_var_symb($argType, $argType2);

                $this->instruction($this->curLine[0], $argValue, $argType, $argValue2, $argType2);
                break;


            case 'READ':
                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                list($argType2, $argValue2) = $this->parseInstructionArgument($this->curLine[2]);
                $this->instruction($this->curLine[0], $argValue, $argType, $argValue2, $argType2);
                break;

            case 'WRITE':
                list($argType, $argValue) = $this->parseInstructionArgument($this->curLine[1]);
                $this->instruction($this->curLine[0], $argValue, $argType);
                break;


                //1 LABEL
            case 'CALL':
                if ($argumentCount != 2)
                    exit(SYNTAX_ERROR);
                $this->checkVarName($this->curLine[1]);
                $this->instruction($this->curLine[0], $this->curLine[1], 'label');
                break;



            default:
                // echo DEVEL."Operation ".$this->curLine[0]." not found \n\n";
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
