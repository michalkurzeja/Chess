<?php

namespace Polcode\ChessBundle\Entity\Pieces;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Polcode\ChessBundle\Model\Vector;

/**
 * @ORM\Entity
 * @ORM\Table(name="pieces")
 */
class Pawn extends Piece
{
    /**
     * @ORM\Column(type="boolean")
     * 
     * @var boolean
     */
    protected $is_checking = false;
    
    /**
     * @ORM\Column(type="boolean")
     * 
     * @var boolean
     */
    protected $has_moved = false; 
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var Game
     */
    protected $game;

    /**
     * @var boolean
     */
    protected $is_white;

    /**
     * @var integer
     */
    protected $rank;

    /**
     * @var integer
     */
    protected $file;
    
    public function __construct($file, $rank, $is_white, $game)
    {
        parent::__construct($file, $rank, $is_white, $game);
        $this   ->setMultimove(false);
    }

    /**
     * @return array
     */
    public function getMoveVectors()
    { 
        return array( new Vector(0, $this->getIsWhite() ? 1 : -1) );
    }
    
    /**
     * Set is_checking
     *
     * @param boolean $isChecking
     * @return Pawn
     */
    public function setIsChecking($isChecking)
    {
        $this->is_checking = $isChecking;
    
        return $this;
    }

    /**
     * Get is_checking
     *
     * @return boolean 
     */
    public function getIsChecking()
    {
        return $this->is_checking;
    }

    /**
     * Set has_moved
     *
     * @param boolean $hasMoved
     * @return Pawn
     */
    public function setHasMoved($hasMoved)
    {
        $this->has_moved = $hasMoved;
    
        return $this;
    }

    /**
     * Get has_moved
     *
     * @return boolean 
     */
    public function getHasMoved()
    {
        return $this->has_moved;
    }

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
     * Set is_white
     *
     * @param boolean $isWhite
     * @return Pawn
     */
    public function setIsWhite($isWhite)
    {
        $this->is_white = $isWhite;
    
        return $this;
    }

    /**
     * Get is_white
     *
     * @return boolean 
     */
    public function getIsWhite()
    {
        return $this->is_white;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return Pawn
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    
        return $this;
    }

    /**
     * Get rank
     *
     * @return integer 
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set file
     *
     * @param integer $file
     * @return Pawn
     */
    public function setFile($file)
    {
        $this->file = $file;
    
        return $this;
    }

    /**
     * Get file
     *
     * @return integer 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set game
     *
     * @param \Polcode\ChessBundle\Entity\Game $game
     * @return Pawn
     */
    public function setGame(\Polcode\ChessBundle\Entity\Game $game = null)
    {
        $this->game = $game;
    
        return $this;
    }

    /**
     * Get game
     *
     * @return \Polcode\ChessBundle\Entity\Game 
     */
    public function getGame()
    {
        return $this->game;
    }
}