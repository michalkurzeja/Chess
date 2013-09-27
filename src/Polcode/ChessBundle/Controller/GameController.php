<?php

namespace Polcode\ChessBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Polcode\ChessBundle\Exception\NotYourGameException;
use Polcode\ChessBundle\Exception\InvalidMoveException;
use Polcode\ChessBundle\Exception\InvalidFormatException;

class GameController extends Controller
{
    public function createAction()
    {
        $gm = $this->get('GameMaster');
        
        $game_id = $gm->createNewGame($this->getUser());
        
        return $this->redirect( $this->generateUrl('display', array('game_id' => $game_id)), 301 );
    }
    
    public function joinAction($game_id)
    {
        $gm = $this->get('GameMaster');
        
        $gm->joinGame($this->getUser(), $game_id);
        
        return $this->redirect( $this->generateUrl('display', array('game_id' => $game_id)), 301 );
    }
    
    public function displayAction($game_id)
    {
        $gm = $this->get('GameMaster');
        $user = $this->getUser();
        
        try {
            $player_white = $gm->loadGameState( $user, $game_id );
        } catch(NotYourGameException $e) {
            return new Response('You\'re not allowed to view this game!');
        }
        
        $gm->checkMaterial();
        
        $color = $player_white ? 1 : 0;
        $my_turn = $gm->isMyTurn( $user ) ? 1 : 0;
        
        $move_count = $gm->getMoveCount();
        
        $ep_square = json_encode($gm->getEnPassantSquareArray());
        $ep_piece = json_encode($gm->getEnPassantPieceArray());
        
        $castle_data = array(  'my' => $gm->getCastleDataAsArray($player_white),
                               'opponent' => $gm->getCastleDataAsArray(!$player_white)
        );
        
        $castle = json_encode($castle_data);
        
        $started = $gm->hasGameStarted() ? 1: 0;
        
        $ended = $gm->hasGameEnded() ? 1 : 0;
        $winner = '';
        
        if($ended) {
            $winner = $gm->getWinner();
        } 
        
        $white = $gm->getPlayerName( true );
        $black = $gm->getPlayerName( false );
        
        $cont = $gm->getAllValidMoves();
        return $this->render('PolcodeChessBundle:Game:game.html.twig', array(   'game_id' => $game_id, 
                                                                                'move_count' => $move_count,
                                                                                'my_turn' => $my_turn,
                                                                                'color' => $color,
                                                                                'white' => $white,
                                                                                'black' => $black,
                                                                                'content' => $cont,
                                                                                'ep_square' => $ep_square,
                                                                                'ep_piece' => $ep_piece,
                                                                                'castle' => $castle,
                                                                                'started' => $started,
                                                                                'ended' => $ended,
                                                                                'winner' => $winner ));
    }
    
    public function updateAction($game_id)
    {
        $cache = $this->get('winzou_cache.memcache');   

        if($cache->contains( "chess.game_ended.{$game_id}" ) && ($winner = $cache->fetch( "chess.game_ended.{$game_id}" )) ) {
            $gm = $this->get('GameMaster');
            $update = array('winner' => $winner); 
            return new JsonResponse($update);
        } else {
            $cache->save("chess.game_ended.{$game_id}", false);
        }
        
        if( $cache->contains( "chess.move_count.{$game_id}" ) ) {
            $game_move_count = $cache->fetch( "chess.move_count.{$game_id}" );
        } else {
            $game_move_count = $this->getDoctrine()->getManager()->getRepository('PolcodeChessBundle:Game')->findOneById($game_id)->getMoveCount();        
            $cache->save( "chess.move_count.{$game_id}", $game_move_count );
        }
                
        try {
            $data = json_decode($this->get('request')->getContent());
            if( $game_move_count > $data->move_count || $game_move_count == 0) {
                $gm = $this->get('GameMaster');
                $update = $gm->getUpdate($this->getUser(), $game_id, $data->move_count);
                return new JsonResponse($update);
            }
        } catch(InvalidFormatException $e) {
            return new Response('Wrong format!', 404);
        } catch(NotYourGameException $e) {
            return new Response('Not your game!', 404);
        }

        return new Response();        
    }

    public function moveAction($game_id)
    {        
        $gm = $this->get('GameMaster');
        
        try {            
            $data = json_decode($this->get('request')->getContent());
            $gm->movePiece($this->getUser(), $game_id, $data);
        } catch(InvalidFormatException $e) {
            return new Response('Wrong format!', 404);
        } catch(NotYourGameException $e) {
            return new Response('Not your game!', 404);
        } catch(InvalidMoveException $e) {
            return new Response('Invalid move!', 404);
        }

        return new Response('Move accepted!');
    }

    public function getPiecesAction($game_id)
    {
        $gm = $this->get('GameMaster');

        try {
            $pieces = $gm->getGamePieces( $this->getuser(), $game_id );
            return new JsonResponse($pieces);
        } catch(NotYourGameException $e) {
            return new Response('Not your game!', 404);
        }
    }  
    
    public function modalAction()
    {
        return $this->render('PolcodeChessBundle:Chessboard:modal.html.twig', array());
    }  
}
