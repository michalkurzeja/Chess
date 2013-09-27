<?php

namespace Polcode\ChessBundle\Entity\Pieces;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Polcode\ChessBundle\Model\Vector;

/**
 * @ORM\Entity
 * @ORM\Table(name="pieces")
 * @UniqueEntity(fields="id")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="piece_type", type="string")
 * @ORM\DiscriminatorMap({"piece" = "Piece", "pawn" = "Pawn", "bishop"="Bishop", "knight"="Knight", "rook"="Rook", "queen"="Queen", "king"="King"})
 */
abstract class Piece
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
     * @ORM\ManyToOne(targetEntity="Polcode\ChessBundle\Entity\Game", inversedBy="pieces")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="id")
     * 
     * @var Game
     */
    protected $game;
    
    /**
     * @ORM\Column(type="boolean")
     * 
     * @var boolean
     */
    protected $is_white;
    
    /**
     * @ORM\Column(type="integer")
     * 
     * @var int
     */
    protected $rank;
    
    /**
     * @ORM\Column(type="integer")
     * 
     * @var int
     */
    protected $file;
    
    /**
     * @ORM\Column(type="boolean")
     * 
     * @var boolean
     */
    protected $multimove;
    
    /**
     * @ORM\Column(type="boolean")
     *
     * @var boolean
     */
    protected $is_captured = false;

    public function __construct($file, $rank, $is_white, $game)
    {
        $this   ->setRank($rank)
                ->setFile($file)
                ->setIsWhite($is_white)
                ->setGame($game);
    }

    public function toShortArray()
    {
        return array(
            'id' => $this->getId(),
            'file' => $this->getFile(),
            'rank' => $this->getRank()
        );
    }

    public function setCoordinates(Vector $vector)
    {
        $this->setRank($vector->getY());
        $this->setFile($vector->getX());
    }
    
    public function getCoordinates()
    {
        return new Vector( $this->getFile(), $this->getRank() );
    }
    
    /**
     * @return array
     */
    abstract public function getMoveVectors();
    
    /**
     * @return boolean
     */
    public function getMultimove()
    {
        return $this->multimove;
    }
    
    /**
     * @param boolean
     */
    public function setMultimove($multimove)
    {
        $this->multimove = $multimove;
        
        return $this;
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
     * @return Piece
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
     * Set has_moved
     *
     * @param boolean $hasMoved
     * @return Piece
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
     * Set rank
     *
     * @param integer $rank
     * @return Piece
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
     * @return Piece
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
    
    public function __toString()
    {
        $name = get_class($this);
        return ($this->getIsWhite() ? 'White ' : 'Black ') . substr($name, strrpos($name, '\\')+1) . $this->getCoordinates();
    }
    
    public function getPieceName()
    {
        $name = get_class($this);
        return substr($name, strrpos($name, '\\')+1);
    }

    /**
     * Set game
     *
     * @param \Polcode\ChessBundle\Entity\Game $game
     * @return Piece
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
     * Set is_captured
     *
     * @param boolean $isCaptured
     * @return Piece
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