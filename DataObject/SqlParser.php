<?php
namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name SqlParser
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 1.4
 * @category Catappa ORM
 */

class SqlParser {

    var $handle = null;
    var $type="query";
    var $from=array();
    var $sub=array();
    var $select;
    var $next=0;
    public static $querysections = array('select','from','where','limit','order','group','having');
    public static $functions = array('avg', 'count', 'max', 'min', 'sum', 'nextval', 'currval', 'concat');
    public static $startparens = array('{', '(');
    public static $endparens = array('}', ')');
    public static $tokens = array(',', ' ');
    private $query = '';
    public function __construct() { }

 
    public static function Tokenize($sqlQuery,$cleanWhitespace = true) {
        $sqlQuery = strtolower($sqlQuery);
      
        $regex = '('; # begin group
        $regex .= '(?:--|\\#)[\\ \\t\\S]*'; # inline comments
        $regex .= '|(?:<>|<=>|>=|<=|==|=|!=|!|<<|>>|<|>|\\|\\||\\||&&|&|-|\\+|\\*(?!\/)|\/(?!\\*)|\\%|~|\\^|\\?)'; # logical operators
        $regex .= '|[\\[\\]\\(\\),;`]|\\\'\\\'(?!\\\')|\\"\\"(?!\\"")'; # empty single/double quotes
        $regex .= '|".*?(?:(?:""){1,}"|(?<!["\\\\])"(?!")|\\\\"{2})|\'.*?(?:(?:\'\'){1,}\'|(?<![\'\\\\])\'(?!\')|\\\\\'{2})'; # quoted strings
        $regex .= '|\/\\*[\\ \\t\\n\\S]*?\\*\/'; # c style comments
        $regex .= '|(?:[\\w:@]+(?:\\.(?:\\w+|\\*)?)*)'; # words, placeholders, database.table.column strings
        $regex .= '|[\t\ ]+';
        $regex .= '|[\.]'; #period

        $regex .= ')'; # end group

        // get global match
        preg_match_all( '/' . $regex . '/smx', $sqlQuery, $result );

        // return tokens
        return $result[0];

    }

    function nextFrom()
    {
        return $this->from[$this->next];
        $this->next++;
    }

    private function readsub2($tokens, &$position) {

        $sub =array();
        $index= array_push($sub  , array("soz"=>"select","val"=>array()));
        $tokenCount = count( $tokens );
        $position ++;
        $subs=1;
        while ($position < $tokenCount ) {

            if (in_array( $tokens[$position], self::$startparens ))
            $subs++;
            else if (in_array( $tokens[$position], self::$endparens ))
            $subs--;
            // $index=  array_push($sub , $tokens[$position]);
            $this->setlen($tokens, $sub, $position,$index);
            if($subs==0)
            return $sub;
            $position ++;
        }
        return $sub;
    }



    public function parssing($sql)
    {
        if (! isset( $this )) {
            $handle = new SqlParser();
        } else {
            $handle = $this;
        }

        $tokens = self::Tokenize( $sql);
        $tokenCount = count( $tokens );
        $parts = array();
        $section = $tokens[0];
        $index=null;
        $sels=0;
        $old="";
        $kelime="";
        // print_r(array_filter($tokens));
        for ($t = 0; $t < $tokenCount; $t ++) {

            if (in_array( $tokens[$t], self::$querysections )) {
                $kelime = $tokens[$t];
                $index=  array_push($parts , array("soz"=>$tokens[$t],"val"=>array()));
                if($tokens[$t]=="from")
                {
                    $from=true;
                    $f=0;
                }
                elseif($tokens[$t]=="select")
                {
                    if($tokens[$t-1]=="(")
                    {
                        $parts[$index-1]['soz']="SUBQUERY";
                        $subid=  array_push($this->sub,$this->readsub2($tokens, $t));
                        array_push($parts[$index-1]['val'],$subid-1);

                    }
                }
            }
            elseif(strlen(trim($tokens[$t])))
            {

                if($from){
                    if($f==0){
                        $fr = array_push($this->from,array("class"=>0,"alias"=>0));
                        $f++;
                        $this->from[$fr-1]["class"]=$tokens[$t];
                    }else{
                        $from=false;
                        $this->from[$fr-1]["alias"]=$tokens[$t];
                    }
                }

                if(in_array( $tokens[$t], self::$functions)&&$kelime=="select")
                $this->type="function";
                array_push($parts[$index-1]['val'],$tokens[$t]);

            }

        }
        return $parts;

    }

    function setlen($tokens,$parts,$t,$index) {
        if (in_array( $tokens[$t], self::$querysections )) {
            $index=  array_push($parts , array("soz"=>$tokens[$t],"val"=>array()));
            if($tokens[$t]=="from"){

                $this->subfrom=true;
            }

            elseif($tokens[$t]=="select")
            {
                if($tokens[$t-1]=="(")
                {
                    $parts[$index-1]['soz']="SUBQUERY";
                    $subid=  array_push($this->sub,$this->readsub2($tokens, $t));
                    array_push($parts[$index-1]['val'],$subid-1);
                }
            }

        }

        elseif(strlen(trim($tokens[$t])))
        {
            array_push($parts[$index-1]['val'],$tokens[$t]);
            if($this->subfrom==true)
            $parts["from"]= $tokens[$t];
            $this->subfrom=false;

        }
    }
}