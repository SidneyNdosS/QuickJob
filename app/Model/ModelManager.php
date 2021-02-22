<?php

namespace App\Model;

use Nette;
/**
 * Base model class
 * @access public
 * @abstract
 * @author Luan Sidney <luansidneyseliga@gmail.com>
 * @@method void addIssue(string title, string description) Save all issues that were reported in a model
 * @@method array getIssues(array $values) Returns all issues reported
 */
abstract class ModelManager {
   /**
    * @access private
    */
    private array $issues = array();
    
   /**
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param string $title Short description of the issue
    * @param string $describtion Detail about the issue
    * @return void  
    */
    public function addIssue(string $title, string $describtion){
        $this->issues[$title] = $describtion;
    }
    
   /**
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @return Array All issue reported, the array key is the title of the issue and the value its description 
    */
    public function getIssues(){
        return $this->issues;
    }
}