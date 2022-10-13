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

    static function loadPowerModels()
    {
        $data = self::readCSVData(__DIR__ . '/../data/teclib-editions/powermodels.csv');
        foreach ($data as $values) {
            $name = $values['Power model'];
            $power = floatval($values['Power']);
            $category = $values['Category'];

            PowerModel::updateOrInsert($name, $power, $category);
        }
    }

    static function install(Migration $migration)
    {
        self::loadPowerModels();
        //self::$computerModels2powerModels = self::readCSVData(__DIR__ . '/../data/teclib-editions/computermodels2powermodels.csv');
    }

    static function uninstall(Migration $migration)
    {
    }

}
