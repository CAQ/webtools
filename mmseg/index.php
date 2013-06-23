<?php
// the input parameter is "t"
// if no such parameter provided, return 400 error
if (!isset($_REQUEST['t'])) {
  header("HTTP/1.0 400 Bad Request", true, 400);
  echo "Error: Parameter <i>t</i> is missing.";
  echo "<p><i>t</i> should be either (1) a plain string, (2) a quoted (\") JSON string, or (3) an array of strings in JSON format.<br/>";
  echo "Optional parameters:<br/>";
  echo "<i>detail</i> is for additional info: offset and length of each word.<br/>";
  echo "<i>removestop</i> is for removing stop words.</p>";
  echo "Examples:<ul>";
  echo "<li>Request: GET / POST mmseg/?t=南京市长江大桥<br/>or /mmseg/?t=%E5%8D%97%E4%BA%AC%E5%B8%82%E9%95%BF%E6%B1%9F%E5%A4%A7%E6%A1%A5<br/>Response: [\"南京\", \"市\", \"长江大桥\"]</li>";
  echo "<li>Request: mmseg/?t=\"新加坡国立大学\"&detail<br/>or /mmseg/?t=%22%E6%96%B0%E5%8A%A0%E5%9D%A1%E5%9B%BD%E7%AB%8B%E5%A4%A7%E5%AD%A6%22&detail<br/>Response: [{\"text\":\"新加坡\",\"offset\":0,\"length\":9},{\"text\":\"国立大学\",\"offset\":9,\"length\":12}]</li>";
  echo "<li>Request: mmseg/?t=[\"你可以输入一个数组\",\"批量输入的效率会高一些\"]&removestop<br/>or /mmseg/?t=%5B%22%E4%BD%A0%E5%8F%AF%E4%BB%A5%E8%BE%93%E5%85%A5%E4%B8%80%E4%B8%AA%E6%95%B0%E7%BB%84%22%2C%22%E6%89%B9%E9%87%8F%E8%BE%93%E5%85%A5%E7%9A%84%E6%95%88%E7%8E%87%E4%BC%9A%E9%AB%98%E4%B8%80%E4%BA%9B%22%5D&removestop<br/>Response: [[\"你可以\",\"输入\",\"一个\",\"数组\"],[\"批量\",\"输入\",\"效率\",\"会\",\"高一些\"]]</li>";
  echo "</ul>";
  die();
}

$text = $_REQUEST['t'];
$obj = $text;

// now try to parse it in json format
$obj = json_decode($text);
// check if json has error
$code = 406; // default is error
$msg = '';
switch (json_last_error()) {
  case JSON_ERROR_NONE:
    $code = 200;
    break;
  case JSON_ERROR_DEPTH:
    $msg = ' - Maximum stack depth exceeded';
    break;
  case JSON_ERROR_STATE_MISMATCH:
    $msg = ' - Underflow or the modes mismatch';
    break;
  case JSON_ERROR_CTRL_CHAR:
    $msg = ' - Unexpected control character found';
    break;
  case JSON_ERROR_SYNTAX:
    $msg = ' - Syntax error, malformed JSON';
    break;
  case JSON_ERROR_UTF8:
    $msg = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
    break;
  default:
    $msg = ' - Unknown error';
    break;
}
if ($code == 406) {
  // if parse error, considering it as a plain textj
  /* header("HTTP/1.0 406 Not Acceptable", false, 406);
  die('JSON decode error' . $msg);*/
  $obj = $text;
}

// detail information (offset and length)?
$detail = isset($_REQUEST['detail']);
// remove stop words?
$rmstop = isset($_REQUEST['removestop']);

$zhstopwords = array(
  "?", "、", "。", "“", "”", "《", "》", "！", "，", "：", "；", 
  "？", "啊", "阿", "哎", "哎呀", "哎哟", "唉", "俺", "俺们", 
  "按", "按照", "吧", "吧哒", "把", "罢了", "被", "本", "本着", 
  "比", "比方", "比如", "鄙人", "彼", "彼此", "边", "别", "别的", 
  "别说", "并", "并且", "不比", "不成", "不单", "不但", "不独", 
  "不管", "不光", "不过", "不仅", "不拘", "不论", "不怕", "不然", 
  "不如", "不特", "不惟", "不问", "不只", "朝", "朝着", "趁", 
  "趁着", "乘", "冲", "除", "除此之外", "除非", "除了", "此", 
  "此间", "此外", "从", "从而", "打", "待", "但", "但是", "当", 
  "当着", "到", "得", "的", "的话", "等", "等等", "地", "第", 
  "叮咚", "对", "对于", "多", "多少", "而", "而况", "而且", 
  "而是", "而外", "而言", "而已", "尔后", "反过来", "反过来说", 
  "反之", "非但", "非徒", "否则", "嘎", "嘎登", "该", "赶", "个", 
  "各", "各个", "各位", "各种", "各自", "给", "根据", "跟", "故", 
  "故此", "固然", "关于", "管", "归", "果然", "果真", "过", "哈", 
  "哈哈", "呵", "和", "何", "何处", "何况", "何时", "嘿", "哼", 
  "哼唷", "呼哧", "乎", "哗", "还是", "还有", "换句话说", "换言之", 
  "或", "或是", "或者", "极了", "及", "及其", "及至", "即", "即便", 
  "即或", "即令", "即若", "即使", "几", "几时", "己", "既", "既然", 
  "既是", "继而", "加之", "假如", "假若", "假使", "鉴于", "将", 
  "较", "较之", "叫", "接着", "结果", "借", "紧接着", "进而", 
  "尽", "尽管", "经", "经过", "就", "就是", "就是说", "据", 
  "具体地说", "具体说来", "开始", "开外", "靠", "咳", "可", "可见", 
  "可是", "可以", "况且", "啦", "来", "来着", "离", "例如", "哩", 
  "连", "连同", "两者", "了", "临", "另", "另外", "另一方面", "论", 
  "嘛", "吗", "慢说", "漫说", "冒", "么", "每", "每当", "们", "莫若", 
  "某", "某个", "某些", "拿", "哪", "哪边", "哪儿", "哪个", "哪里", 
  "哪年", "哪怕", "哪天", "哪些", "哪样", "那", "那边", "那儿", 
  "那个", "那会儿", "那里", "那么", "那么些", "那么样", "那时", 
  "那些", "那样", "乃", "乃至", "呢", "能", "你", "你>们", "您", 
  "宁", "宁可", "宁肯", "宁愿", "哦", "呕", "啪达", "旁人", "呸", 
  "凭", "凭借", "其", "其次", "其二", "其他", "其它", "其一", "其余", 
  "其中", "起", "起见", "起见", "岂但", "恰恰相反", "前后", "前者", 
  "且", "然而", "然后", "然则", "让", "人家", "任", "任何", "任凭", 
  "如", "如此", "如果", "如何", "如其", "如若", "如上所述", "若", 
  "若非", "若是", "啥", "上下", "尚且", "设若", "设使", "甚而", 
  "甚么", "甚至", "省得", "时候", "什么", "什么样", "使得", "是", 
  "是的", "首先", "谁", "谁知", "顺", "顺着", "似的", "虽", "虽然", 
  "虽说", "虽则", "随", "随着", "所", "所以", "他", "他们", "他人", 
  "它", "它们", "她", "她们", "倘", "倘或", "倘然", "倘若", "倘使", 
  "腾", "替", "通过", "同", "同时", "哇", "万一", "往", "望", "为", 
  "为何", "为了", "为什么", "为着", "喂", "嗡嗡", "我", "我们", "呜", 
  "呜呼", "乌乎", "无论", "无宁", "毋宁", "嘻", "吓", "相对而言", 
  "像", "向", "向着", "嘘", "呀", "焉", "沿", "沿着", "要", "要不", 
  "要不然", "要不是", "要么", "要是", "也", "也罢", "也好", "一", 
  "一般", "一旦", "一方面", "一来", "一切", "一样", "一则", "依", 
  "依照", "矣", "以", "以便", "以及", "以免", "以至", "以至于", 
  "以致", "抑或", "因", "因此", "因而", "因为", "哟", "用", "由", 
  "由此可见", "由于", "有", "有的", "有关", "有些", "又", "于", 
  "于是", "于是乎", "与", "与此同时", "与否", "与其", "越是", 
  "云云", "哉", "再说", "再者", "在", "在下", "咱", "咱们", "则", 
  "怎", "怎么", "怎么办", "怎么样", "怎样", "咋", "照", "照着", 
  "者", "这", "这边", "这儿", "这个", "这会儿", "这就是说", "这里", 
  "这么", "这么点儿", "这么些", "这么样", "这时", "这些", "这样", 
  "正如", "吱", "之", "之类", "之所以", "之一", "只是", "只限", 
  "只要", "只有", "至", "至于", "诸位", "着", "着呢", "自", "自从", 
  "自个儿", "自各儿", "自己", "自家", "自身", "综上所述", "总的来看", 
  "总的来说", "总的说来", "总而言之", "总之", "纵", "纵令", "纵然", 
  "纵使", "遵照", "作为", "兮", "呃", "呗", "咚", "咦", "喏", "啐", 
  "喔唷", "嗬", "嗯", "嗳", "•",
  "的", "是", "我", "不", "你", "了", "有", "一", "在", "人", "了", "就", "好", "都", "也", "要", "这",
  "会", "个", "啊", "和", "还", "说", "很", "能", "看", "来", "我们", "吧", "去", "到", "自己", "大", "他", "想", "一个", "上",
  "最", "着", "对", "可以", "让", "给", "中", "这个", "爱", "被", "做", "什么", "吗",
  "不是", "你的", "就是", "微博", "我的", "都是", "一个", "这是", "不会", "自己的", "不能",
  "是一", "也是", "一种", "我也", "好的", "真的", "是不", "还是", "也不", "我是", "一天", "不知道", "是我", "人的",
  "有一", "只是", "我不", "一次", "是你", "一个人", "个人", "你是", "都不", "这样的", "不要", "我们的", "我在", "两个",
  "是个", "他的", "更多", "快来", "你不", "给我", "说的", "是一个", "就不", "大的", "我就",
  "是不是", "是一种", "一个人", "来关注我", "最好的", "最大的", "有没有", "腾讯微博",
  "喜欢请收听", "有一种", "有木有", "是我的", "有一天", "而不是", "更多的", "会不会", "是真的", "是你的",
  "每个人都", "我正在", "快来看看", "睡不着", "条原创", "两个人", "微博的", "不是我", "一句话", "早上好", "童鞋们",
  "不是你", "也不是", "转发微博", "我爱你", "来看看吧", "不是很", "年月", "最重要的", "就好了", "这不是", "我也是",
  "微博上", "在土豆网", "网关注了", "土豆网关注", "这就是", "个人的", "巨蟹座", "在微博", "也不会", "也是一",
  "来自"
);

$enstopwords = array(
  "a", "about", "above", "after", "again", "against", "all", "am", "an", "and", 
  "any", "are", "aren't", "as", "at", "be", "because", "been", "before", 
  "being", "below", "between", "both", "but", "by", "can't", "cannot", "could", 
  "couldn't", "did", "didn't", "do", "does", "doesn't", "doing", "don't", "down", 
  "during", "each", "few", "for", "from", "further", "had", "hadn't", "has", 
  "hasn't", "have", "haven't", "having", "he", "he'd", "he'll", "he's", "her", 
  "here", "here's", "hers", "herself", "him", "himself", "his", "how", "how's", 
  "i", "i'd", "i'll", "i'm", "i've", "if", "in", "into", "is", "isn't", "it", 
  "it's", "its", "itself", "let's", "me", "more", "most", "mustn't", "my", 
  "myself", "no", "nor", "not", "of", "off", "on", "once", "only", "or", "other", 
  "ought", "our", "ours", "ourselves", "out", "over", "own", "same", "shan't", 
  "she", "she'd", "she'll", "she's", "should", "shouldn't", "so", "some", "such", 
  "than", "that", "that's", "the", "their", "theirs", "them", "themselves", "then", 
  "there", "there's", "these", "they", "they'd", "they'll", "they're", "they've", 
  "this", "those", "through", "to", "too", "under", "until", "up", "very", "was", 
  "wasn't", "we", "we'd", "we'll", "we're", "we've", "were", "weren't", "what", 
  "what's", "when", "when's", "where", "where's", "which", "while", "who", "who's", 
  "whom", "why", "why's", "with", "won't", "would", "wouldn't", "you", "you'd", 
  "you'll", "you're", "you've", "your", "yours", "yourself", "yourselves"
);

// load dictionaries
mmseg_load_chars('data/chars.dic');
mmseg_load_words('data/words-sogou.dic');
mmseg_load_words('data/words-custom.dic');
#mmseg_load_words('data/words.dic');

// set header to json and utf-8
header("Content-Type: text/json; charset=UTF-8");

if (is_array($obj)) {
  // if input is an array, do it one by one
  $answers = array();
  foreach ($obj as $idx => $value) {
    array_push($answers, segment_one($value, $detail, $rmstop));
  }
  echo json_encode($answers);
} else {
  // is a single string, do it once
  $tokens = segment_one($obj, $detail, $rmstop);
  echo json_encode($tokens);
}


// segmentation for one string
function segment_one($text, $detail, $rmstop) {
  global $zhstopwords, $enstopwords;

  // replace consecutive white characters to a space character
  $text = preg_replace('/\s+/', ' ', $text);

  $mmseg = mmseg_algor_create($text);
  $tokens = array();
  while ($token = mmseg_next_token($mmseg)) {
    $tokentext = $token['text'];
    // check if stopwords
    if ($rmstop) {
      if (in_array(strtolower($tokentext), $zhstopwords))
        continue;
      if (in_array(strtolower($tokentext), $enstopwords))
        continue;
    }
    if ($detail)
      array_push($tokens, $token);
    else
      array_push($tokens, $tokentext);
  }
  mmseg_algor_destroy($mmseg);
  return $tokens;
}
