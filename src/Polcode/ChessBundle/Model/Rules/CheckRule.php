<?php

namespace Polcode\ChessBundle\Model\Rules;

use Polcode\ChessBundle\Exception\OutOfBoardException;
use Polcode\ChessBundle\Entity\Pieces;
use Polcode\ChessBundle\Model\Vector;

class CheckRule extends RuleAbstract
{   
    public function __counstruct($chessboard, $game)
    {
        parent::construct($chessboard, $game);
    }
    
    public function checkRule(&$args)
    {
        $piece = $args['piece'];
        $squares = &$args['squares'];
        
        $squares = $this->getSafeMoves($piece, $squares);
    }
    
    public function checkRuleAfterMove(&$args)
    {
        $piece = $args['piece'];
        
        $this->updateCheckStatus();
    }
    
    public function updateCheckStatus()
    {
        $pieces = $this->chessboard->getPieces();
        $king_white = $this->chessboard->getKing(true);
        $king_black = $this->chessboard->getKing(false);
        
        foreach($pieces as $piece) {
            if( !($piece instanceof Pieces\King) ) {
                $king = $piece->getIsWhite() ? $king_black : $king_white;
                
                if( $this->isPieceCheckingKing($piece, $king) ) {
                    $piece->setIsChecking( true );
                } else {
                    $piece->setIsChecking( false );
                }
            }
        }
    }
    
    public function isPieceCheckingKing($piece, $king = null)
    {
        if( !$king ) {
            $king = $this->chessboard->getKing(!$piece->getIsWhite());
        }
        
        return $this->chessboard->canAssaultSquare($piece, $king->getCoordinates());
    }
    
    public function getCheckingPieces($is_white)
    {
        $checking_pieces = $this->chessboard->getCheckingPieces($is_white);
        
        if( empty($checking_pieces) ) {
            return null;
        }
        
        return $checking_pieces;
    }
     
    public function isSquareUnderCheck($square, $king)
    {
        return $this->chessboard->isSquareUnderCheck($square, $king->getIsWhite(), $king->getCoordinates());
    }
    
    public function getPieceCheckingOnMyKingVector($piece)
    {
        if( $piece instanceof Pieces\King ) {
            return null;
        }
        
        $king = $this->chessboard->getKing( $piece->getIsWhite() );
        $king_square = $king->getCoordinates();
        $piece_square = $piece->getCoordinates();
        
        $king_vector = $this->chessboard->getVectorFromTo( $king_square, $piece_square );
        
        if( $king_vector ) {        
           $square = $piece_square->addVector($king_vector);
           try {
               while( null === $this->chessboard->getSquareContent($square) ) { /* add empty squares */
                   $square->setCoordinates($square->addVector($king_vector));
               }
               
               $found_piece = $this->chessboard->getSquareContent($square);
               
               if( $found_piece->getIsWhite() != $piece->getIsWhite() &&
                   $this->canCheckKingFromDistance( $found_piece, $king_square, $king_vector, $piece ) ) {
                   
                   return $found_piece;
               }
           } catch(OutOfBoardException $e) {} /* the end of the board has been reached */            
        }
        
        return null;
    }
     
    public function canSafelyMoveFromSquare($piece)
    {
        if( $piece instanceof Pieces\King ) {
            return true;
        }
        
        $found_piece = $this->getPieceCheckingOnMyKingVector($piece);
        
        return $found_piece ? false : true;
    }
    
    public function getSafeMoves($piece, &$moves)
    {
        if( $piece instanceof Pieces\King ) {
            return $this->getKingSafeMoves($piece, $moves);
        }            
        
        $checking_pieces = $this->getCheckingPieces(!$piece->getIsWhite());
        
        if( $checking_pieces ) {
            return $this->getPieceSafeMoves($piece, $moves, $checking_pieces);
        }
        
        if( $this->canSafelyMoveFromSquare($piece) ) {
            return $moves;
        }
          
        return $this->getMovesAlongCheckVector($piece, $moves);
    }
    
    public function getMovesAlongCheckVector($piece, $moves)
    {   
        $threat = $this->getPieceCheckingOnMyKingVector($piece);
        
        $check_squares = $this->getCheckSquares($piece, $threat, true);
        $check_squares[] = $threat->getCoordinates();
        
        $safe_moves = array();
        
        foreach($moves as $square) {
            if( in_array($square, $check_squares) ) {
                $safe_moves[] = $square;
            }
        }
        
        return empty($safe_moves) ? null : $safe_moves;
    }
    
    public function getKingSafeMoves($king, $moves)
    {
        if( !($king instanceof Pieces\King) ) {
            return null;
        }
        
        $safe_moves = array();
        
        foreach($moves as $square) {
            if( !$this->isSquareUnderCheck($square, $king) ) {
                $safe_moves[] = $square;
            }
        }
        
        return empty( $safe_moves ) ? null : $safe_moves;
    }
    
    public function getPieceSafeMoves($piece, $moves, $checking_pieces)
    {
        if( count( $checking_pieces ) > 1 ) {
            return null;
        }
        
        $safe_moves = array();
        
        $potential_threat = $this->getPieceCheckingOnMyKingVector($piece);
        
        $check_moves = $this->getCheckSquares($piece, $checking_pieces[0]);

        foreach($moves as $square) {
            if( $this->breaksCheckByKill($piece, $square, $potential_threat, $checking_pieces[0]) ) {
                $safe_moves[] = $square;
            }
            
            if( $this->breaksCheckByShielding($piece, $square, $potential_threat, $check_moves, $checking_pieces[0]) ) {
                $safe_moves[] = $square;
            }
        }
        
        return empty( $safe_moves ) ? null : $safe_moves;
    }
    
    public function getCheckSquares($piece, $checking_piece, $ignore_piece = false)
    {
        if( $checking_piece instanceof Pieces\Knight ) {
            return null;
        }
        
        $king_square = $this->chessboard->getKing($piece->getIsWhite())->getCoordinates();
        $checking_piece_square = $checking_piece->getCoordinates();
        $check_vector = $this->chessboard->getVectorFromTo($checking_piece_square, $king_square, true);

        $ignored_square = $ignore_piece ? $piece->getCoordinates() : null;

        return $this->chessboard->getMovesAlongVector($checking_piece, $check_vector, $king_square, $ignored_square);
    }
    
    public function breaksCheckByKill($piece, $square, $potential_threat, $checking_piece)
    {
        if( $checking_piece->getCoordinates() == $square ) {
            $hostile_piece = $this->chessboard->getSquareContent($square);
            
            if( $potential_threat && $potential_threat != $hostile_piece ) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    public function breaksCheckByShielding($piece, $square, $potential_threat, $check_moves, $checking_piece)
    {
        if( !$check_moves ) {
            return false;
        }

        if($potential_threat && $checking_piece != $potential_threat) {
            return false;
        }

        $king_square = $this->chessboard->getKing($piece->getIsWhite())->getCoordinates();
        
        if( $square != $king_square && in_array($square, $check_moves) ) {
            return true;
        }
        
        return false;
    }
    
    /* ignored piece - piece that won't be taken into calculations (in case it moves from it's square) */
    public function canCheckKingFromDistance(   Pieces\Piece $piece, Vector $king_square, Vector $king_vector, 
                                                Pieces\Piece $ignored_piece = null)
    {
        if( $piece instanceof Pieces\Pawn || $piece instanceof Pieces\Knight || $piece instanceof Pieces\King ) {
            return false;
        }
        
        return $this->chessboard->canMoveToSquareAlongVector( $piece, $king_square, $king_vector->multiplyByScalar( -1 ), $ignored_piece );
    }
}