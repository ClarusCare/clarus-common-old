<?php

namespace ClarusCommon\Traits;

use DateTime;

/**
 * Attaches S3 files to models trait.
 *
 * @author Erik Galloway <egalloway@claruscare.com>
 */
trait ReformattingTranscriptionResponses
{
    function dob_from_content($input) {
        $alphbetExist = preg_match("/[a-zA-Z]/", $input);
        $input = preg_replace('/\s+/', ' ', strtolower($input));
        $input = trim(str_replace(",","",$input));
        $input = trim(str_replace("-"," ",$input));
    
        if (strtolower($input) === 'no message left.' || strtolower($input) === 'no message left') {
            return config('constants.NO_MESSAGE_LEFT');
        }
    
        $pattern = '/\b(\d{1,2}\/\d{1,2}\/\d{2,4})\b/';
        preg_match($pattern, $input, $matches);
        if (!empty($matches)) {
         return $matches[1];
        }
        // Convert month names to integers
    
        $months = [
            'january' => '01',
            'jan' => '01',
            'february' => '02',
            'feb' => '02',
            'march' => '03',
            'april' => '04',
            'may' => '05',
            'june' => '06',
            'july' => '07',
            'august' => '08',
            'september' => '09',
            'october' => '10',
            'november' => '11',
            'december' => '12',
        ];
        $matchMonth = false;
        $pattern = '/\b(' . implode('|', array_keys($months)) . ')\b/i';
        $output = preg_replace_callback($pattern, function ($match) use ($months,&$matchMonth) {
            $matchMonth = $months[$match[0]];
            return $months[$match[0]];
        }, $input);
    
        $words = [
            'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth',
            'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth', 'twentieth',
            'twenty-first', 'twenty-second', 'twenty-third', 'twenty-fourth', 'twenty-fifth', 'twenty-sixth', 'twenty-seventh', 'twenty-eighth', 'twenty-ninth', 'thirtieth', 'thirty-first'
        ];
        $numbers = [
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
            '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
            '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'
        ];
        // Combine the two arrays into a key-value array
        $result = array_combine($words, array_map(function($number) {
            return str_pad($number, 2, '0', STR_PAD_LEFT);
        }, $numbers));
        $days = [
            '1st' => '01',
            '2nd' => '02',
            '3rd' => '03',
            '4th' => '04',
            '5th' => '05',
            '6th' => '06',
            '7th' => '07',
            '8th' => '08',
            '9th' => '09',
            '10th' => '10',
        ];
    
        $days = array_merge($result, $days);
    
        $matchDays = false;
        $pattern = '/\b(' . implode('|', array_keys($days)) . ')\b/i';
    
        $output = preg_replace_callback($pattern, function ($match) use ($days,&$matchDays) {
            $matchDays = $days[$match[0]];
            return $days[$match[0]];
        }, $output);
    
    
    
    
        $input = preg_replace('/[^0-9 ]/', '', $output);
        $input = preg_replace('/\s+/', ' ', $input);
        $dateString = str_replace(' ','/',trim($input));
    
        $slashCount = substr_count($dateString, '/');
    
        if ($slashCount == 5) {
            $splitDates = explode('/', $dateString);
            $midpoint = ceil(count($splitDates) / 2);
            $firstDate = implode('/', array_slice($splitDates, 0, $midpoint));
            $secondDate = implode('/', array_slice($splitDates, $midpoint));
            if($firstDate == $secondDate){
                $dateString =  $firstDate;
            }
        }
    
        if (preg_match('/^(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])\/(?!0)\d{2,4}$/', $dateString)) {
            return $dateString;
        }
    
        preg_match_all("/\b\d{4}\b/", $input, $matches);
        $fourDigityear = '';
        if(!empty($matches[0])){
            $fourDigityear = $matches[0];
            if(!empty($matchDays) && !empty($matchMonth)) {
                $input = $matchMonth.$matchDays.$fourDigityear[0];
    
            } else {
                $uniquarr = array_values(array_filter(array_unique(explode(" ",$input)),function ($value) use ($matchMonth) {
                    if($matchMonth){
                        return   $value !== "" && $value != $matchMonth;
                    }
                    return $value !== "";
    
                }));
                $input  =  ($matchMonth??$uniquarr[1]??"").($uniquarr[0]??"") . $fourDigityear[0];
            }
    
    
        }
    
        //Checck for valid input
        $checkVaildArr = explode(" ",$input);
    
        if(!empty($checkVaildArr) && !$matchMonth && ($alphbetExist || preg_match('/^\d{2} \d{2} \d{2}$/', trim($input)))) {
            $validInput = false;
            foreach($checkVaildArr as $value){
                if(strlen($value) == 2 && $value < 12){
                    $validInput = true;
                    break;
                }else if (strlen($value) == 4) {
                    $validInput = true;
                    break;
                }
            }
            if(!$validInput){
                return "Unable to transcribe";
            }
    
        }
    
    
        $input = str_replace([' '], '', $input);
    
        $length = strlen($input);
        if ($length === 7) {
            if(substr($input, 0, 1) !=  0){
                $input = '0' . $input;
            }else {
                $input = substr($input, 0, 2) ."0". substr($input, 2, 1).'19' . substr($input, -2);
            }
        } elseif ($length === 6) {
            $input =  substr($input, 0, 2) .substr($input, 2, 2). '19' . substr($input, -2);
        }elseif ($length === 5) {
            $input = '0'.substr($input, 0, 1) . substr($input, 1, 2).'19' . substr($input, -2);
        }
        elseif ($length === 4) {
            $input = '0'.substr($input, 0, 1) .'0'. substr($input, 1, 1) . '19' . substr($input, -2);
        }
    
            preg_match_all('/(\b\d{8})/', $input, $matches);
            $sequences = $matches[1];
            $sequences = array_unique($sequences);
    
    
    
        // Remove duplicate sequences
    
    
        // If multiple sequences found, use the first one
        $date_sequence = count($sequences) > 0 ? $sequences[0] : '';
    
    
        // If the date sequence is exactly 8 digits, convert to date format
        if (strlen($date_sequence) === 8) {
            $date = DateTime::createFromFormat('mdY', $date_sequence);
            $currentDate = new DateTime(); // Get the current date
    
            if ($date && $date <= $currentDate) {
                $dataFormat =  $date->format('m/d/Y');
                $dataFormatarr = explode('/',$dataFormat);
                if(!empty($matchDays)) {
                    $dataFormatarr[1] = $matchDays;
                }
                if(!empty($matchMonth)) {
                    $dataFormatarr[0] = $matchMonth;
                }
                if(!empty($fourDigityear)) {
                    $dataFormatarr[2] = $fourDigityear[0];
                }
                return implode('/',$dataFormatarr);
    
            }
        }
        return 'Unable to transcribe';
    
    }


    


    public function name_from_content($inputString) {
        $wordArray = array();
        $word = '';
        $spelledWord = '';
        $spokenWord = '';
        $outputString = '';
        
        // Trim input
        $inputString = trim($inputString);
        $inputString = str_replace(["'","<",">"], '', $inputString);
         //Respect system message "Unable to transcribe"
         if (strtolower($inputString) === 'unable to transcribe') {
            return $inputString;
        }
        
        // Check for two-word input
        $inputArr = explode(' ', $inputString);
        if (count($inputArr) === 2) {
            // Remove periods

            //Check , in between word
            // $tmpstr = str_replace(' ','',$inputString);
            // $commaExplodeArr = explode(',',$tmpstr);
            // $inputString = implode(" ", (strpos($inputString, ',') !== false && !empty($commaExplodeArr[1]))?array_reverse($inputArr):$inputArr);
            $inputString = str_replace(['.',','], '', $inputString);
            return $inputString;
        }

        $inputArr = explode(',', $inputString);
        if (count($inputArr) > 1) {      

            $wordCounts = array_count_values(array_map(function($value) {
                return trim(strtolower($value));
            }, $inputArr));
            $uniqueArray = array_keys(array_filter($wordCounts, function ($count) {
                return $count === 1;
            }));

                    $inputString = implode(" ", $uniqueArray);
            $inputString = preg_replace('/(?<=\b\p{L})\s(?=\p{L}\b)/u', '', $inputString);
         
        
        }
        
        // Split into words, adding space to end
        $wordArray = preg_split('/\s/', $inputString, -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = ['could', 'might', 'system', 'um', 'uh', 'hello', 'you', 'first', 'name', 'is', 'and', 'the', 'office', 'dr', 'doctor', 'patient', 'any', 'thank', 'call', 'last', 'that\'s', 'it', 'its', 'it’s', 'be', 'my', 'hi', 'i\'m', 'new', 'one', 'birth', 'date', 'yes', 'tells', 'in', 'no', 'us', 'our', 'ever', 'sold', 'next', 'as', 'in', 'as-in', 'at', 'to', 'are', 'phone', 'periods','capital','of','have',"this","then","with","birthdate","that",
        "thats","an","from","icu","patients"];
        
        // Remove duplicate words
        if(!empty($wordArray)){
            $uniqueArray = array_intersect_key(
                $wordArray,
                array_unique(
                    array_map('strtolower', $wordArray)
                )
            );            
            $wordArray = array_values($uniqueArray);
        }
                                                                   // Loop words
        foreach ($wordArray as &$word) {
            // Skip 1 character words
            if (strlen($word) === 1) {
                $word = '';
            }
            
            // Remove common words
            if (in_array(strtolower($word), $stopWords)) {
                $word = '';
            }
            
            // Check for spoken letter
            if (strlen($word) === 2 && substr($word, -1) === '.') {
                // Remove periods
                $spelledWord .= str_replace('.', '', $word);
            } else {
                // Remove numbers and special characters
                $word = trim(preg_replace('/[^[:alpha:]\s]/', '', $word));
                
                $spokenWord .= ' ' . $word;
                $spelledWord .= ' ' . $word;
            }
        }
        
        // Check for 1 character words in spelled
        if (strlen($spelledWord) > 0) {
            // Split into words, adding space to end
            $wordArray = preg_split('/\s/', $spelledWord, -1, PREG_SPLIT_NO_EMPTY);
            
            $spelledWord = '';
            
            // Loop words
            foreach ($wordArray as $word) {
                if (strlen($word) > 1) {
                    $spelledWord .= ' ' . $word;
                }
            }
        }
        
        // Trim values
        $spelledWord = trim($spelledWord);
        $spokenWord = trim($spokenWord);
        
        // Use spelled word if greater than one
        if (array_sum(array_map('str_word_count', explode(' ', $spelledWord))) > 1) {
            $outputString = ucwords($spelledWord);
        } else {
            if (strlen($spokenWord) > 0) {
                $outputString = $spokenWord;
            } else {
                if (strlen($spelledWord) > 0) {
                    $outputString = $spelledWord;
                }
            }
        }
        
        // Check for empty output
        if (strlen($outputString) === 0) {
            // Remove numbers and special characters
            $outputString = str_replace('. ', '', $inputString);
        }
        
        // Remove characters after third word
        $outputString = implode(' ', array_slice(explode(' ', $outputString), 0, 3));
        
        return $outputString;
    }

    public function message_from_content($input) {
        // Rule 1: Format 10-digit numbers like phone numbers
        $input = preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $input);
        
        // Rule 2: Format dates or 8 digits like MM/DD/YYYY
        $currentDate = date('Y-m-d');
        $currentYear = date('Y');
        $currentMonth = date('m');
        $currentDay = date('d');
        
        $input = preg_replace_callback('/(\d{2})(\d{2})(\d{4})/', function($match) use ($currentYear, $currentMonth, $currentDay) {
            $month = $match[1];
            $day = $match[2];
            $year = $match[3];
            
            if ($month <= 12 && $day <= 31 && $year <= $currentYear) {
                return $month . '/' . $day . '/' . $year;
            }
            
            return $match[0]; // No reformatting
        }, $input);
        
        // Rule 3: Sentence case
        $input = ucfirst(strtolower($input));
        
        
        // Rule 5: Respect system message "No message left"
        if (strtolower($input) === strtolower(config('constants.NO_MESSAGE_LEFT'))) {
            return $input;
        }
        
        // Rule 6: If no final result can be parsed, return "Unable to transcribe"
        if (preg_match('/\b\d+\b/', $input)) {
            return $input;
        }
        
        return $input;
    }
}
