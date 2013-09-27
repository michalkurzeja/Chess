<?php

namespace Polcode\ChessBundle\Model\Rules;

abstract class RuleAbstract
{
    protected $chessboard;
    protected $game;
    
    public function __construct($chessboard, $game)
    {
        $this->chessboard = $chessboard;
        $this->game = $game;
    }
    
    abstract public function checkRule(&$args);
    
    abstract public function checkRuleAfterMove(&$args);
}
