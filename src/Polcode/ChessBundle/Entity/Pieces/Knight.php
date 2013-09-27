<?php

namespace Polcode\ChessBundle\Entity\Pieces;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Polcode\ChessBundle\Model\Vector;

/**
 * @ORM\Entity
 * @ORM\Table(name="pieces")
 */
class Knight extends Piece
{
    /**
     * @ORM\Column(type="boolean")
     * 
     * @var boolean
     */
    protected $is_checking = false;
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
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
        return array(   new Vector(2, 1), new Vector(1, 2), new Vector(-1, 2), new Vector(-2, 1),
                        new Vector(-2, -1), new Vector(-1, -2), new Vector(1, -2), new Vector(2, -1) );
    }

    /**
     * Set is_checking
     *
     * @param boolean $isChecking
     * @return Knight
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
     * @return Knight
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
     * @return Knight
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
     * @return Knight
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
     * @return Knight
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
    /**
     * @var boolean
     */
    protected $multimove;

    /**
     * @var boolean
     */
    protected $is_captured = false;


    /**
     * Set multimove
     *
     * @param boolean $multimove
     * @return Knight
     */
    public function setMultimove($multimove)
    {
        $this->multimove = $multimove;
    
        return $this;
    }

    /**
     * Get multimove
     *
     * @return boolean 
     */
    public function getMultimove()
    {
        return $this->multimove;
    }

    /**
     * Set is_captured
     *
     * @param boolean $isCaptured
     * @return Knight
     */
    public function setIsCaptured($isCaptured)
    {
        $this->is_captured = $isCaptured;
    
        return $this;
    }

    /**
     * Get is_captured
     *
     * @return boolean 
     */
    public function getIsCaptured()
    {
        return $this->is_captured;
    }
}