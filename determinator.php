<?php
require_once "colours.php";

class Determinator
{
    public $values = array();
    public $narrow = array();
    public $points = array();
    public $ratings = array();
    public $main_rating = 0;
    public $main = null;
    public $binary = null;

    function __construct($file)
    {
        $this->colours = getColours($file);
        $this->machine = new ColourMachine($this->colours);
        $this->values = $this->machine->coloursHSL;
        $this->main = $this->values[0];

        array_shift($this->values);
        $this->narrow = $this->values;

        for($i = 0; $i<count($this->narrow);$i++)
        {
            $this->points[$i] = 0;
            $this->ratings[$i] = 0;
        }

        $this->binary = $this->getContrast50($this->hexDisplay($this->main));

        $this->process();
    }

    private function getContrast50($hexcolor){
        return (hexdec($hexcolor) > 0xffffff/2) ? array(0,0,0):array(0,0,100);
    }

    private function process()
    {
        $this->saturationDifference();
        $this->luminanceDifference();
        $this->lumSat();
        $this->brightness();
        $this->differenceCheck();
        $this->zeroSaturation();
        $this->strongSaturation();
        $this->colourRating();
        
        $max = max($this->points);

        if($max<=3)
        {
            $this->saturationRange();
        }
        $this->ignoreBlack();

        $maxs = array_keys($this->points, max($this->points));

        if($this->ratings[$maxs[0]]<=0.12)
        {
            $this->brightnessDifferentiate();

            $maxs = array_keys($this->points, max($this->points));

            if(count($maxs)>6)
            {
                $this->differentiate();
            }
        }

        $this->colourMonotone();
        $this->eradicateDark();
        $this->eradicateLight();
        $this->brightness();


        $this->accent = $this->getMax();
        $this->dominant = $this->main;
    }

    private function eradicateLight()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        $sum = $main_s + $main_l;

        if($sum>=80&&$sum<=120)
        {
            $maxs = array_keys($this->points, max($this->points));

            list($h,$s,$l) = $this->narrow[$maxs[0]];
            $sum = $s + $l;

            if($sum>=80&&$sum<=120)
            {
                $this->points[$maxs[0]] -= 2;
            }
        }
    }

    private function eradicateDark()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        $avg = array_sum(array($main_s,$main_l)) / 2;

        $large = 0;
        $largeKey = -1;

        if($avg<40)
        {
            foreach($this->narrow as $k => $v)
            {
                list($h,$s,$l) = $v;

                $comb = $s + $l;

                if($comb>$large)
                {
                    $largeKey = $k;
                    $large = $comb;
                }
            }

            $this->points[$largeKey] += 2;
        }
    }

    private function colourMonotone()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        if($main_s>40)
        {
            foreach($this->narrow as $k => $v)
            {
                list($h,$s,$l) = $v;

                if($h<=($main_h+5)&&$h>=($main_h-5))
                {
                    if($l<($main_l - 8))
                    {
                        $this->points[$k] += 3;
                    }
                }
            }
        }
    }

    private function saturationDifference()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        foreach($this->narrow as $k => $v)
        {
            list($h,$s,$l) = $v;

            $diff = abs($main_s - $s);

            if($diff>=20&&$diff<=30)
            {
                $this->points[$k] += 2;
            }
            else if($diff>=10&&$diff<=40)
            {
                $this->points[$k] += 1;
            }
        }
    }

    private function luminanceDifference()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        foreach($this->narrow as $k => $v)
        {
            list($h,$s,$l) = $v;

            $diff = abs($main_l - $l);

            if($main_l<=50)
            {
                if($diff>=65&&$diff<=75)
                {
                    $this->points[$k] += 2;
                }
                else if($diff>=55&&$diff<=85)
                {
                    $this->points[$k] += 1;
                }
            }
            else
            {
                if($diff>=35&&$diff<=45)
                {
                    $this->points[$k] += 2;
                }
                else if($diff>=25&&$diff<=55)
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function lumSat()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        foreach($this->narrow as $k => $v)
        {
            list($h,$s,$l) = $v;

            $diff = abs($main_l - $s);


            if($main_l<50)
            {
                if($diff>80)
                {
                    $this->points[$k] += 1;
                }
            }
            else
            {
                if($diff<60&&$diff>40)
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function brightness()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        foreach($this->narrow as $k => $v)
        {
            list($h,$s,$l) = $v;

            $alter = 20;

            if($main_l<=20&&$l>=$main_l+$alter)
            {
                $this->points[$k] += 1;
            }
            else if($main_l>=80&&$l<=$main_l-$alter)
            {
                $this->points[$k] += 1;
            }
        }
    }

    private function differenceCheck()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        $main_diff = abs($main_s - $main_l);

        if($main_diff<40)
        {
            foreach($this->narrow as $k => $v)
            {
                list($h,$s,$l) = $v;

                $diff = abs($s - $l);

                if($diff<30)
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function zeroSaturation()
    {
        list($main_h,$main_s,$main_l) = $this->main;
        $main_h_perc = ($main_h / 360)*100;

        if($main_s==0)
        {
            foreach($this->narrow as $k => $v)
            {
                list($h,$s,$l) = $v;

                if($s>0)
                {
                    $this->points[$k] += 1;
                }

                if($h==$main_h)
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function strongSaturation()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        if($main_s>=90)
        {
            foreach($this->narrow as $k => $v)
            {
                list($h,$s,$l) = $v;

                if($s<=90)
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function ignoreBlack()
    {
        $maxs = array_keys($this->points, max($this->points));

        if(count($maxs)>1)
        {
            foreach($maxs as $k => $v)
            {
                list($h,$s,$l) = $this->narrow[$v];
                if($h==0&&$s==0&&$l==0)
                {
                    $this->points[$v] = 0;
                }
            }
        }
    }

    private function saturationRange()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        $alter = 15;

        if($main_s<=100 - $alter)
        {
            foreach($this->narrow as $k => $v)
            {
                list($h,$s,$l) = $v;

                if($s<=$main_s+($alter/2)&&$s>=$main_s-($alter/2))
                {
                    $this->points[$k] += 2;
                }
                else if($s<=$main_s+$alter&&$s>=$main_s-$alter)
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function colourRating()
    {
        list($main_h,$main_s,$main_l) = $this->main;

        $main_h = $this->map($main_h,0,360,0,1);
        $main_s = $this->map($main_s,0,100,0,1);
        $main_l = $this->map($main_l,0,100,0,1);

        $this->main_rating = round(array_sum(array($main_h,$main_s,$main_l)) / 3,2);

        $alter = 15;

        if($main_s<=100 - $alter)
        {
            foreach($this->narrow as $k => $v)
            {
                list($h,$s,$l) = $v;

                $h = $this->map($h,0,360,0,1);
                $s = $this->map($s,0,100,0,1);
                $l = $this->map($l,0,100,0,1);

                $this->ratings[$k] = abs($this->main_rating - (round(array_sum(array($h,$s,$l)) / 3,2)));

                if($s<=$main_s+($alter/2)&&$s>=$main_s-($alter/2))
                {
                    $this->points[$k] += 2;
                }
                else if($s<=$main_s+$alter&&$s>=$main_s-$alter)
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function differentiate()
    {
        $vals = $this->points;
        arsort($vals);

        foreach($vals as $k => $v)
        {
            if($this->ratings[$k]>0.12)
            {
                $this->points[$k] += 2;

                $thisL = $this->narrow[$k][2];
                $mainL = $this->main[2];

                if($thisL<=($mainL+5)&&$thisL>=($mainL-5))
                {
                    $this->points[$k] += 1;
                }
            }
        }
    }

    private function brightnessDifferentiate()
    {
        list($main_h,$main_s,$main_l) = $this->main;
        list($h,$s,$l) = $this->getMax();
        $key = array_keys($this->points, max($this->points));
        $key = $key[0];

        $alter = 15;

        if($l <= ($main_l + $alter) && $l >= ($main_l - $alter))
        {
            $this->points[$key] -= 2;
        }
    }

    public function map($value, $fromLow, $fromHigh, $toLow, $toHigh) {
        $fromRange = $fromHigh - $fromLow;
        $toRange = $toHigh - $toLow;
        $scaleFactor = $toRange / $fromRange;

        $tmpValue = $value - $fromLow;
        $tmpValue *= $scaleFactor;
        return $tmpValue + $toLow;
    }

    public function getMax()
    {
        $maxs = array_keys($this->points, max($this->points));

        return $this->narrow[$maxs[0]];
    }

    public function renderHSL($v)
    {
        $str  = "hsl(".$v[0].",".$v[1]."%,".$v[2]."%)";

        return $str;
    }

    public function hslToRgb($h, $s, $l)
    {
       $r;
       $g;
       $b;

       $h /= 360;
       $s /= 100;
       $l /= 100;

        if($s == 0)
        {
            $r = $g = $b = $l; // achromatic
        }
        else
        {

            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue2rgb($p, $q, $h + 1/3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1/3);
        }

        return array(round($r * 255), round($g * 255), round($b * 255));
    }

    public function hexDisplay($hsl){

        $rgb = $this->hslToRgb($hsl[0],$hsl[1],$hsl[2]);
        list($r,$g,$b) = $rgb;

        $r=dechex($r);
        if(strlen($r)<2)
            $r='0'.$r;

        $g=dechex($g);
        if(strlen($g)<2)
            $g='0'.$g;

        $b=dechex($b);
        if(strlen($b)<2)
            $b='0'.$b;

        return '#' . $r . $g . $b;
    }

    private function hue2rgb($p, $q, $t)
    {
        if($t < 0) $t += 1;
        if($t > 1) $t -= 1;
        if($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if($t < 1/2) return $q;
        if($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    public function render($type,$loc,$opac = false)
    {
        switch($type)
        {
            case "bg":
                if($opac)
                {
                    $target = $this->{$loc};
                    $rgb = $this->hslToRgb($target[0],$target[1],$target[2]);
                    echo "background-color:rgba(".$rgb[0].",".$rgb[1].",".$rgb[2].",".$opac.");";
                }
                else
                {
                    echo "background-color:".$this->hexDisplay($this->{$loc}).";";
                }
                break;
            case "colour":
                echo "color:".$this->hexDisplay($this->{$loc}).";";
                break;
            case "data":
                echo 'data-accent="'.$this->hexDisplay($this->accent).'" data-dominant="'.$this->hexDisplay($this->main).'" data-binary="'.$this->hexDisplay($this->binary).'" data-hsl="'.$this->renderHSL($this->main).'"';
                break;
        }
    }
    public function getRender($type,$loc,$opac = false)
    {
        switch($type)
        {
            case "bg":
                if($opac)
                {
                    $target = $this->{$loc};
                    $rgb = $this->hslToRgb($target[0],$target[1],$target[2]);
                    return "background-color:rgba(".$rgb[0].",".$rgb[1].",".$rgb[2].",".$opac.");";
                }
                else
                {
                    return "background-color:".$this->hexDisplay($this->{$loc}).";";
                }
                break;
            case "colour":
                return "color:".$this->hexDisplay($this->{$loc}).";";
                break;
            case "data":
                return 'data-accent="'.$this->hexDisplay($this->accent).'" data-dominant="'.$this->hexDisplay($this->main).'" data-binary="'.$this->hexDisplay($this->binary).'" data-hsl="'.$this->renderHSL($this->main).'"';
                break;
        }
    }
};
