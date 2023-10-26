<?php
declare(strict_types=1);
namespace Lay\orm\traits;

use Lay\orm\SQL;

trait Clean {
    protected static array $stock_escape_string = ["%3D","%21","%2B","%40","%23","%24","%25","%5E","%26","%2A","%28","%29","%27",
        "%22","%3A","%3B","%3C","%3D","%3E","%3F","%2F","%5C","%7C","%60","%2C","_","-","–","%0A","%E2","%80","%99","%E2%80%98","%E2%80%99"];
    protected static array $escape_string = [];
    /**
     * ## Clean variables for SQL or generally
     * @param string|int|float $value string value to be cleansed
     * @param float $level__combo <table><tr><th>BASE FUNCTIONS</th></tr>
     * <tr><td>0</td><td>real_escape_string[<b>default</b>]</td></tr><tr><td>1</td><td>strip_tags</td></tr>
     * <tr><td>2</td><td>trim</td></tr><tr><td>3</td><td>htmlspecialchars</td></tr>
     * <tr><td>4</td><td>rawurlencode</td></tr><tr><td>5</td><td>str_replace</td></tr>
     * <tr><td>6</td><td>url_beautify</td></tr>
     * <tr><th>LEVEL MODE</th></tr>
     * <tr><td>Double</td><td>[10=1 & 0] [11= 2 & 0] [12=0 & 3] [13=3 & 1] [14=1 & 2] [15=3 & 2]</td></tr>
     * <tr><td>Multiple</td><td>[16=1 & 2 & 0] [17=3 & 2 & 0] [18=1 && 3 && 0] [19=1 && 3 && 2] [20=1,3,2,0]</td></tr>
     * <tr><th>COMBO MODE</th><td>Dev has the freedom of combining cleansing independently, but each number must appear once
     * and cleansing happens from left-to-right</td></tr>
     * </table>
     * @param array $options Optional settings required by cleansing agents and to also change cleansing core<br>
     * <em>['flags | flag' => ENT_QUOTES,'allowed | tags | allowed_tags'=> '<br><div>','core'=>"combo" | 'combo'=>1]</em>
     * <em>Passing "combo" as a value will work in place of the "core" key</em>
     * <div><b>As of 2.0.1, the `clean` function ignores passing empty value to the "value" argument except "strict" is
     * passed in the option array as a value</b></div>
     * pass an int value of 1 to the function to debug it
     * @return mixed
     */
    public function clean(mixed $value, float $level__combo = 0, ...$options): mixed {
        // perquisite
        $core = SQL::new();
        $link = $core->get_link();

        $options = $core->array_flatten($options);
        $flags = $options['flag'] ?? $options['flags'] ?? ENT_QUOTES;
        $allowedTags = $options['allowed'] ?? $options['tags'] ?? $options['allowed_tags'] ?? "";
        // this condition is meant for the $find variable when handling url_beautify
        if(count(self::$escape_string) == 0) self::$escape_string = self::$stock_escape_string;
        if($level__combo == 6 && !in_array('ignore_preset',$options,true)){
            $this->reset_escape_string();
            $this->add_escape_string("/","\\","\"","#","|","^","*","~","!","$","@","%","`",';', ':', '=','<',
                '>',"»"," ","%20","?","'",'"',"(",")","[","]",".",",");
        }
        $find = $options['search'] ?? $options['find'] ?? self::$escape_string;
        $replace = $options['replace'] ?? $options['put'] ?? "";

        $esc_str = function ($mode,$value) use($link){
            // Extra layer of security for escape string
            if($mode == "strict" || $mode == "PREVENT_SQL_INJECTION") {
                $keyWords = ["SELECT","INSERT","DELETE","UPDATE","CREATE","DROP","SHOW","USE","DESCRIBE","DESC","ALTER","UNION","INFORMATION_SCHEMA"];
                $keyWords = array_merge($keyWords, array_map("strtolower", $keyWords));
                $value = str_replace($keyWords, array_map(fn($x) => mysqli_real_escape_string($link,$x), $keyWords), $value);
            }

            return mysqli_real_escape_string($link,$value);
        };
        // parse value
        $value = filter_var($value,FILTER_VALIDATE_INT) ? (int) $value : (filter_var($value,FILTER_VALIDATE_FLOAT) ? (float) $value : $value);
        // core && difficulty
        $mode = $options['core'] ?? "level";
        $difficulty = "loose";
        if(in_array("combo",$options,true)) $mode = "combo";
        if(in_array("strict",$options,true) || in_array("!",$options,true)) $difficulty = "strict";
        // debug
        if(in_array(1,$options,true)) $this->exceptions(1,["value" => $value,"combo" => $level__combo, "opts" => $options]);
        // check mate
        if (($value === "" || $value === null) && !is_numeric($value) && !is_object($value) && $difficulty == "strict") $this->exceptions(2);
        elseif (empty($value) && $difficulty == "loose") return $value;
        if(!is_string($value) && !is_numeric($value) && !is_object($value)) $this->exceptions(3, ["value" => $value]);
        if(is_numeric($value)) return $value;
        if(is_object($value)) $value = json_encode($value);
        // function
        $func = [
            /*0*/ fn ($val=null) => $esc_str($difficulty,$val ?? $value),
            /*1*/ fn ($val=null) => strip_tags((string) ($val ?? $value),$allowedTags),
            /*2*/ fn ($val=null) => trim($val ?? $value),
            /*3*/ fn ($val=null) => htmlspecialchars($val ?? $value,$flags),
            /*4*/ fn ($val=null) => rawurlencode($val ?? $value),
            /*5*/ fn ($val=null) => str_replace($find,$replace,$val ?? $value),
            /*6*/ function ($val=null) use ($find,$value) {
                    rsort($find);
                    return preg_replace("/^-/","",preg_replace("/-$/","",strtolower(preg_replace("/--+/","-", str_replace($find,"-",rawurlencode(trim($val ?? $value)))))));
                }
        ];

        $permute = function ($combination,$value) use ($func) {
            foreach ($combination as $combo) { $value = $func[$combo]($value); } return $value;
        };
        // cleansing
        if(isset($options['combo']) OR $mode == "combo") {
            if (extension_loaded('mbstring'))
                $combine = mb_str_split("$level__combo");
            else
                $combine = str_split("$level__combo");
            if (count($combine) !== count(array_unique($combine))) $this->exceptions(0,["combo" => $level__combo]);
            $value = $permute($combine,$value);
        }
        else{

            if((($level__combo + 1) > count($func)) && is_float($level__combo)) {
                switch ($level__combo){
                    case 0.1:case 10: $combine = [1,0]; break;
                    case 0.2:case 11: $combine = [2,0]; break;
                    case 0.3:case 12: $combine = [0,3]; break;
                    case 1.3:case 13: $combine = [3,1]; break;
                    case 1.2:case 14: $combine = [1,2]; break;
                    case 2.3:case 15: $combine = [3,2]; break;
                    case 0.12:case 16: $combine = [1,2,0]; break;
                    case 0.23:case 17: $combine = [3,2,0]; break;
                    case 0.13:case 18: $combine = [1,3,0]; break;
                    case 1.23:case 19: $combine = [1,3,2]; break;
                    case 0.123:case 20: $combine = [1,3,2,0]; break;
                    default: $this->exceptions(0, ["combo" => $level__combo]); break;}
                $value = $permute($combine,$value);
            }
            else $value = $func[$level__combo]();
        }
        return $value;
    }
    public function clean_multi(int $level,...$values) : array {
        $return = [];
        for ($i = 0; $i < count($values); $i++){
            $return[] = $this->clean($values[$i], $level);
        }
        return $return;
    }
    public function add_escape_string(...$escape_string) : void {
        if(count(self::$escape_string) == 0) self::$escape_string = self::$stock_escape_string;
        self::$escape_string = array_merge(self::$escape_string, SQL::new()->array_flatten($escape_string));
    }
    public function get_escape_string() : array { return self::$escape_string; }
    public function reset_escape_string() : self
    {
        self::$escape_string = self::$stock_escape_string;
        return $this;
    }

    private function exceptions(int $level, array $args = []) : void {
        $option = [];
        switch ($level){
            default:
                $title = "Clean::Err";
                $body = "No Valid Cleanse Combo or Level passed!<br> <b>COMBO_RANGE = 0 - 6</b> <br><br>
                    <b>DEFAULT_COMBO_VALUES = [10-20]</b> <br>  EXPECTED: <br>
                    <b>[COMBO MODE]</b> single value from COMBO_RANGE or unique comma (,) separated combination of COMBO_RANGE; <br>
                    <b>[LEVEL MODE]</b> DEFAULT_COMBO_VALUES or unique float combination of COMBO_RANGE; <br>
                    Got <b>__RAW_VALUE_TYPE__</b> as Level or Combo";
                $option = [
                    "__RAW_VALUE_TYPE__" => $args['combo']
                ];
            break;
            case 1 :
                $title = "Clean::Dbg";
                $body = "<b>Value:</b> __RAW_VALUE__<br> <b>Level/Combo:</b> __RAW_VALUE_TYPE__<br> <b>Extra:</b> __THIRD_OPT__";
                $option = [
                    "__RAW_VALUE__" => $args['value'],
                    "__RAW_VALUE_TYPE__" => $args['combo'],
                    "__THIRD_OPT__" => $args['opts'],
                ];
            break;
            case 2 :
                $title = "Clean::Err";
                $body = "No value passed!<br> <em>An empty string cannot be cleaned</em>";
            break;
            case 3:
                $title = "Clean::Err";
                $body = "A Non-String Value was encountered! __RAW_VALUE__";
                $option = [
                    "__RAW_VALUE__" => $args['value'],
                ];
            break;

        }

        $this->use_exception($title,$body,raw: $option);
    }
}
