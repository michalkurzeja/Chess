<?php

namespace Polcode\ChessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Polcode\ChessBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Polcode\ChessBundle\Entity\Pieces;
use Polcode\ChessBundle\Exception\GameFullException;

/**
 * @ORM\Entity
 * @ORM\Table(name="games")
 * @UniqueEntity(fields="id")
 */
class Game
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * @var int
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="white_games")
     * @ORM\JoinColumn(name="white_id", referencedColumnName="id")
     * 
     * @var User
     */
    protected $white = null;
    
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="black_games")
     * @ORM\JoinColumn(name="black_id", referencedColumnName="id")
     * 
     * @var User
     */
    protected $black = null;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     * 
     * @var boolean
     */
    protected $white_turn = null;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * 
     * @var datetime
     */
    protected $start_time;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * 
     * @var datetime
     */
    protected $end_time;

    /**
     * @ORM\OneToMany(targetEntity="Polcode\ChessBundle\Entity\Pieces\Piece", mappedBy="game", cascade={"remove"})
     * 
     * @var Piece
     */
    protected $pieces;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $move_count = 0;
    
    /**
     * @ORM\OneToOne(targetEntity="Polcode\ChessBundle\Entity\Pieces\Piece")
     * @ORM\JoinColumn(name="last_moved_id", referencedColumnName="id")
     */
    protected $last_moved = null;

    /**
     * @ORM\OneToOne(targetEntity="Polcode\ChessBundle\Entity\Pieces\Piece")
     * @ORM\JoinColumn(name="last_captured_id", referencedColumnName="id")
     */
    protected $last_captured = null;
    
    /**
     * @ORM\OneToOne(targetEntity="Polcode\ChessBundle\Entity\Pieces\Piece")
     * @ORM\JoinColumn(name="en_passable_id", referencedColumnName="id")
     */
    protected $en_passable = null;
    
    /**
     * @ORM\Column(type="boolean")
     * 
     *  @var boolean
     */
    protected $ended = false;
    
    /**
     * @ORM\Column(type="integer")
     * 
     *  @var int
     */
    protected $swapped_old_id = null;
    
    /**
     * @ORM\OneToOne(targetEntity="Polcode\ChessBundle\Entity\Pieces\Piece")
     * @ORM\JoinColumn(name="swapped_piece_id", referencedColumnName="id")
     */
    protected $swapped_piece = null;
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set white_turn
     *
     * @param boolean $whiteTurn
     * @return Game
     */
    public function setWhiteTurn($whiteTurn)
    {
        $this->white_turn = $whiteTurn;
    
        return $this;
    }

    /**
     * Get white_turn
     *
     * @return boolean 
     */
    public function getWhiteTurn()
    {
        return $this->white_turn;
    }

    /**
     * Set start_time
     *
     * @param \DateTime $startTime
     * @return Game
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;
    
        return $this;
    }

    /**
     * Get start_time
     *
     * @return \DateTime 
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set end_time
     *
     * @param \DateTime $endTime
     * @return Game
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;
    
        return $this;
    }

    /**
     * Get end_time
     *
     * @return \DateTime 
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set white
     *
     * @param \Polcode\ChessBundle\Entity\User $white
     * @return Game
     */
    public function setWhite(\Polcode\ChessBundle\Entity\User $white = null)
    {
        $this->white = $white;
    
        return $this;
    }

    /**
     * Get white
     *
     * @return \Polcode\ChessBundle\Entity\User 
     */
    public function getWhite()
    {
        return $this->white;
    }

    /**
     * Set black
     *
     * @param \Polcode\ChessBundle\Entity\User $black
     * @return Game
     */
    public function setBlack(\Polcode\ChessBundle\Entity\User $black = null)
    {
        $this->black = $black;
    
        return $this;
    }

    /**
     * Get black
     *
     * @return \Polcode\ChessBundle\Entity\User 
     */
    public function getBlack()
    {
        return $this->black;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pieces = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add pieces
     *
     * @param \Polcode\ChessBundle\Entity\Pieces\Piece $pieces
     * @return Game
     */
    public function addPiece(\Polcode\ChessBundle\Entity\Pieces\Piece $pieces)
    {
        $this->pieces[] = $pieces;
    
        return $this;
    }

    /**
     * Remove pieces
     *
     * @param \Polcode\ChessBundle\Entity\Pieces\Piece $pieces
     */
    public function removePiece(\Polcode\ChessBundle\Entity\Pieces\Piece $pieces)
    {
        $this->pieces->removeElement($pieces);
    }

    /**
     * Get pieces
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPieces()
    {
        return $this->pieces;
    }
    
    public function getWhitePieces()
    {
        return $this->pieces->filter(function($elem) {
             return $elem->getIsWhite() && !$elem->getIsCaptured();
        });
    }
    
    public function getBlackPieces()
    {
        return $this->pieces->filter(function($elem) {
             return !$elem->getIsWhite() && !$elem->getIsCaptured();
        });
    }
    
    public function isPlayerWhite(User $user) {
        if( $this->getWhite()->getId() == $user->getId() ) {
            return true;
        }
        
        return false;
    }
    
    public function hasEmptySlot()
    {
        if(!$this->getWhite() || !$this->getBlack()) {
            return true;
        }
        
        return false;
    }
    
    public function setPlayerOnEmptySlot(User $user)
    {
        if( !$this->getWhite() && !$this->getBlack() ) {
            throw new GameFullException();
        }
        
        if(!$this->getWhite()) {
            $this->setWhite($user);
        }

        if(!$this->getBlack()) {
            $this->setBlack($user);
        }
        
        $this->setStartTime( new \DateTime() );
        $this->setWhiteTurn( true );
    }

    /**
     * Set move_count
     *
     * @param integer $moveCount
     * @return Game
     */
    public function setMoveCount($moveCount)
    {
        $this->move_count = $moveCount;
    
        return $this;
    }

    public function incrementMoveCount()
    {
        $this->setMoveCount( $this->getMoveCount() + 1 );
        
        return $this;
    }

    /**
     * Get move_count
     *
     * @return integer 
     */
    public function getMoveCount()
    {
        return $this->move_count;
    }

    /**
     * Set last_moved
     *
     * @param \Polcode\ChessBundle\Entity\Pieces\Piece $lastMoved
     * @return Game
     */
    public function setLastMoved(\Polcode\ChessBundle\Entity\Pieces\Piece $lastMoved = null)
    {
        $this->last_moved = $lastMoved;
    
        return $this;
    }

    /**
     * Get last_moved
     *
     * @return \Polcode\ChessBundle\Entity\Pieces\Piece 
     */
    public function getLastMoved()
    {
        return $this->last_moved;
    }

    /**
     * Set en_passable
     *
     * @param \Polcode\ChessBundle\Entity\Pieces\Piece $enPassable
     * @return Game
     */
    public function setEnPassable(\Polcode\ChessBundle\Entity\Pieces\Piece $enPassable = null)
    {
        $this->en_passable = $enPassable;
    
        return $this;
    }

    /**
     * Get en_passable
     *
     * @return \Polcode\ChessBundle\Entity\Pieces\Piece 
     */
    public function getEnPassable()
    {
        return $this->en_passable;
    }

    /**
     * Set last_captured
     *
     * @param \Polcode\ChessBundle\Entity\Pieces\Piece $lastCaptured
     * @return Game
     */
    public function setLastCaptured(\Polcode\ChessBundle\Entity\Pieces\Piece $lastCaptured = null)
    {
        $this->last_captured = $lastCaptured;
    
        return $this;
    }

    /**
     * Get last_captured
     *
     * @return \Polcode\ChessBundle\Entity\Pieces\Piece 
     */
    public function getLastCaptured()
    {
        return $this->last_captured;
    }

    /**
     * Set ended
     *
     * @param boolean $ended
     * @return Game
     */
    public function setEnded($ended)
    {
        $this->ended = $ended;
    
        return $this;
    }

    /**
     * Get ended
     *
     * @return boolean 
     */
    public function getEnded()
    {
        return $this->ended;
    }

    /**
     * Set swapped_old_id
     *
     * @param integer $swappedOldId
     * @return Game
     */
    public function setSwappedOldId($swappedOldId)
    {
        $this->swapped_old_id = $swappedOldId;
    
        return $this;
    }

    /**
     * Get swapped_old_id
     *
     * @return integer 
     */
    public function getSwappedOldId()
    {
        return $this->swapped_old_id;
    }

    /**
     * Set swapped_piece
     *
     * @param \Polcode\ChessBundle\Entity\Pieces\Piece $swappedPiece
     * @return Game
     */
    public function setSwappedPiece(\Polcode\ChessBundle\Entity\Pieces\Piece $swappedPiece = null)
    {
        $this->swapped_piece = $swappedPiece;
    
        return $this;
    }

    /**
     * Get swapped_piece
     *
     * @return \Polcode\ChessBundle\Entity\Pieces\Piece 
     */
    public function getSwappedPiece()
    {
        return $this->swapped_piece;
    }
}