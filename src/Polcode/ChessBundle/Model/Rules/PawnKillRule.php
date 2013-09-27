<?php

namespace Polcode\ChessBundle\Model\Rules;

use Polcode\ChessBundle\Entity\Pieces;
use Polcode\ChessBundle\Exception\OutOfBoardException;

class PawnKillRule extends RuleAbstract
{
    public function checkRule(&$args)
    {
        $piece = $args['piece'];
        $squares = &$args['squares'];
        
        /* check if $piece is a Pawn */
        if( !($piece instanceof Pieces\Pawn) ) {
            return;
        }
        
        $kill_squares = $this->getKillSquares($piece);
        
        foreach($kill_squares as $kill_square) {
            $target = $this->chessboard->getSquareContent( $kill_square );
            
            if( $target && $target->getIsWhite() != $piece->getIsWhite() ) {
                 $squares[] = $kill_square;
             }
        }
    }

    public function checkRuleAfterMove(&$args) {}

    public function getKillSquares($piece)
    {
        $kill_squares = array();
        
        $piece_coords = $piece->getCoordinates();
        
        $move_vector = $piece->getMoveVectors();
        $kill_vector = $move_vector[0]->setX( $move_vector[0]->getX() - 1 );
        
        $kill_square = $piece_coords->addVector( $kill_vector );
        
        if( $this->chessboard->isSquareWithinBoard($kill_square) ) {
            $kill_squares[] = $kill_square;
        }
        
        $kill_vector->setX( $kill_vector->getX() + 2 );
        
        $kill_square = $piece_coords->addVector( $kill_vector );
        
        if( $this->chessboard->isSquareWithinBoard($kill_square) ) {
            $kill_squares[] = $kill_square;
        }
        
        return $kill_squares;
    }
}
