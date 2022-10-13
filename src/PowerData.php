<?php

namespace GlpiPlugin\Carbon;

use Migration;

class PowerData {

    public static $powerModels;
    public static $computerModels2powerModels;

    static function readCSVData(string $filename)
    {
        $data = [];

        $file = fopen($filename, 'r');
        if ($file == false) {
            return $data;
        }

        $header = fgetcsv($file);
        while (($line = fgetcsv($file)) !== FALSE) {
            $data[] = array_combine($header, $line);
        }

        fclose($file);

        return $data;
    }

    static function install(Migration $migration)
    {
        self::$powerModels = self::readCSVData(__DIR__ . '/../data/teclib-editions/powermodels.csv');
        self::$computerModels2powerModels = self::readCSVData(__DIR__ . '/../data/teclib-editions/computermodels2powermodels.csv');
    }

    static function uninstall(Migration $migration)
    {
    }

}
