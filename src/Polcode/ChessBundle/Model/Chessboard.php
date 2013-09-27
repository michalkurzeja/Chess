<?php

namespace Polcode\ChessBundle\Model;

use Polcode\ChessBundle\Exception\OutOfBoardException;
use Polcode\ChessBundle\Model\Vector;
use Polcode\ChessBundle\Model\Rules\PawnKillRule;
use Polcode\ChessBundle\Entity\Pieces\Piece;

class Chessboard
{
    private $whites;
    
    private $blacks;
    
    private $board;
    
    private $logger;
    
    public function __construct($whites, $blacks, $logger)
    {
        $this->_init($whites, $blacks);
        $this->logger = $logger;
    }
    
    private function _init($whites, $blacks)
    {
        $this->whites = $whites;
        $this->blacks = $blacks;
        
        $this->board = array();
        
        foreach($whites as &$piece) {
            $this->addPiece($piece);
        }

        foreach($blacks as &$piece) {
             $this->addPiece($piece);
        }
    } 
    
    public function addPiece($piece)
    {
        $this->board[$piece->getFile() . $piece->getRank()] = $piece;
    }
    
    public function getEnPassantSquare($en_passant_piece)
    {
        if(!$en_passant_piece instanceof \Polcode\ChessBundle\Entity\Pieces\Pawn) {
            return null;
        }
        
        $move_vector = $en_passant_piece->getMoveVectors();
        $move_vector = $move_vector[0]->multiplyByScalar(-1);
        
        return $en_passant_piece->getCoordinates()->addVector($move_vector);
    }
    
    public function removePiece(Piece $piece)
    {
        $pieces = $this->getPieces( $this->getColor( $piece->getIsWhite() ) );
        
        $coords = $piece->getCoordinates();
        
        unset($pieces[array_search($piece, $pieces)]);
        unset($this->board[$coords->getX() . $coords->getY()]);
    }
    
    public function findPieceById($piece_id)
    {
        $pieces = $this->getPieces();
        
        foreach($pieces as $p) {
            if( $p->getId() == $piece_id ) {
                return $p;
            }
        }
        
        return null;
    }

    public function getPieces($color = null)
    {
        if($color == 'white') {
            return $this->whites;
        }
        
        if($color == 'black') {
            return $this->blacks;
        }
        
        return array_merge($this->whites, $this->blacks);
    }
    
    public function getColor($is_white)
    {
        if($is_white) {
            return 'white';
        }
            
        return 'black';
    }
    
    public function getKing($white)
    {
        if($white) {
            $pieces = $this->whites;
        } else {
            $pieces = $this->blacks;
        }
        
        foreach($pieces as $piece) {
            if( $piece instanceof \Polcode\ChessBundle\Entity\Pieces\King ) {
                return $piece;
            }
        }
        
        /* virtually impossible... */
        return null;
    }
    
    public function getRooks($white)
    {
        if($white) {
            $pieces = $this->whites;
        } else {
            $pieces = $this->blacks;
        }
        
        $rooks = array();
        
        foreach($pieces as $piece) {
            if( $piece instanceof \Polcode\ChessBundle\Entity\Pieces\Rook ) {
                $rooks[] = $piece;
            }
        }
        
        return $rooks;
    }
    
    public function getCastleDataAsArray($white)
    {
        $castle_data = $this->getCastleData($white);
        
        if(!$castle_data) {
            return null;
        }
        
        $arr = array();
        
        foreach($castle_data as $index => $data) {
            $arr[$index] = array();
            $arr[$index]['square'] = $data['square']->toArray();
            $arr[$index]['rook_square'] = $data['rook_square']->toArray();
            $arr[$index]['rook'] = $data['rook']->toShortArray();
        }
        
        return $arr;
    }
    
    public function getCastleData($white)
    {
        $king = $this->getKing($white);
        
        if($king->getHasMoved()) {
            return null;
        }
        
        $king_square = $king->getCoordinates();
        
        $castle_data = array();
        
        $castle_data[0] = array();
        $castle_data[1] = array();
        
        $castle_square = $king_square->addVector( new Vector(2, 0) );
        $rook_square = $king_square->addVector( new Vector(1, 0) );
        $rook = $this->getRookOnVector($king, $this->getVectorFromTo($king_square, $castle_square));
        
        $castle_data[0]['square'] = $castle_square;
        $castle_data[0]['rook_square'] = $rook_square;
        $castle_data[0]['rook'] = $rook;
        
        $castle_square = $king_square->addVector( new Vector(-2, 0) );
        $rook_square = $king_square->addVector( new Vector(-1, 0) );
        $rook = $this->getRookOnVector($king, $this->getVectorFromTo($king_square, $castle_square));
        
        $castle_data[1]['square'] = $castle_square;
        $castle_data[1]['rook_square'] = $rook_square;
        $castle_data[1]['rook'] = $rook;

        return $castle_data;
    }
    
    public function getRookOnVector($piece, $vector)
    {
        $rooks = $this->getRooks($piece->getIsWhite());
        
        foreach($rooks as $rook) {
            $rook_vector = $this->getVectorFromTo($piece->getCoordinates(), $rook->getCoordinates());
            
            if($rook_vector == $vector) {
                return $rook;
            }
        }
        
        return null;
    }
    
    public function getCheckingPieces($is_white)
    {
        $pieces = $this->getPieces($this->getColor($is_white));
        
        $checking_pieces = array();
        
        foreach($pieces as $piece) {
            if( !($piece instanceof \Polcode\ChessBundle\Entity\Pieces\King) &&
                $piece->getIsChecking()) {
                    
                $checking_pieces[] = $piece;
            }
        }
        
        return empty($checking_pieces) ? null : $checking_pieces;
    }
    
    /* $white_asking == true => check if blacks are checking $square */
    public function isSquareUnderCheck($square, $white_asking, $king_square)
    {
        $pieces = $this->getPieces($this->getColor(!$white_asking));
        
        foreach($pieces as $piece) {
            if( $this->canAssaultSquare($piece, $square, $king_square) ) {
                return true;
            }
        }
        
        return false;
    }
    
    public function isOneOfThePiecesOnSquare($pieces_array, $square)
    {
        foreach($pieces_array as $piece) {
            if( $piece->getCoordinates() == $square ) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getVectorFromTo(Vector $square_from, Vector $square_to)
    {
        $vector = $square_from->multiplyByScalar( -1 )->addVector( $square_to );
        
        $x = $vector->getX();
        $y = $vector->getY();
        
        if(!$x && !$y) {
            return false;
        } else if($x) {
            $length = abs($x);
        } else {
            $length = abs($y);
        }

        $vector = $vector->multiplyByScalar( 1/$length );

        if( ( abs($vector->getX()) == 1 || abs($vector->getX()) == 0 ) &&   /* true if $square_from and $square_to ... */
            ( abs($vector->getY()) == 1 || abs($vector->getY()) == 0 ) ) {  /* ... share rank, row or diagonal */
            
            return $vector;
        }
            
        return null;
    }
    
    public function getMovesAlongVector(Piece $piece, Vector $vector, Vector $target = null, Vector $ignored_square = null, $ignore_color = false)
    {
        $moves = array();
        
        $position = $piece->getCoordinates();
        $sq = $position->addVector( $vector );

        try {
            /* loop continues until $square, end of border or non-ignored piece encounter */
            while( ($this->getSquareContent( $sq ) === null || $sq == $ignored_square) &&
                    $sq != $target ) {
                $moves[] = clone $sq;
                $sq->setCoordinates( $sq->addVector( $vector ) );
            }
            
            if( $target && $sq != $target ) {
                return null;
            }
            
            if( $ignore_color || !$this->getSquareContent($sq) || $this->getSquareContent($sq)->getIsWhite() != $piece->getIsWhite() ) {
                $moves[] = clone $sq;
            }
        } catch(OutOfBoardException $e) {
            if($target) {
                return null;
            }
        }
        
        return $moves;
    }
    
    public function canAssaultSquare(Piece $piece, Vector $square, Vector $ignored_square = null)
    {
        if( !$this->isSquareWithinBoard($square) ) {
            return false;
        }
        
        if( $piece instanceof \Polcode\ChessBundle\Entity\Pieces\Pawn ) {
            $kill_rule = new PawnKillRule( $this, null );
            
            if( !in_array( $square, $kill_rule->getKillSquares($piece) ) ) {
                return false;
            }
        } else {
            $moves = $this->getPieceMoveList($piece, $ignored_square, true);
            if( !in_array( $square, $moves ) ) {
                return false;
            }
        }
        
        return true;
    }
    
    public function updatePiecePosition(Vector $piece_coords, $file, $rank)
    {
        $this->board[$file . $rank] = $this->board[$piece_coords->getX() . $piece_coords->getY()];
        unset( $this->board[$piece_coords->getX() . $piece_coords->getY()] );
    }
    
    public function canMoveToSquareAlongVector(Piece $piece, Vector $square, Vector $vector, Piece $ignored_piece = null)
    {
        if( !$this->isSquareWithinBoard($square) ) {
            return false;
        }
                
        foreach( $piece->getMoveVectors() as $move_vector ) {
            if( $vector == $move_vector ) {
                $ignored_square = $ignored_piece ? $ignored_piece->getCoordinates() : null;
                
                if( $this->getMovesAlongVector($piece, $move_vector, $square, $ignored_square) ) {
                    return true;
                }
                
                return false;
            }
        }
        
        return false;   /* square doesn't lay on any of the piece's move vectors */
    }
    
    public function isSquareWithinBoard(Vector $square)
    {
        if($square->getX() < 1 || $square->getX() > 8 ||$square->getY() < 1 ||$square->getY() > 8) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @param Polcode\ChessBundle\Model\Vector
     * @throws OutOfBoardException
     */
    public function getSquareContent(Vector $square)
    {
        if( !$this->isSquareWithinBoard($square) ) {
            throw new OutOfBoardException();
        }
        
        if( isset( $this->board[$square->getX() . $square->getY()] ) ) {
            return $this->board[$square->getX() . $square->getY()];
        }
        
        return null;
    }
    
    public function getPieceMoveList(Piece $piece, Vector $ignored = null, $ignore_color = false)
    {
        $squares = array();
        
        if($piece->getMultimove()) {
            foreach($piece->getMoveVectors() as $vector) {
                $vector_moves = $this->getMovesAlongVector($piece, $vector, null, $ignored, $ignore_color);
                
                if( $vector_moves ) {
                    $squares = array_merge($squares, $vector_moves);
                }
            }
            
            return $squares;
        }
        
        foreach($piece->getMoveVectors() as $vector) {
            $square = $piece->getCoordinates()->addVector($vector);
            if( $this->isSquareWithinBoard($square) ) {
                $sq_content = $this->getSquareContent($square);
                
                if( !$sq_content || 
                        ( $sq_content->getIsWhite() != $piece->getIsWhite() &&
                        false == ($piece instanceof \Polcode\ChessBundle\Entity\Pieces\Pawn) ) ) {
                    $squares[] = clone $square;
                }
            }  
        }
        
        return $squares;
    }
}
