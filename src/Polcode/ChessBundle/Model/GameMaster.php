<?php

namespace Polcode\ChessBundle\Model;

use Polcode\ChessBundle\Model\Chessboard;
use Polcode\ChessBundle\Model\Rules;
use Polcode\ChessBundle\Entity\Pieces;
use Polcode\ChessBundle\Entity\Game;
use Polcode\ChessBundle\Exception\NotYourGameException;
use Polcode\ChessBundle\Exception\InvalidClassNameException;
use Polcode\ChessBundle\Exception\InvalidMoveException;
use Polcode\ChessBundle\Exception\GameFullException;

class GameMaster
{
    private $game;
    private $chessboard;
    private $em;
    private $game_utils;
    private $cache;
    private $rules = null;
    
    public function __construct($em, $game_utils, $cache)
    {
        $this->em = $em;
        $this->game_utils = $game_utils;
        $this->cache = $cache;
    }
    
    public function createNewGame($user)
    {
        $this->game = new Game();
        $this->game->setWhite($user);
        
        $this->em->persist($this->game);
        
        $this->generateStartingPositions($this->game);
        $this->em->flush();
        
        $game_id = $this->game->getId();
        
        $this->cache->save("chess.game_ended.{$game_id}", false);
        
        return $game_id;
    }
    
    public function joinGame($user, $game_id) {
        try {
            $this->game = $this->game_utils->getGameWithSlot($game_id, $this->em);
            $this->game->setPlayerOnEmptySlot($user);
        } catch(GameFullException $e) {
            throw $e;
        }
        
        $this->em->flush();
    }
    
    public function getGamePieces($user, $game_id)
    {
        try {
            $player_white = $this->loadGameState($user, $game_id);
        } catch(NotYourGameException $e) {
            throw $e;
        }        
        
        return $this->game_utils->getAllPiecesArray( $this->chessboard, $this, $player_white );       
    }
    
    public function loadGameState($user, $game_id)
    {
        try {
            $this->game = $this->game_utils->getUserGameById($user, $game_id);        
        } catch(NotYourGameException $e) {
            throw $e ;
        }
                
        $this->chessboard = $this->getChessboardFromDb($this->game);
        
        return $this->game->isPlayerWhite($user);
    }
    
    public function getChessboardFromDb($game)
    {
        return new Chessboard($game->getWhitePieces()->getValues(), $game->getBlackPieces()->getValues());
    }
    
    public function dev_starting_pos($game)
    {
        $whites = array(    $this->createPiece('Queen', 5, 1, true, $game),
                            $this->createPiece('Pawn', 1, 2, true, $game) );
        $blacks = array(    $this->createPiece('Queen', 5, 8, false, $game),
                            $this->createPiece('Pawn', 1, 7, false, $game) );
                            
        $this->chessboard = new Chessboard($whites, $blacks);
    }
    
    public function generateStartingPositions($game)
    {
        $whites = array( 
            $this->createPiece('King', 5, 1, true, $game),
            $this->createPiece('Queen', 4, 1, true, $game),
            $this->createPiece('Bishop', 3, 1, true, $game), $this->createPiece('Bishop', 6, 1, true, $game),
            $this->createPiece('Knight', 2, 1, true, $game), $this->createPiece('Knight', 7, 1, true, $game),
            $this->createPiece('Rook', 1, 1, true, $game), $this->createPiece('Rook', 8, 1, true, $game)   
        );

        $blacks = array( 
            $this->createPiece('King', 5, 8, false, $game),
            $this->createPiece('Queen', 4, 8, false, $game),
            $this->createPiece('Bishop', 3, 8, false, $game), $this->createPiece('Bishop', 6, 8, false, $game),
            $this->createPiece('Knight', 2, 8, false, $game), $this->createPiece('Knight', 7, 8, false, $game),
            $this->createPiece('Rook', 1, 8, false, $game), $this->createPiece('Rook', 8, 8, false, $game)   
        );

        for($i=1; $i<=8; $i++) {
            $whites[] =  $this->createPiece('Pawn', $i, 2, true, $game);
            $blacks[] =  $this->createPiece('Pawn', $i, 7, false, $game);
        }

        $this->chessboard = new Chessboard($whites, $blacks);
    }
    
    public function createPiece($class, $file, $rank, $is_white, $game)
    {
        $full_class = "Polcode\\ChessBundle\\Entity\\Pieces\\$class";
        
        try {
            $piece = new $full_class($file, $rank, $is_white, $game);
        } catch(InvalidClassNameException $e) {} /* do something? */
        
        $this->em->persist($piece);
        
        return $piece;
    }
    
    public function isMyTurn($user)
    {
        if( $this->game->getWhiteTurn() === $this->game->isPlayerWhite($user) ) {
            return true;
        }
        
        return false;
    }
    
    public function getEnPassantSquareArray()
    {
        $sq = $this->chessboard->getEnPassantSquare( $this->game->getEnPassable() );
        return $sq ? $sq->toArray() : null;
    }
    
    public function getEnPassantPieceArray()
    {
        return $this->game_utils->getShortPieceArray( $this->game->getEnPassable() );
    }
    
    public function getUpdate($user, $game_id, $move_count) {
        $update = array();
        
        try {
            $player_white = $this->loadGameState($user, $game_id);
        } catch(NotYourGameException $e) {
            throw $e;
        }
        
        if( $this->getMoveCount() > $move_count || $this->getMoveCount() == 0 ) {
            /* get update on player's turn */
            $update['turn'] = $this->isMyTurn($user);
            
            $update['old_move_count'] = $move_count;
            /* get current move count */
            $update['move_count'] = $this->getMoveCount();
                        
            /* get last moved piece */
            if( $this->hasSwapped() ) {
                $swapped_piece = $this->game->getSwappedPiece();
                $update['last_moved'] = array(
                    'id' => $this->game->getSwappedOldId(),
                    'file' => $swapped_piece->getFile(),
                    'rank' => $swapped_piece->getRank()
                );
                
                $update['swap_class'] = $swapped_piece->getPieceName();
            } else {
                $update['last_moved'] = $this->game_utils->getShortPieceArray( $this->game->getLastMoved() );
            }
            
            /* square of piece captured in last turn or null */
            $update['captured'] = $this->game_utils->getShortPieceArray( $this->game->getLastCaptured() );
            
            $update['en_passant_square'] = $this->getEnPassantSquareArray();
            $update['en_passant_piece'] = $this->getEnPassantPieceArray();
            $update['castle'] = array(  'my' => $this->getCastleDataAsArray($player_white),
                                        'opponent' => $this->getCastleDataAsArray(!$player_white)
            );
            
            /* get update moves of pieces */
            $update['moves'] = $this->game_utils->getAllPiecesMovesArray($this->chessboard, $this, $player_white);
            
            if( !$this->getMoveCount() ) {
                $update['white_name'] = $this->getPlayerName(true);
                $update['black_name'] = $this->getPlayerName(false);
            }
            
            if( !$this->getMoveCount() && $this->hasGameStarted() ) {
                $update['started'] = true;
            }
            
            if( $this->game->getEnded() ) {
                $update['winner'] = $this->getWinner();
            }
        }

        return $update;
    }
    
    public function getEndgameUpdate($user, $game_id)
    {
        $update = array();
        
        try {
            $player_white = $this->loadGameState($user, $game_id);
        } catch(NotYourGameException $e) {
            throw $e;
        }
        
        $update['winner'] = $this->getWinner();
        
        return $update;
    }
    
    public function getRules()
    {
        if( is_null($this->rules) ) {
            $this->loadRules();
        }
        
        return $this->rules;
        
    } 
    
    public function loadRules()
    {
        $this->rules = array(
            new Rules\DoubleMoveRule( $this->chessboard, $this->game ),
            new Rules\EnPassantRule( $this->chessboard, $this->game ),
            new Rules\PawnKillRule( $this->chessboard, $this->game ),
            new Rules\CastleRule( $this->chessboard, $this->game ),
            new Rules\CheckRule( $this->chessboard, $this->game )
        );
    }
    
    public function movePiece($user, $game_id, $data) {
         try {
            $player_white = $this->loadGameState($user, $game_id);
        } catch(NotYourGameException $e) {
            throw $e;
        }
        
        /* set no-ones turn for the time of calculations */
        $this->game->setWhiteTurn(null);
        $this->em->flush();
        
        $piece = $this->chessboard->findPieceById($data->piece->id);
        
        if( !$this->game_utils->verifyPiece($piece, $data->piece, $player_white) ) {
            throw new InvalidMoveException();
        }
        
        if( !$this->isMoveLegal($piece, $data->coords) ) {
            throw new InvalidMoveException();
        }
        
        $this->capture($piece, new Vector($data->coords->file, $data->coords->rank), $this->game->getEnPassable());
        
        if( $piece instanceof Pieces\King ) {
            $this->castle($piece, new Vector($data->coords->file, $data->coords->rank));
        }
        
        if( $piece instanceof Pieces\Pawn ) {        
            $difference = $piece->getCoordinates()->getY() - $data->coords->rank;
            
            if( $difference == 2 || $difference == -2 ) {
                $this->game->setEnPassable( $piece );
            }
        } else {
            $this->game->setEnPassable( null );
        }
        
        if( isset($data->new_class) ) {
            $piece = $this->swapPiece($piece, $data->new_class, $data->coords->file, $data->coords->rank);
        } else {
            $this->updatePieceCoordinates($piece, $data->coords->file, $data->coords->rank);        
        }
                
        $this->afterMove($game_id, $piece, $player_white);

        $this->checkRulesAfterMove($piece);
        
        $this->em->flush();
    }

    public function swapPiece(Pieces\Piece $piece, $new_class, $file, $rank) {
        $color = $piece->getIsWhite();
        $old_id = $piece->getId();
        
        $this->chessboard->removePiece($piece);
        $this->em->remove($piece);
        
        $new_piece = $this->createPiece($new_class, $file, $rank, $color, $this->game);
        $this->chessboard->addPiece($new_piece);
        $this->chessboard->addPieceToWhitesOrBlacks($new_piece);
        
        $this->setSwapData($old_id, $new_piece);
        
        return $new_piece;
    }

    public function setSwapData($old_id, $new_piece)
    {
        $this->game->setSwappedPiece( $new_piece );
        $this->game->setSwappedOldId( $old_id );
    }

    public function clearSwap()
    {
        $this->game->setSwappedPiece( null );
        $this->game->setSwappedOldId( 0 );
    }

    public function hasSwapped()
    {
        return $this->game->getSwappedOldId() ? true : false;
    }

    public function updatePieceCoordinates(Pieces\Piece $piece, $file, $rank)
    {
        $this->chessboard->updatePiecePosition($piece->getCoordinates(), $file, $rank);
        $piece->setCoordinates( new Vector( $file, $rank ) ); /* set new coordinates for a piece */
        $piece->setHasMoved( true ); /* mark the piece as already moved */
        $this->clearSwap();
    }

    public function getCapturePiece(Pieces\Piece $piece, Vector $new_coords)
    {
        $capture_piece = $this->chessboard->getSquareContent($new_coords);
        
        if( $capture_piece && $capture_piece->getIsWhite() != $piece->getIsWhite() ) {
            return $capture_piece;
        }
        
        return null;
    }

    public function castle(Pieces\Piece $king, Vector $new_coords) {
        $castle_data = $this->chessboard->getCastleData($king->getIsWhite());
        
        if(!$castle_data) {
            return;
        }
        
        foreach($castle_data as $data) {
            if( $new_coords == $data['square'] ) {
                $data['rook']->setCoordinates($data['rook_square']);
                $data['rook']->setHasMoved( true );
                return;
            }
        }
    }

    public function capture(Pieces\Piece $piece, Vector $new_coords, $en_passant_piece)
    {
        $capture_piece = null;
        
        if($piece instanceof Pieces\Pawn && $en_passant_piece) {
            $en_passant_square = $this->chessboard->getEnPassantSquare($en_passant_piece);
            
            if($new_coords == $en_passant_square) {
                $capture_piece = $this->getCapturePiece($piece, $en_passant_piece->getCoordinates());
            }
        } else {
            $capture_piece = $this->getCapturePiece($piece, $new_coords);
        }

        if( $capture_piece ) {
            $this->game->setLastCaptured($capture_piece);
            $this->removePiece($capture_piece);
        } else {
            $this->game->setLastCaptured( null );
        }
    }

    public function removePiece($piece)
    {
        $this->chessboard->removePiece($piece);
        $piece->setIsCaptured( true );
    }

    public function afterMove($game_id, &$piece, $player_white)
    {
        
        $this->game->setLastMoved($piece); /* sets the last moved piece */
        $this->game->incrementMoveCount(); /* increment move count */
        $this->game->setWhiteTurn( !$player_white ); /* if player is white, sets turn to black (and vice versa) */
        $this->checkMaterial();
        
        $this->cache->save( "chess.move_count.{$game_id}", $this->game->getMoveCount() ); /* save move count in cache */
        
        $this->em->flush();
    }
            
    public function checkMaterial()
    {
        if( !$this->canCheckmateWithMaterial( true ) && !$this->canCheckmateWithMaterial( false ) ) {
            $this->endCurrentGame();
        }
    }
        
    public function canCheckmateWithMaterial($white)
    {
        $pieces = $this->chessboard->getPieces( $this->chessboard->getColor( $white ) );
        
        $piece_count = count($pieces);
        
        if( $piece_count == 1 ) {
            return false;
        }
        
        if( $piece_count > 2 ) {
            return true;
        }
        
        foreach( $pieces as $piece ) {
            /* piece #1: king */
            /* if piece #2 is a knight - player cannot checkmate opponent */
            if( $piece instanceof Pieces\Knight ) {
                return false;
            }
        }
        
        return true;
    }
            
    public function isMoveLegal($piece, $coords)
    {
        $legal_moves = $this->getValidMoves($piece);
        
        foreach($legal_moves as $square) {
            if( $square->getX() == $coords->file && $square->getY() == $coords->rank ) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getValidMoves($piece)
    {       
        $squares = $this->chessboard->getPieceMoveList($piece);
        
        $args = array( 'piece' => $piece, 'squares' => &$squares );
        
        /* modifies $squares */
        $this->runRuleFunction( 'checkRule', $args );
               
        return $squares;
    }
    
    public function endCurrentGame()
    {
        $this->game->setEnded( true );
        $this->game->setEndTime( new \DateTime() );
        
        $game_id = $this->game->getId();
           
        $this->cache->save("chess.game_ended.{$game_id}", $this->getWinner());
        
        $this->em->flush();        
    }
    
    public function checkRulesAfterMove(&$piece)
    {
        $args = array( 'piece' => $piece );
        
        $this->runRuleFunction('checkRuleAfterMove', $args);
    }
    
    public function runRuleFunction($func, &$args)
    {
        foreach( $this->getRules() as $rule ) {
            $rule->$func($args);
        }
    }
    
    /* to be removed */
    public function getAllValidMoves()
    {
        $pieces = $this->chessboard->getPieces();
        
        $positions = '';
        
        foreach($pieces as &$piece) {
            $positions .= "{$piece}".PHP_EOL;
            $squares = $this->chessboard->getPieceMoveList($piece);
              foreach($squares as &$square) {
                  $positions .= "\t{$square}" . PHP_EOL;
              }
        }
        
        return $positions;
    }
        
    public function setGame($game)
    {
        $this->game = $game;
        
        return $this;
    }
    
    public function getGameId()
    {
        return $this->game->getId();
    }
    
    public function getMoveCount()
    {
        return $this->game->getMoveCount();
    }
    
    public function hasGameStarted()
    {
        if( $this->game->getWhite() && $this->game->getBlack() ) {
            return true;
        }
        
        return false;
    }
    
    public function hasGameEnded()
    {
        return $this->game->getEnded();
    }
        
    public function getWinner()
    {
        if( $this->chessboard->getCheckingPieces( true ) ) {
            return $this->game->getWhite()->getUsername();
        }        
        
        if( $this->chessboard->getCheckingPieces( false ) ) {
            return $this->game->getBlack()->getUsername();
        }        
        
        return null;
    }
    
    public function getPlayerName($white)
    {
        if($white) {
            $player = $this->game->getWhite();
            return $player ? $player->getUsername() : null;
        }
        
        $player = $this->game->getBlack();
        return $player ? $player->getUsername() : null;
    }
    
    public function getCastleDataAsArray($white)
    {
        return $this->chessboard->getCastleDataAsArray($white);
    }
}
