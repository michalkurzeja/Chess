<?php

namespace Polcode\ChessBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="chess_user")
 * @UniqueEntity(fields="username", message="Username already taken")
 * @UniqueEntity(fields="email", message="Email already taken")
 */
class User extends BaseUser
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
     * @ORM\OneToMany(targetEntity="Game", mappedBy="white")
     */
    protected $white_games;
    
    /**
     * @ORM\OneToMany(targetEntity="Game", mappedBy="black")
     */
    protected $black_games;
	
	public function __construct()
    {
        parent::__construct();
        $this->white_games = new ArrayCollection();
        $this->black_games = new ArrayCollection();
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
     * Add white_games
     *
     * @param \Polcode\ChessBundle\Entity\Game $whiteGames
     * @return User
     */
    public function addWhiteGame(\Polcode\ChessBundle\Entity\Game $whiteGames)
    {
        $this->white_games[] = $whiteGames;
    
        return $this;
    }

    /**
     * Remove white_games
     *
     * @param \Polcode\ChessBundle\Entity\Game $whiteGames
     */
    public function removeWhiteGame(\Polcode\ChessBundle\Entity\Game $whiteGames)
    {
        $this->white_games->removeElement($whiteGames);
    }

    /**
     * Get white_games
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getWhiteGames()
    {
        return $this->white_games->getValues();
    }

    /**
     * Add black_games
     *
     * @param \Polcode\ChessBundle\Entity\Game $blackGames
     * @return User
     */
    public function addBlackGame(\Polcode\ChessBundle\Entity\Game $blackGames)
    {
        $this->black_games[] = $blackGames;
    
        return $this;
    }

    /**
     * Remove black_games
     *
     * @param \Polcode\ChessBundle\Entity\Game $blackGames
     */
    public function removeBlackGame(\Polcode\ChessBundle\Entity\Game $blackGames)
    {
        $this->black_games->removeElement($blackGames);
    }

    /**
     * Get black_games
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBlackGames()
    {
        return $this->black_games->getValues();
    }
    
    public function getAllGames()
    {
        return array_merge($this->getWhiteGames(), $this->getBlackGames());
    }
}