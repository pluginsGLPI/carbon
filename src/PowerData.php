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

        $header = array_slice(fgetcsv($file), 1);
        while (($line = fgetcsv($file)) !== FALSE) {
            $data[$line[0]] = array_combine($header, array_slice($line, 1));
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
