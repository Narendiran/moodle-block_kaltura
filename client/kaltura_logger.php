<?php
class KalturaLogger implements IKalturaLogger {
    public function __construct() {
    }

    public function log($str)  {
        // echo $str . "<br/>\n";

        $myFile = "/tmp/logger.txt";
        $fh = fopen($myFile, 'a');
        $stringData = $str . " \n";
        fwrite($fh, $stringData);

    }
}
?>