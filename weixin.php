<?php
require_once 'lib_images_process.php';
require_once 'lib_mysql_process.php';
require_once '../php-image-magician/php_image_magician.php';

define("TOKEN", "zxtweixin");
$wechatObj = new wechatCallbackapiTest();
if (isset($_GET["echostr"])) {
    $wechatObj->valid();
} else {
    $wechatObj->responseMsg();
}
class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            ob_clean();
            echo $echoStr;
            exit;
        }
    }
    private function checkSignature()
    {
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    public function responseMsg()
    {
        $file = "log.txt";
        $postStr = file_get_contents("php://input");
        file_put_contents($file, $postStr, FILE_APPEND);
        if (!empty($postStr)) {
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            // file_put_contents($file, $RX_TYPE, FILE_APPEND);
            switch ($RX_TYPE) {
                case "text":
                    $resultStr = $this->receiveText($postObj);
                    break;
                case "image":
                    $resultStr = $this->receiveImage($postObj);
                    break;
                case "location":
                    $resultStr = $this->receiveLocation($postObj);
                    break;
                case "voice":
                    $resultStr = $this->receiveVoice($postObj);
                    break;
                case "video":
                    $resultStr = $this->receiveVideo($postObj);
                    break;
                case "link":
                    $resultStr = $this->receiveLink($postObj);
                    break;
                case "event":
                    $resultStr = $this->receiveEvent($postObj);
                    break;
                default:
                    $resultStr = "unknow msg type: " . $RX_TYPE;
                    break;
            }
            echo $resultStr;
        } else {
            echo "";
            file_put_contents($file, "hahahhhhh", FILE_APPEND);
            exit;
        }
    }
    private function transmitText($object, $content, $flag = 0)
    {
        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>%d</FuncFlag>
					</xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
        return $resultStr;
    }
    private function receiveText($object)
    {
        $file = "log.txt";
        $funcFlag = 0;
        $contentStr = $object->Content;
        file_put_contents($file, $contentStr, FILE_APPEND);
        // try
        // {
        //     $conn = new PDO("mysql:host=localhost;dbname=demo", "root", "zxt1044zcx7389");
        //     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //     $sql = "SELECT imagename FROM espimages LIMIT 1,1";
        //     foreach ($conn->query($sql) as $row) {
        //         $image_id = intval($row['imagename']);
        //         if ($image_id >= 10 || $image_id < 1 || !$image_id) {
        //             $image_id = 1;
        //         }
        //     }
        //     $sql = "DELETE FROM espimages";
        //     $conn->exec($sql);
        //     $sql = "INSERT INTO espimages (imagename) VALUES ('$contentStr')";
        //     $conn->exec($sql);
        //     $sql = "INSERT INTO espimages (imagename) VALUES ('$image_id')";
        //     $conn->exec($sql);

        //     $file = new File();
        //     $info = $file->getFiles('images_format/', true);
        //     $stack = array();
        //     $stack[1] = $contentStr;
        //     $stack[2] = $image_id;
        //     $images_counts = 3;
        //     foreach ($info as $filename) {
        //         $sql = "INSERT INTO espimages (imagename) VALUES ('$filename')";
        //         $conn->exec($sql);
        //         $stack[$images_counts] = $filename;
        //         $images_counts++;
        //     }
        //     echo json_encode($stack);
        // } catch (PDOException $err) {
        //     echo $sql . "<br>" . $err->getMessage();
        // }
        // $conn = null;

        $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
        return $resultStr;
    }
    private function receiveImage($object)
    {
        // $file = "log.txt";
        $funcFlag = 0;
        $contentStr = $object->PicUrl;
        // file_put_contents($file, $contentStr, FILE_APPEND);
        $my_images_dir = "images/";
        $filename = "weixin_images.jpg";
        $images_save_loc = $my_images_dir . $filename;
        file_put_contents($images_save_loc, file_get_contents($contentStr));
        $my_images_format_dir = "images_format/";
        $filename = "weixin_images.bmp";
        $images_format_save_loc = $my_images_format_dir . $filename;
        $magicianObj = new imageLib($images_save_loc);
        $magicianObj->resizeImage(144, 1000, 2);
        $magicianObj->saveImage($images_format_save_loc, 100);
        $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
        return $resultStr;
    }
}
