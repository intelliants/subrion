<?php

class iaMetaKeywords
{
    //minimum word length for inclusion into the single word
    protected $wordLengthMin = 5;
    protected $wordOccuredMin = 2;

    //minimum word length for inclusion into the 2 word
    protected $word2WordPhraseLengthMin = 3;
    protected $phrase2WordLengthMinOccur = 2;

    //minimum word length for inclusion into the 3 word
    protected $word3WordPhraseLengthMin = 3;
    protected $phrase2WordLengthMin = 10;
    protected $phrase3WordLengthMinOccur = 2;
    //minimum phrase length for inclusion into the 3 word
    protected $phrase3WordLengthMin = 3;

    protected $_stopWords = [
    'able', 'about', 'above', 'act', 'add', 'afraid', 'after', 'again', 'against', 'age', 'ago', 'agree', 'all',
    'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'amount', 'an', 'and', 'anger',
    'angry', 'animal', 'another', 'answer', 'any', 'appear', 'apple', 'are', 'arrive', 'arm', 'arms', 'around',
    'arrive', 'as', 'ask', 'at', 'attempt', 'aunt', 'away', 'back', 'bad', 'bag', 'bay', 'be', 'became', 'because',
    'become', 'been', 'before', 'began', 'begin', 'behind', 'being', 'bell', 'belong', 'below', 'beside', 'best',
    'better', 'between', 'beyond', 'big', 'body', 'bone', 'born', 'borrow', 'both', 'bottom', 'box', 'boy', 'break',
    'bring', 'brought', 'bug', 'built', 'busy', 'but', 'buy', 'by', 'call', 'came', 'can', 'cause', 'choose',
    'close', 'close', 'consider', 'come', 'consider', 'considerable', 'contain', 'continue', 'could', 'cry', 'cut',
    'dare', 'dark', 'deal', 'dear', 'decide', 'deep', 'did', 'die', 'do', 'does', 'dog', 'done', 'doubt', 'down',
    'during', 'each', 'ear', 'early', 'eat', 'effort', 'either', 'else', 'end', 'enjoy', 'enough', 'enter', 'even',
    'ever', 'every', 'except', 'expect', 'explain', 'fail', 'fall', 'far', 'fat', 'favor', 'fear', 'feel', 'feet',
    'fell', 'felt', 'few', 'fill', 'find', 'fit', 'fly', 'follow', 'for', 'forever', 'forget', 'from', 'front',
    'gave', 'get', 'gives', 'goes', 'gone', 'good', 'got', 'gray', 'great', 'green', 'grew', 'grow', 'guess', 'had',
    'half', 'hang', 'happen', 'has', 'hat', 'have', 'he', 'hear', 'heard', 'held', 'hello', 'help', 'her', 'here',
    'hers', 'high', 'hill', 'him', 'his', 'hit', 'hold', 'hot', 'how', 'however', 'I', 'if', 'ill', 'in', 'indeed',
    'instead', 'into', 'iron', 'is', 'it', 'its', 'just', 'keep', 'kept', 'knew', 'know', 'known', 'late', 'least',
    'led', 'left', 'lend', 'less', 'let', 'like', 'likely', 'likr', 'lone', 'long', 'look', 'lot', 'make', 'many',
    'may', 'me', 'mean', 'met', 'might', 'mile', 'mine', 'moon', 'more', 'most', 'move', 'much', 'must', 'my',
    'near', 'nearly', 'necessary', 'neither', 'never', 'next', 'no', 'none', 'nor', 'not', 'note', 'nothing', 'now',
    'number', 'of', 'off', 'often', 'oh', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'out', 'please',
    'prepare', 'probable', 'pull', 'pure', 'push', 'put', 'raise', 'ran', 'rather', 'reach', 'realize', 'reply',
    'require', 'rest', 'run', 'said', 'same', 'sat', 'saw', 'say', 'see', 'seem', 'seen', 'self', 'sell', 'sent',
    'separate', 'set', 'shall', 'she', 'should', 'side', 'sign', 'since', 'so', 'sold', 'some', 'soon', 'sorry',
    'stay', 'step', 'stick', 'still', 'stood', 'such', 'sudden', 'suppose', 'take', 'taken', 'talk', 'tall', 'tell',
    'ten', 'than', 'thank', 'that', 'the', 'their', 'them', 'then', 'there', 'therefore', 'these', 'they', 'this',
    'those', 'though', 'through', 'till', 'to', 'today', 'told', 'tomorrow', 'too', 'took', 'tore', 'tought',
    'toward', 'tried', 'tries', 'trust', 'try', 'turn', 'two', 'under', 'until', 'up', 'upon', 'us', 'use', 'usual',
    'various', 'verb', 'very', 'visit', 'want', 'was', 'we', 'well', 'went', 'were', 'what', 'when', 'where',
    'whether', 'which', 'while', 'white', 'who', 'whom', 'whose', 'why', 'will', 'with', 'within', 'without',
    'would', 'yes', 'yet', 'you', 'young', 'your', 'br', 'img', 'p','lt', 'gt', 'quot', 'copy'
    ];


    public function get($text)
    {
        $content = explode(' ', $this->_replaceChars($text));
        $keywords = $this->_parseWords($content)
         .$this->_parse2Words($content)
         .$this->_parse3Words($content);

        return substr($keywords, 0, -2);
    }

    protected function _replaceChars($content)
    {
        $content = strip_tags(mb_strtolower($content));

        $punctuations = [
            ',', ')', '(', '.', "'", '"',
        '<', '>', '!', '?', '/', '-',
        '_', '[', ']', ':', '+', '=', '#',
        '$', '&quot;', '&copy;', '&gt;', '&lt;',
        '&nbsp;', '&trade;', '&reg;', ';',
        chr(10), chr(13), chr(9)
        ];

        $content = str_replace($punctuations, ' ', $content);
        $content = preg_replace('# {2,}#si', ' ', $content);

        return $content;
    }

    protected function _parseWords($text)
    {
        $s = $text;
        $k = [];

        foreach ($s as $key => $val) {
            if (mb_strlen(trim($val)) >= $this->wordLengthMin
                && !in_array(trim($val), $this->_stopWords)
                && !is_numeric(trim($val))
            ) {
                $k[] = trim($val);
            }
        }

        $k = array_count_values($k);
        if (empty($k)) {
            return;
        }

        $filtered = $this->_chunkFilter($k, $this->wordOccuredMin);
        arsort($filtered);

        $imploded = $this->_implodeArray($filtered);

        unset($k, $s);

        return $imploded;
    }

    protected function _parse2Words($text)
    {
        $x = $text;

        for ($i=0; $i < count($x)-1; $i++) {
            //delete phrases lesser than 5 characters
            if ((mb_strlen(trim($x[$i])) >= $this->word2WordPhraseLengthMin)
                && (mb_strlen(trim($x[$i+1])) >= $this->word2WordPhraseLengthMin)
            ) {
                $y[] = trim($x[$i]).' '.trim($x[$i+1]);
            }
        }

        //count the 2 word phrases
        $y = array_count_values($y);
        if (empty($k)) {
            return;
        }

        $filtered = $this->_chunkFilter($y, $this->phrase2WordLengthMinOccur);
        arsort($filtered);

        $imploded = $this->_implodeArray($filtered);

        unset($x, $y);

        return $imploded;
    }

    protected function _parse3Words($text)
    {
        $a = $text;
        $b = [];

        for ($i = 0; $i < count($a) - 2; $i++) {
            if ((mb_strlen(trim($a[$i])) >= $this->word3WordPhraseLengthMin)
                && (mb_strlen(trim($a[$i+1])) > $this->word3WordPhraseLengthMin)
                && (mb_strlen(trim($a[$i+2])) > $this->word3WordPhraseLengthMin)
                && (mb_strlen(trim($a[$i]).trim($a[$i+1]).trim($a[$i+2])) > $this->phrase3WordLengthMin)
            ) {
                $b[] = trim($a[$i]).' '.trim($a[$i+1]).' '.trim($a[$i+2]);
            }
        }

        $b = array_count_values($b);
        if (empty($k)) {
            return;
        }

        $filtered = $this->_chunkFilter($b, $this->phrase3WordLengthMinOccur);
        arsort($filtered);
        $imploded = $this->_implodeArray($filtered);

        unset($a, $b);

        return $imploded;
    }

    protected function _chunkFilter(array $countValues, $minOccur)
    {
        $filtered = [];
        foreach ($countValues as $word => $occured) {
            if ($occured >= $minOccur) {
                $filtered[$word] = $occured;
            }
        }

        return $filtered;
    }

    protected function _implodeArray($array, $glue = ', ')
    {
        $result = '';
        foreach ($array as $key => $val) {
            $result .= $key . $glue;
        }

        return $result;
    }
}
