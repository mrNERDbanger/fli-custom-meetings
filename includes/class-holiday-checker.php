<?php
class FLI_Holiday_Checker {
    private static $holidays_cache = [];

    public static function get_federal_holidays($year = null) {
        if (!$year) {
            $year = date('Y');
        }

        if (isset(self::$holidays_cache[$year])) {
            return self::$holidays_cache[$year];
        }

        $holidays = [
            // New Year's Day
            "$year-01-01",
            
            // Martin Luther King Jr. Day (3rd Monday in January)
            date('Y-m-d', strtotime("third monday of january $year")),
            
            // Presidents Day (3rd Monday in February)
            date('Y-m-d', strtotime("third monday of february $year")),
            
            // Memorial Day (Last Monday in May)
            date('Y-m-d', strtotime("last monday of may $year")),
            
            // Juneteenth National Independence Day
            "$year-06-19",
            
            // Independence Day
            "$year-07-04",
            
            // Labor Day (1st Monday in September)
            date('Y-m-d', strtotime("first monday of september $year")),
            
            // Columbus Day (2nd Monday in October)
            date('Y-m-d', strtotime("second monday of october $year")),
            
            // Veterans Day
            "$year-11-11",
            
            // Thanksgiving Day (4th Thursday in November)
            date('Y-m-d', strtotime("fourth thursday of november $year")),
            
            // Christmas Day
            "$year-12-25"
        ];

        // Handle weekend holidays
        foreach ($holidays as $key => $date) {
            $timestamp = strtotime($date);
            $day_of_week = date('w', $timestamp);
            
            // If holiday falls on Saturday, observe on Friday
            if ($day_of_week == 6) {
                $holidays[$key] = date('Y-m-d', strtotime('-1 day', $timestamp));
            }
            // If holiday falls on Sunday, observe on Monday
            elseif ($day_of_week == 0) {
                $holidays[$key] = date('Y-m-d', strtotime('+1 day', $timestamp));
            }
        }

        self::$holidays_cache[$year] = $holidays;
        return $holidays;
    }

    public static function is_holiday($date) {
        $year = date('Y', strtotime($date));
        $holidays = self::get_federal_holidays($year);
        return in_array($date, $holidays);
    }

    public static function get_next_business_day($date) {
        $timestamp = strtotime($date);
        do {
            $timestamp = strtotime('+1 day', $timestamp);
            $new_date = date('Y-m-d', $timestamp);
            $day_of_week = date('w', $timestamp);
        } while ($day_of_week == 0 || $day_of_week == 6 || self::is_holiday($new_date));
        
        return $new_date;
    }

    public static function get_holidays_between_dates($start_date, $end_date) {
        $start_year = date('Y', strtotime($start_date));
        $end_year = date('Y', strtotime($end_date));
        $holidays = [];

        for ($year = $start_year; $year <= $end_year; $year++) {
            $yearly_holidays = self::get_federal_holidays($year);
            foreach ($yearly_holidays as $holiday) {
                if ($holiday >= $start_date && $holiday <= $end_date) {
                    $holidays[] = $holiday;
                }
            }
        }

        return $holidays;
    }
} 