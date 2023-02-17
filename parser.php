<?php
declare(strict_types=1);

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
                        exit(10);
                    else
                        exit(0);

                default: 
                    //other parameters are not allowed
                    exit(10);
            }
            
        } while ($moreArguments == true);
    }
}


class mainParser{
    private $input;

    public function __construct($stdin){
        $input = $stdin;
    }

    private function header(){

    }

    private function move(){

    }
    // ...

    public function parse(){
        $line = fgets($this->input);


    }

}


$argumentParser = new ArgumentParser($argc,$argv);

$argumentParser->parseArguments();

$parser = new mainParser(fopen("php://stdin","r"));

