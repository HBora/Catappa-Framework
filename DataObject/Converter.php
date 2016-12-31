<?php
/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name Converter
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 1.0
 * @category Catappa ORM
 */
namespace Catappa\DataObject;

Class Converter
{
    private $manager ;
    //public function __construct(Manager $m) {
   //     $this->manager = $m;
  //  }
    function entityListToXml(ClassMap $classmap ,Array $list) {

        // for($i =0,$n= count($list);$i<$n;$i++){

        $iter = new ArrayIterator($list);
        while($iter->valid())
        {
            $cur=    $iter->current();
            if(is_object($cur))
            {
                $name= get_class($cur);
                $collumns = $classmap->getClassColumns($name);
                echo "<$name>\n";
                foreach($collumns as $col){
                    $property = $col["property"];
                    echo "<$property>".$cur->$property."</$property>\n";
                }
                echo "</$name>\n";
                $iter->next();

            }
        }

    }

    private $is=array();
    function entityToXML($entity) {
        $map=  ClassMap::getInstance();//new ClassMap();
        $class_name = get_class($entity);
        if(isset($this->is[$class_name]))
        return;
        $this->is[$class_name]=1;
        $columns= $map->getClassColumns($class_name);
         $class_name = basename($class_name);
        $xmlStr="<$class_name>\n";
        foreach ($columns as $key => $column) {
            $propertyname = $column["property"];
            $xmlStr.="<$propertyname>".$entity->__get($propertyname)."</$propertyname>\n";

        }

        $sinif_haritasi=$map->getForeignClassesMap($class_name);
        foreach ($sinif_haritasi as $tablo => $deger)
        {
            foreach ($deger as $oge){

                $islenecek_sinif_adi = $oge["class"];
                $propertyname =$oge["property"];
                $array=  $map->getClassColumns($islenecek_sinif_adi);
                {
                    if($entity->$propertyname instanceof ArrayObject )
                    {
                        foreach ($entity->$propertyname as $obj)
                        {
                            $xmlStr.="<$islenecek_sinif_adi>\n";
                            ;
                            foreach ($array as $key => $val) {
                                $subkey =$val["property"];
                                $xmlStr.="<$subkey>".$obj->$subkey."</$subkey>\n";
                                if($obj->$subkey instanceof Entity )
                               $xmlStr.= $this->entityToXML($obj->$subkey);

                            }

                            $xmlStr.="</$islenecek_sinif_adi>\n";
                        }
                    }
                    elseif($entity->$propertyname instanceof Entity)
                    {
                        $xmlStr.="<$islenecek_sinif_adi>\n";
                        foreach ($array as $key => $val) {
                            // efe($array);
                            $subkey =$val["property"];

                            $xmlStr.="<$subkey>".$entity->$propertyname->$subkey."</$subkey>\n";
                            if($entity->$propertyname->$subkey instanceof Entity )
                          $xmlStr.=   $this->entityToXML($entity->$propertyname->$subkey);
                        }
                        $xmlStr.="</$islenecek_sinif_adi>\n";
                    }
                }
            }}
        $xmlStr.="</$class_name>\n";
        return $xmlStr;
    }

/**
 *
 * @param <Array> $ar
 * @param <Entity> $object
 * @param <String> $onek
 * @param <Boolean> $class
 *
 *onek parametresi array indexsinde ismine bir on ok eger class name true ise bu on eke class isimide ekler
 * ve en son classın nitelik ismini ekler arraydan ceker varlık nesnesine ekler
 * html true ise htmlspecialchars çalışır
 */
    function arrayToEntity(array $ar,Entity $object,$onek="",$class=false,$html=false) {
    
   
      $class_name = get_class($object);
        $map=  ClassMap::getInstance();//new ClassMap();
        $array=  $map->getClassColumns($class_name);
        foreach ($array as $key => $val) {
            $monek =   ($class==true)? strtolower($onek.$class_name.$key): strtolower($onek.$key);
            if(isset ($ar[$monek]));
            // call_user_method("__set",&$object,$key,($html)?htmlspecialchars($ar[$monek]):$ar[$monek]);
        }
        $sinif_haritasi=$map->getForeignClassesMap($class_name);
        foreach ($sinif_haritasi as $tablo => $deger)
        {
            foreach ($deger as $oge){
                $islenecek_sinif_adi = $oge["class"];
                $property =$oge["property"];
                if($class==true)
                //$onek.=$islenecek_sinif_adi;
                $array=  $map->getClassColumns($islenecek_sinif_adi);
                foreach ($array as $key => $val) {
                    $monek =  strtolower($onek.$islenecek_sinif_adi.$key);
                    if(isset ($ar[$monek]));
                  //call_user_method("__set",&$object->$property,$key,($html)?htmlspecialchars($ar[$monek]):$ar[$monek]);

                }
            }
        }
    }


    public function entityToJSON($entity) {
       return json_encode($entity);
    }
}
?>
