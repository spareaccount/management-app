<?php

namespace xepan\base;

class Model_Document extends \xepan\base\Model_Table{
	
	public $table='document';
	public $strict_fields=false;
	
	public $status=[];
	public $actions=[];

	function init(){
		parent::init();
		
		// $this->hasOne('xepan\base\Epan');
		$this->hasOne('xepan\base\Contact','created_by_id')->system(true);
		$this->hasOne('xepan\base\Contact','updated_by_id')->system(true);

		$this->addField('status')->enum($this->status)->mandatory(true)->system(true);
		$this->addField('type')->mandatory(true);
		$this->addField('sub_type')->system(true);

		$this->hasMany('xepan\base\Document_Attachment','document_id',null,'Attachments');
		$this->addExpression('attachments_count')->set($this->refSQL('Attachments')->count());
		$this->addField('search_string')->type('text')->system(true)->defaultValue(null);

		$this->addField('created_at')->type('date')->defaultValue($this->app->now)->sortable(true)->system(true);
		$this->addField('updated_at')->type('date')->defaultValue($this->app->now)->sortable(true)->system(true);

		$this->addHook('beforeDelete',[$this,'DeleteAttachements']);

		$this->is([
				'created_at|required',
				'type|to_trim|required'
			]);

	}

	function DeleteAttachements(){
		foreach($this->ref('Attachments') as $a){
			$a->delete();
		}
	}

	function convertNumberToWords($number){
		$words = array(
				'0' => '', 
				'1' => 'one', 
				'2' => 'two',
		    	'3' => 'three', 
		    	'4' => 'four', 
		    	'5' => 'five', 
		    	'6' => 'six',
			    '7' => 'seven', 
			    '8' => 'eight', 
			    '9' => 'nine',
			    '10' => 'ten', 
			    '11' => 'eleven', 
			    '12' => 'twelve',
			    '13' => 'thirteen', 
			    '14' => 'fourteen',
			    '15' => 'fifteen', 
			    '16' => 'sixteen', 
			    '17' => 'seventeen',
			    '18' => 'eighteen', 
			    '19' => 'nineteen', 
			    '20' => 'twenty',
			    '30' => 'thirty', 
			    '40' => 'forty', 
			    '50' => 'fifty',
			    '60' => 'sixty', 
			    '70' => 'seventy',
			    '80' => 'eighty', 
			    '90' => 'ninety'
			);
		$digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
		$amount = $number;
		$no = round($number);
		$point = round($number - $no, 2) * 100;
		$hundred = null;
		$digits_1 = strlen($no);
		$i = 0;
		$str = array();
		while ($i < $digits_1) {
			$divider = ($i == 2) ? 10 : 100;
		 	$number = floor($no % $divider);
		 	$no = floor($no / $divider);
		 	$i += ($divider == 10) ? 1 : 2;
		 	if ($number) {
		    	$plural = (($counter = count($str)) && $number > 9) ? '' : null;
		    	$hundred = ($counter == 1 && $str[0]) ? ' ' : null;
		    	$str [] = ($number < 21) ? $words[$number] .
			        " " . $digits[$counter] . $plural . " " . $hundred
			        :
			        $words[floor($number / 10) * 10]
			        . " " . $words[$number % 10] . " "
			        . $digits[$counter] . $plural . " " . $hundred;
			} else $str[] = null;
		}
		$str = array_reverse($str);
		return $result = implode('', $str);
	}

	function amountInWords($number, $currency_id=null){
		$currency_model = $this->app->epan->default_currency;
		if($currency_id)
			$currency_model = $this->add('xepan\accounts\Model_Currency')->load($currency_id);
		
		$integer_part = isset($currency_model['integer_part'])?$currency_model['integer_part']:"";
		$fractional_part = isset($currency_model['fractional_part'])?$currency_model['fractional_part']:"";	    
	    $prefix = isset($currency_model['prefix'])?$currency_model['prefix']:"";	    
		$postfix = isset($currency_model['postfix'])?$currency_model['postfix']:"";	    

		$integer_number = 0;			
		if (strpos($number, '.') !== false) {
	       list($integer_number, $fraction) = explode('.', $number);
		}

		//integer part
		$result = $this->convertNumberToWords($integer_number);
		$amount_in_words = ($result?$result:" zero ")." ".$integer_part;

		// fractional part 
		if(isset($fraction) and $fraction > 0){
			$result = $this->convertNumberToWords($fraction);
			$amount_in_words .= " and ".$result." ".$fractional_part;
		}

		return ucwords($amount_in_words." ".$postfix);
	}

	function amountInWordsUK($number,$currency_id=null) {
	    $currency_model = $this->app->epan->default_currency;
		if($currency_id)
			$currency_model = $this->add('xepan\accounts\Model_Currency')->load($currency_id);
		
		$integer_part = isset($currency_model['integer_part'])?$currency_model['integer_part']:"";
		$fractional_part = isset($currency_model['fractional_part'])?$currency_model['fractional_part']:"";	    
	    $prefix = isset($currency_model['prefix'])?$currency_model['prefix']:"";	    
		$postfix = isset($currency_model['postfix'])?$currency_model['postfix']:"";	    
	   
	    $hyphen      = '-';
	    $conjunction = ' ';
	    // $conjunction = ' and ';
	    $separator   = ' ';
	    $negative    = 'negative ';
	    $decimal     = ' and ';
	    $dictionary  = array(
	        0                   => 'Zero',
	        1                   => 'One',
	        2                   => 'Two',
	        3                   => 'Three',
	        4                   => 'Four',
	        5                   => 'Five',
	        6                   => 'Six',
	        7                   => 'Seven',
	        8                   => 'Eight',
	        9                   => 'Nine',
	        10                  => 'Ten',
	        11                  => 'Eleven',
	        12                  => 'Twelve',
	        13                  => 'Thirteen',
	        14                  => 'Fourteen',
	        15                  => 'Fifteen',
	        16                  => 'Sixteen',
	        17                  => 'Seventeen',
	        18                  => 'Eighteen',
	        19                  => 'Nineteen',
	        20                  => 'Twenty',
	        30                  => 'Thirty',
	        40                  => 'Forty',
	        50                  => 'Fifty',
	        60                  => 'Sixty',
	        70                  => 'Seventy',
	        80                  => 'Eighty',
	        90                  => 'Ninety',
	        100                 => 'Hundred',
	        1000                => 'Thousand',
	        1000000             => 'Million',
	        1000000000          => 'Billion',
	        1000000000000       => 'Trillion',
	        1000000000000000    => 'Quadrillion',
	        1000000000000000000 => 'Quintillion'
	    );

	    if (!is_numeric($number)) {
	        return false;
	    }

	    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
	        // overflow
	        trigger_error(
	            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
	            E_USER_WARNING
	        );
	        return false;
	    }

	    if ($number < 0) {
	        return $negative . $this->amountInWords(abs($number));
	    }

	    $string = $fraction = null;

	    if (strpos($number, '.') !== false) {
	        list($number, $fraction) = explode('.', $number);
	    }

	    switch (true) {
	        case $number < 21:
	            $string = $dictionary[$number];
	            break;
	        case $number < 100:
	            $tens   = ((int) ($number / 10)) * 10;
	            $units  = $number % 10;
	            $string = $dictionary[$tens];
	            if ($units) {
	                $string .= $hyphen . $dictionary[$units];
	            }
	            break;
	        case $number < 1000:
	            $hundreds  = $number / 100;
	            $remainder = $number % 100;
	            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
	            if ($remainder) {
	                $string .= $conjunction .$this->amountInWords($remainder);
	            }
	            break;
	        default:
	            $baseUnit = pow(1000, floor(log($number, 1000)));
	            $numBaseUnits = (int) ($number / $baseUnit);
	            $remainder = $number % $baseUnit;
	            $string = $this->amountInWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
	            if ($remainder) {
	                $string .= $remainder < 100 ? $conjunction : $separator;
	                $string .= $this->amountInWords($remainder);
	            }
	            break;
	    }

	    

	    if (null !== $fraction && is_numeric($fraction)) {
	    	// concating integer part
	    	$string .= " ".$integer_part;

		    if($prefix)
		    	$string = $prefix . " " . $string;

	        if($fraction > 0){
		        $string .= $decimal;
		        $words = array();
		        foreach (str_split((string) $fraction) as $number) {
		            $words[] = $dictionary[$number];
		        }
		        $string .= implode(' ', $words);

		        // concating fractional part
		        $string .= " ".$fractional_part;

		        if($postfix)	
			    	$string .= " ".$postfix;
	        }else{
	        	if($postfix)	
			    	$string .= " ".$postfix;
	        }
	    }


	    return $string;
	}
}
