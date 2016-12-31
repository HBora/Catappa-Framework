<?php

/**
 * WSDL Generator
 * @author H.B.ABACI
 * @package Soap
 * @since 2010
 *
 */

namespace Catappa\Http\Soap;

use Catappa\Collections\Config;

ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 0);
ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 0);

class WSGen {

    public $class_map = array();
    private $elems;
    private $types;
    private $union;
    private $response_Union;
    private $requestTypes = "";
    private $responseTypes = "", $messageParts = "", $soap_opetations = "";
    private $config;

    function __construct() {
        $this->config = Config::getInstance();

        $dir = str_replace("Http" . DS . "Soap", "", __DIR__);

        require_once $dir . DS . "DataObject" . DS . 'annotations.php';


        require_once __DIR__ . DS . "annotations.php";
    }

    function classProperties($class_pack, $type = "Request", $elem_name = null) {
        $pos = strrpos($class_pack, '\\') + 1;
        $class_name = substr($class_pack, $pos);
        $package_name = substr($class_pack, 0, $pos);

        $item = new \ReflectionAnnotatedClass($class_pack);
        if ($elem_name == null)
            $elem_name = $class_name;



        if ($type == "Request")
            $this->union.= "\n" . '<xsd:element name="' . $elem_name . '" type="tns:' . $class_name . '"/>';
        else
            $this->response_Union.= "\n" . '<xsd:element name="' . $elem_name . '" type="tns:' . $class_name . '"/>';

        if (in_array("\\" . $class_pack, $this->class_map))
            return;

        $this->class_map[$class_name] = "\\" . $class_pack;
        $properties = $item->getProperties();

        $this->subProperties($properties);

        $elems = $this->subProperties($properties);
        $this->elems.= "\n" . '<xsd:element name="' . $elem_name . '" type="tns:' . $class_name . '"/>';

        $this->types.= $this->docomplexTypeItem($class_name, $elems) . "\n";
    }

    function get_class_name($param) {

        return substr($param, strrpos($param, '\\') + 1);
    }

    function subProperties($properties) {
        $in = "";
        foreach ($properties as $prty) {
            if ($prty->hasAnnotation("Property")) {
                $str = "";
                $attributes = $prty->getAnnotation("Property");
                $keys = "[^value|^return|^suffix|^type]";
                foreach ($attributes as $key => $val) {
                    if (!preg_match($keys, $key))
                        if ($val != null)
                            $str.=" $key = \"$val\"";
                }
                $type = $prty->getAnnotation("Property")->type;
                $prty = $prty->getName();
                $in.="\n" . '<xsd:element name="' . $prty . '" type="xsd:' . $type . $attributes->suffix . '"' . $str . '/>';
            }
        }
        return $in;
    }

    function response($method) {
        $param = $method->getAnnotation("WebMethod")->return;
        $min = $method->getAnnotation("WebMethod")->minOccurs;
        $max = $method->getAnnotation("WebMethod")->maxOccurs;
        $suffix = $method->getAnnotation("WebMethod")->suffix;
        $name = $method->getName() . "ResponseType";
        $attributes = $method->getAnnotation("WebMethod");
        $str = "";
        $keys = "[^value|^return|^suffix]";
        foreach ($attributes as $key => $val) {
            if (!preg_match($keys, $key))
                if ($val != null)
                    $str.=" $key = \"$val \"";
        }
        $this->elems.="\n" . '<xsd:element name="' . $name . '" type="tns:' . $name . $suffix . '"' . $str . ' />';

        if ($param) {
            $pos = stripos($param, '.');
            if ($pos != false) {

                $class = str_replace(".", "\\", $param);
                $this->classProperties($class, "Response");
            } else {
                $this->response_Union.= "\n" . '<xsd:element name="Response" type="xsd:' . $param . '"/>';
            }
        }
    }

    function gen($class_, $show = true, $style = "document") {
        $pos = strrpos($class_, '\\') + 1;
        $class_name1 = substr($class_, $pos);
        $package_name1 = substr($class_, 0, $pos);
        $ref = new \ReflectionAnnotatedClass($class_, "soap\\");

        $this->class_map = array();

        $methods = $ref->getMethods();

        // $methods = new \ReflectionAnnotatedMethod($class_or_method, $name);

        foreach ($methods as $method) {

            if ($method->hasAnnotation("WebMethod")) {
                if ($method->hasAnnotation("Param")) {

                    $mparams = $method->getParameters();

                    $annotparams = $method->getAllAnnotations();

                    $i = 0;
                    foreach ($mparams as $param) {
                        if (strpos($annotparams[$i + 1]->type, '.') != false) {
                            $class = str_replace(".", "\\", $annotparams[$i + 1]->type);
                            $this->classProperties($class, "Request", $param->name);
                        } else {
                            $keys = "[^value|^return|^suffix|^type]";

                            $str = "";
                            foreach ($annotparams[$i + 1] as $key => $val) {

                                if (!preg_match($keys, $key)) {
                                    if ($val != null)
                                        $str.=" $key = \"$val \"";
                                }
                            }

                            $this->union.="\n" . '<xsd:element name="' . $mparams[$i]->name . '" type="xsd:' . $annotparams[$i + 1]->type . '"' . $str . '/>';
                        }
                        $i++;
                    }
                }
                $name = $method->getName();
                $request_name = $name . "RequestType";
                $this->elems.="\n" . '<xsd:element name="' . $request_name . '" type="tns:' . $request_name . '"/>';

                $this->requestTypes.= $this->docomplexTypeItem($request_name, $this->union) . "\n";
                $this->union = "";
                $this->messageParts.='
                    <wsdl:message name="' . $name . 'Request">
                    <wsdl:part element="tns:' . $request_name . '" name="parameters"/>
                    </wsdl:message>' . "\n";

                /* ----------------------------Response----------------------------------------------- */
                $this->response($method);

                $response_name = $method->getName() . "ResponseType";
                $this->responseTypes.= $this->docomplexTypeItem($response_name, $this->response_Union) . "\n";
                $this->response_Union = "";
                $this->messageParts.='
                    <wsdl:message name="' . $method->getName() . 'Response">
                    <wsdl:part element="tns:' . $response_name . '" name="parameters"/>
                    </wsdl:message>' . "\n";
                /* ------------------------------------------------------------------------------------- */
                $this->wsdl_operations.=
                        "\n" . '<wsdl:operation name="' . $name . '">
            <wsdl:input message="tns:' . $name . 'Request"/>
            <wsdl:output message="tns:' . $name . 'Response"/>
            <wsdl:fault message="tns:Exception" name="Exception"/>
        </wsdl:operation>' . "\n";

                $this->soap_opetations.=
                        "\n" . '<wsdl:operation name="' . $name . '">
            <soap:operation />
            <wsdl:input>
                <soap:body use="literal"/>
            </wsdl:input>
            <wsdl:output>
                <soap:body use="literal"/>
            </wsdl:output>
            <wsdl:fault name="Exception">
               <soap:fault name="Exception" use="literal"/>
             </wsdl:fault>
        </wsdl:operation>' . "\n";
            }
        }

        if ($show)
            $this->write($class_name1, $style);
    }

    function write($target, $style = "document") {
        $pos = \strripos($_SERVER["REQUEST_URI"], "?");
        $uri = $_SERVER["REQUEST_URI"];
        if ($pos)
            $uri = \substr($uri, 0, $pos);


        $url = 'http://' . $_SERVER["HTTP_HOST"] . $uri;
        header("content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <!-- Generated by CATAPPA FRAMEWORK  -->
<wsdl:definitions xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns:tns="http://' . $target . '/service/"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema" name="' . $target . 'Service"
    targetNamespace="http://' . $target . '/service/">
    <wsdl:types>
        <xsd:schema
            targetNamespace="http://' . $target . '/service/"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <xsd:element name="Exception" type="tns:Exception"/>
' . $this->elems . " \n" .
        "\n" .
        '<xsd:complexType name="Exception">
    <xsd:sequence>
      <xsd:element name="message" type="xsd:string" minOccurs="0"/>
    </xsd:sequence>
  </xsd:complexType>
        ' .
        $this->types .
        "\n"
        . $this->requestTypes .
        "\n" .
        $this->responseTypes . '
 </xsd:schema>
    </wsdl:types>
  <wsdl:message name="Exception">
    <wsdl:part name="fault" element="tns:Exception"/>
  </wsdl:message>
 ' . $this->messageParts . '
    <wsdl:portType name="' . $target . 'ServicePortType">
     ' . $this->wsdl_operations . '
    </wsdl:portType>
    <wsdl:binding name="' . $target . 'ServiceBinding" type="tns:' . $target . 'ServicePortType">
        <soap:binding style="' . $style . '"
            transport="http://schemas.xmlsoap.org/soap/http" />
        ' . $this->soap_opetations . '
    </wsdl:binding>
  <wsdl:service name="' . $target . 'Service">
        <wsdl:port binding="tns:' . $target . 'ServiceBinding" name="' . $target . 'ServicePort">
            <soap:address
                location="' . $url . '" />
        </wsdl:port>
    </wsdl:service>
</wsdl:definitions>';
    }

    function docomplexTypeItem($name, $elems) {
        $c = '<xsd:complexType name ="' . $name . '">
<xsd:sequence>';
        $c.=$elems . '
</xsd:sequence>
</xsd:complexType>';
        return $c . "\n";
    }

}
