<?php

namespace Polcode\ChessBundle\Model;

class Vector
{
    private $x;
    
    private $y;
    
    public function __construct($x, $y)
    {
        $this->setX($x);
        $this->setY($y);
    }
    
    public function addVector(Vector $vector)
    {
        return new Vector($this->getX() + $vector->getX(), $this->getY() + $vector->getY());
    }
    
    public function multiplyByScalar($scal)
    {
        return new Vector( $this->getX() * $scal, $this->getY() * $scal );
    }
    
    public function setCoordinates(Vector $vector) {
        $this   ->setX( $vector->getX() )
                ->setY( $vector->getY() );
    }
    
    public function getCoordinates()
    {
        return array( $this->getX(), $this->getY() );
    }
    
    public function setX($val)
    {
        $this->x = $val;
        
        return $this;
    }
    
    public function setY($val)
    {
        $this->y = $val;
        
        return $this;
    }
    
    public function getX()
    {
        return $this->x;
    }
    
    public function getY()
    {
        return $this->y;
    }
    
    public function toArray()
    {
        return array( 'x' => $this->getX(), 'y' => $this->getY() );
    }
    
    public function __toString()
    {
        return '[' . $this->getX() . ',' . $this->getY() . ']';
    }
}
