<?php
/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name ObjectFactory
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Patterns
 * @version 1.0
 * @category Patterns
 */
namespace Catappa\Patterns;
class ObjectFactory {
	public static function getNewInstance($className, $param1 = null,$param2=null) {
		if (class_exists ( $className )) {
			if ($param1 == NULL)
				return new $className();
			else {
				/*$obj = new ReflectionClass ( $className );
				return $obj->newInstanceArgs ( $params );*/
            if ($param2 == NULL)
                    return new $className ($param1);
			return new $className ($param1,$param2);
			}
		}
		 return null;
		//throw new Exception ( "Class [ $className ] not found..." );
	}
}
?>
