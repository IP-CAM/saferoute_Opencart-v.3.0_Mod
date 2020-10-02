<?php

class DbModificator
{

    private $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }


    private function fieldOrderExists($field)
    {

        $sql = "SHOW COLUMNS FROM `".DB_PREFIX."order` LIKE '{$field}'";
        $result = $this->controller->db->query($sql);

        return $result->num_rows > 0;
    }



    public function install()
    {
        if (!$this->fieldOrderExists('saferoute_id'))
        {
            $sql = "ALTER TABLE  `".DB_PREFIX."order`  ADD  `saferoute_id` char(48) NOT NULL;";
            $this->controller->db->query($sql);
        }

        if (!$this->fieldOrderExists('in_saferoute_cabinet'))
        {
            $sql = "ALTER TABLE  `".DB_PREFIX."order`  ADD  `in_saferoute_cabinet` TINYINT(1) DEFAULT 0;";
            $this->controller->db->query($sql);
        }
    }


    public function uninstall()
    {
         if ($this->fieldOrderExists('saferoute_id'))
         {
         	$sql = "ALTER TABLE  `".DB_PREFIX."order`  DROP COLUMN  `saferoute_id`;";
         	$this->controller->db->query($sql);
         }

         if ($this->fieldOrderExists('in_saferoute_cabinet'))
         {
         	$sql = "ALTER TABLE  `".DB_PREFIX."order`  DROP COLUMN  `in_saferoute_cabinet`;";
         	$this->controller->db->query($sql);
         }
    }

}