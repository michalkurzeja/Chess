<?php

namespace Polcode\ChessBundle\Model\Rules;

use Polcode\ChessBundle\Entity\Pieces;

class CastleRule extends RuleAbstract
{
    public function checkRule(&$args)
    {
        $king = $args['piece'];
        $squares = &$args['squares'];
        
        if( !$king instanceof Pieces\King || $king->getHasMoved() ) {
            return false;
        }
        
        $rooks = $this->chessboard->getRooks( $king->getIsWhite() );
        
        if( empty($rooks) ) {
            return false;
        }
        
        foreach($rooks as $rook) {
            if( $rook->getHasMoved() ) {
                continue;
            }
            
            $rook_vector = $this->chessboard->getVectorFromTo( $king->getCoordinates(), $rook->getCoordinates() );
            
            $castle_square = $king->getCoordinates()->addVector($rook_vector)->addVector($rook_vector);
            
            $castle_moves = $this->chessboard->getMovesAlongVector($king, $rook_vector, $castle_square);
            
            /* $king's path to $castle_square is obstructed or there's any piece in $castle_square */
            if( !$castle_moves || count($castle_moves) < 2 || $this->chessboard->getSquareContent($castle_moves[1]) ) {
                continue;
            }
            
            if( $this->chessboard->isSquareUnderCheck( $castle_moves[0], $king->getIsWhite(), $king->getCoordinates() ) ||
                $this->chessboard->isSquareUnderCheck( $castle_moves[1], $king->getIsWhite(), $king->getCoordinates() ) ) {
                continue;
            }
            
            /* $rook's path to $castle_square is obstructed */
            if( !$this->chessboard->canMoveToSquareAlongVector($rook, $castle_square, $rook_vector->multiplyByScalar(-1)) ) {
                continue;
            }
            
            $squares[] = $castle_square;
        }
    }
    
    public function checkRuleAfterMove(&$args) {}
}
