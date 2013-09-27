<?php

namespace Polcode\ChessBundle\Model\Rules;

use Polcode\ChessBundle\Entity\Pieces;

class DoubleMoveRule extends RuleAbstract
{
    public function checkRule(&$args)
    {
        $piece = $args['piece'];
        $squares = &$args['squares'];
        
        if( !($piece instanceof Pieces\Pawn) ) {
            return false;
        }
        
        $move_vector = $piece->getMoveVectors();
        $move_vector = $move_vector[0];
        
        $square_by_the_way = $piece->getCoordinates()->addVector($move_vector);
        $square = $square_by_the_way->addVector($move_vector);
        
        if( !$piece->getHasMoved() &&
            !$this->chessboard->getSquareContent( $square_by_the_way ) &&
            !$this->chessboard->getSquareContent( $square ) ) {
            
            $squares[] = $square;
        }        
    }
    
    public function checkRuleAfterMove(&$args) {}
}
