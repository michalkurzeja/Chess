<?php

namespace Polcode\ChessBundle\Model\Rules;

use Polcode\ChessBundle\Entity\Pieces;

class EnPassantRule extends RuleAbstract
{
     public function checkRule(&$args)
     {
        $piece = $args['piece'];
        $squares = &$args['squares'];

        $en_passable = $this->game->getEnPassable();
        
        /* check if en passant can be done */
        if( !$en_passable ) {
            return false;
        }
        
        /* check if $piece is a Pawn */
        if( !($piece instanceof Pieces\Pawn) ) {
            return false;
        }
        
        $en_passable_coords = $en_passable->getCoordinates();
        $piece_coords = $piece->getCoordinates();
        
        /* check if both pieces are in the same rank (if not, cant perform en passant) */
        if( $en_passable_coords->getY() != $piece_coords->getY() ) {
            return false;
        }
        
        $difference = $en_passable_coords->getX() - $piece_coords->getX();

        /* check if both pieces stand on adjacent squares */        
        if( abs($difference) != 1 ) {
            return false;
        }
        
        $move_vector = $piece->getMoveVectors();
        $move_vector = $move_vector[0]->setX( $move_vector[0]->getX() + $difference );
        
        $squares[] = $piece_coords->addVector( $move_vector );         
     }

     public function checkRuleAfterMove(&$args) {}
}
