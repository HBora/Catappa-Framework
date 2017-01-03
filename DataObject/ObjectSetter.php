<?php

namespace Catappa\DataObject;


/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name ObjectSetter
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 2.0
 * @category Catappa ORM
 */
use Catappa\DataObject\Query\Query;
use Catappa\Collections\ArrayList;
class ObjectSetter {

    var $map;
    var $islenmis_haritalar = array();
    var $islenmis_yavru_nesneler = array();
    var $islenmis_ana_nesneler = array();
    var $ana_sinif_ismi;
    var $ana_nesne_anahtari;
    var $nesne_kayitedici;
    var $query;
    var $ana_tablo_ismi;
    var $unique = array();

    function __construct(ClassMap $map, \SplObjectStorage $registy, $db) {
        $this->map = $map;
        $this->nesne_kayitedici = $registy;
        $this->query = $db;
        $this->ana_sinif_ismi = $map->getSuperClass();
        $pkId = $this->map->getId($this->ana_sinif_ismi);
        $this->ana_tablo_ismi = $this->map->getTableName($this->ana_sinif_ismi);
        $this->ana_nesne_anahtari = $this->ana_tablo_ismi . "." . $pkId;
    }

    private function hazirlan() {
        $this->ana_sinif_ismi = $this->map->getSuperClass();
        $pkId = $this->map->getId($this->ana_sinif_ismi);
        $this->ana_tablo_ismi = $this->map->getTableName($this->ana_sinif_ismi);
        $this->ana_nesne_anahtari = $this->ana_tablo_ismi . "." . $pkId;
    }

    public function clear() {
        $this->islenmis_haritalar = array();
        $this->islenmis_ana_nesneler = array();
        $this->islenmis_yavru_nesneler = array();
        $this->unique = array();
    }

    function kulucka(Query $query=null) {

        $this->clear();
        $isRecord = false;
        if ($query != null)
            $this->query = $query;
        $this->hazirlan();
        $this->islenmis_ana_nesneler[$this->ana_sinif_ismi] = array();
        while ($veri = $this->query->nextResutlt()) {
          //pre($veri);
            $isRecord = true;
            unset($this->islenmis_haritalar); //islenmis haritalari sifirla
            $this->islenmis_haritalar = array();
            $yeni_ana_nesne = null;
            if (!isset($this->unique[$this->ana_sinif_ismi][$veri[$this->ana_nesne_anahtari]])) {
                $new = $this->ana_sinif_ismi;
                $yeni_ana_nesne = new $new();
                $this->atayici($yeni_ana_nesne, $veri, $this->ana_sinif_ismi, $this->ana_tablo_ismi);

                $this->nesne_kayitedici->attach($yeni_ana_nesne);
                //$this->islenmis_ana_nesneler[$this->ana_sinif_ismi][$veri[$this->ana_nesne_anahtari]]=$yeni_ana_nesne;
                $id = array_push($this->islenmis_ana_nesneler[$this->ana_sinif_ismi], $yeni_ana_nesne) - 1;

                $this->unique[$this->ana_sinif_ismi][$veri[$this->ana_nesne_anahtari]] = $id;
            } else {
                $yeni_ana_nesne = $this->islenmis_ana_nesneler[$this->ana_sinif_ismi][$this->unique[$this->ana_sinif_ismi][$veri[$this->ana_nesne_anahtari]]];
            }
            $this->yavrulat($veri, $yeni_ana_nesne, $this->ana_sinif_ismi);
        }
        if ($isRecord)
            return new ArrayList($this->islenmis_ana_nesneler[$this->ana_sinif_ismi]);
        return NULL;
    }

  

    function yavrulat($veri, &$obj) {
        $super_nesne_adi = get_class($obj);
        if (isset($this->islenmis_haritalar[$super_nesne_adi]))
            return;
        $this->islenmis_haritalar[$super_nesne_adi] = $super_nesne_adi;
        $yavru_sinif_adi = "";
        $yavru_nesnenin_haritasi = $this->map->getForeignClassesMap($super_nesne_adi);
        
        foreach ($yavru_nesnenin_haritasi as $iliski_duzeyi => $yavru_harita_ogesi) {

            foreach ($yavru_harita_ogesi as $yavru_harita_sinif) {
                $yavru_sinif_adi = $yavru_harita_sinif["class"];
                $yavru_tablo_adi = $this->map->getTableName($yavru_sinif_adi);
                $nitelik_adi = $yavru_harita_sinif["property"];

                $pkId = $this->map->getId($yavru_sinif_adi);
                $yavru_nesne_anahtari = $yavru_tablo_adi . "." . $pkId;

                if ($yavru_harita_sinif["line"]!=-1)
                    $yavru_nesne_anahtari = "self" . "_" . $yavru_harita_sinif["line"] . "." . $pkId;
           
                if (isset($this->islenmis_yavru_nesneler[$yavru_sinif_adi][$veri[$yavru_nesne_anahtari]]))
                    $yavru_nesne = $this->islenmis_yavru_nesneler[$yavru_sinif_adi][$veri[$yavru_nesne_anahtari]];
            
                elseif ($yavru_sinif_adi == $this->ana_sinif_ismi &&!$yavru_harita_sinif["line"]!=-1)
                
                  $yavru_nesne = $this->islenmis_ana_nesneler[$this->ana_sinif_ismi][$this->unique[$this->ana_sinif_ismi][$veri[$this->ana_nesne_anahtari]]];
                
                else {
                
                    $yavru_nesne = new $yavru_sinif_adi();
                    $this->nesne_kayitedici->attach($yavru_nesne);
                }

                if (isset($veri[$yavru_nesne_anahtari])) {
                    if ($iliski_duzeyi == "one") {
                        
                     if (!isset($this->islenmis_yavru_nesneler[$yavru_sinif_adi][$veri[$yavru_nesne_anahtari]]))

                     $this->atayici($yavru_nesne, $veri, $yavru_sinif_adi, $yavru_tablo_adi, $yavru_harita_sinif["line"]);
                        call_user_func(array($obj, "__dbset"), $nitelik_adi, $yavru_nesne);
                   
                    }
                    if ($iliski_duzeyi == "many") {
                        if (!$obj->$nitelik_adi instanceof ArrayList)
                        //call_user_method("__dbset", $obj, $nitelik_adi,new ArrayObject());
                            call_user_func(array($obj, "__dbset"), $nitelik_adi, new ArrayList());
                        if (!isset($this->islenmis_yavru_nesneler[$yavru_sinif_adi][$veri[$yavru_nesne_anahtari]])) {
                            $this->atayici($yavru_nesne, $veri, $yavru_sinif_adi, $yavru_tablo_adi,$yavru_harita_sinif["line"]);
                            $obj->$nitelik_adi->append($yavru_nesne);
                        }
                    }
                        
                    $this->islenmis_yavru_nesneler[$yavru_sinif_adi][$veri[$yavru_nesne_anahtari]] = $yavru_nesne;

                    $this->yavrulat($veri, $yavru_nesne);
                }
            }
        }
    }

    function atayici($obj, $veriler, $class_name, $table_name=null, $child=-1) {

        if (isset($this->unique[$class_name][$veriler[$this->ana_nesne_anahtari]])&&$child==-1)
                return;
       
        $columns = $this->map->getClassColumns($class_name);
     
        foreach ($columns as $column) {
            
            $nitelik = $column["property"];
            $takmaisim = $table_name . "." . $column["name"];
            if($child!=-1)
                $takmaisim  ="self_$child.".$column["name"];
            // pre("c= $takmaisim ".$class_name);
            if (isset($veriler[$takmaisim]))

                call_user_func(array($obj, "__dbset"), $nitelik, $veriler[$takmaisim]);
          
        }
        
    }
}