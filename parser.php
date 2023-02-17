<?php
declare(strict_types=1);

//error codes
define("MISSING_HEADER",21);
define("PARAMETER_ERROR",10);

class ArgumentParser {
    private $numberOfArguments;
    private $arguments;
    private $argumentsDone = 1; //skip the firs one.

    public function __construct($argc, $argv) {
        $this->numberOfArguments= $argc;
        $this->arguments = $argv;
    }
    public function getNumberOfArgumets(){
        return $this->numberOfArguments;
    }
    public function getArguments(){
        return $this->arguments;
    }

    public function getNextArgument(){
        if ($this->argumentsDone != $this->numberOfArguments) {
            return $this->arguments[$this->argumentsDone++];
        }
        else {
            return null;
        }
    }

    /* TODO */
    public function argHelp(){
        echo("Usage: [...] ...\n");
    }

    public function parseArguments(){
        $moreArguments = true;
        do{
            $curArgument = $this->getNextArgument();
            if ($curArgument == null) {
                $moreArguments = false;
                //skips parsing when there is no more arguments
                continue;
            }
            //Cuts to position of equals in the string, last character if equals is not in the string
            $toEquals = strpos($curArgument, '=')?strpos($curArgument, '='):strlen($curArgument);
            
            switch (substr($curArgument, 0, $toEquals)) {
                case '--help':
                    $this->argHelp();
                    //there should not be any onther argument besides --help
                    $ALLOWED_ARGS_WITH_HELP = 2;
                    if ($ALLOWED_ARGS_WITH_HELP != $this->numberOfArguments)
                        exit(PARAMETER_ERROR);
                    else
                        exit(0);

                default: 
                    //other parameters are not allowed
                    exit(PARAMETER_ERROR);
            }
            
        } while ($moreArguments == true);
    }
}


class mainParser{

    private $stdin;
    private $curLine;

    private function header(){
        $this->prepareLine();
        $prolog = ".IPPcode23";
        if ($this->curLine != $prolog) {
            echo "Missling prolog.";
            exit(MISSING_HEADER);
        }
        /*  */
        echo "DEVEL: prolog found";
    }

    private function move(){

    }

    private function defvar(){
        
    }
    // ...

    private function prepareLine(){
        $line = fgets($this->stdin);
        $commentPos = strpos($line,'#');
        if ($commentPos) {
            //remove any comment
            $line = substr($line,0,$commentPos);
        }
        $line = trim($line);
        $this->curLine = $line;
    }

    private function parseBody(){
        $this->prepareLine();
        $this->curLine = explode(" ",$this->curLine);
        switch ($this->curLine[0]) {
            case 'DEFVAR':
                $this->defvar();
                break;
            
            default:
                
                break;
        }

    }

    public function parse($stdin){
        $this->stdin = $stdin;

        $this->header();

        $this->parseBody();
        
    }

}


$argumentParser = new ArgumentParser($argc,$argv);

$argumentParser->parseArguments();

$parser = new mainParser();

$parser->parse(fopen("php://stdin","r"));
