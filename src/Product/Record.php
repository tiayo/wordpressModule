<?php

class Record
{
    /**
     * 读取进度.
     *
     * @return json
     */
    public function record()
    {
        header('Content-type: application/json');
        if (file_exists(__DIR__.'/record.txt')) {
            $record = fopen(__DIR__.'/record.txt', 'r');
            $body = fread($record, '3');
            fclose($record);

            return json_encode($body);
        }

        return $this->new_record();
    }

    /**
     * 新建记录文件.
     *
     * @return json
     */
    public function new_record()
    {
        $record = fopen(__DIR__.'/record.txt', 'w');
        $txt = 0;
        fwrite($record, $txt);
        fclose($record);

        return json_encode($txt);
    }
}

$h = new Record();
echo $h->record();
